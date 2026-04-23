<?php

namespace Modules\EcpayPayment\Backend\Service;

use Ecpay\Sdk\Factories\Factory;
use Ecpay\Sdk\Services\AesService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Modules\EcpayPayment\Backend\Model\EcpayPayment;
use Modules\EcpayPayment\Backend\Model\EcpayInvoice;
use Carbon\Carbon;

/**
 * 綠界站內付款服務
 * 
 * 負責處理信用卡付款流程：
 * 1. 建立付款 Token
 * 2. 執行付款
 * 3. 處理付款通知回調
 * 4. 查詢訂單付款狀態
 */
class EcpayPaymentService
{
    protected Factory $factory;
    protected string $apiUrl;
    protected string $merchantId;

    public function __construct()
    {
        $this->factory = new Factory([
            'hashKey' => config('ecpay.payment.HASH_KEY'),
            'hashIv'  => config('ecpay.payment.HASH_IV'),
        ]);

        $this->apiUrl     = config('ecpay.payment.API_URL');
        $this->merchantId = config('ecpay.payment.MERCHANT_ID');
    }

    /**
     * 建立付款 Token 並記錄到資料庫
     *
     * @param array $orderData 訂單資料
     * @param array $items 商品明細
     * @param array $invoiceData 發票資料（選填）
     * @return array
     */
    public function createToken(array $orderData, array $items, array $invoiceData = []): array
    {
        $service = $this->factory->create('PostWithAesJsonResponseService');

        $merchantTradeNo = $orderData['merchant_trade_no'] ?? $this->generateMerchantTradeNo();
        $itemNameString  = $this->formatItemName($items);

        $data = [
            'MerchantID'        => $this->merchantId,
            'RememberCard'      =>  0,
            'PaymentUIType'     => $orderData['payment_ui_type'] ?? 2,
            'ChoosePaymentList' => $orderData['choose_payment_list'] ?? '1', // 1=信用卡
            'OrderInfo'         => [
                'MerchantTradeDate' => $orderData['trade_date'] ?? now()->format('Y/m/d H:i:s'),
                'MerchantTradeNo'   => $merchantTradeNo,
                'TotalAmount'       => (int) $orderData['total_amount'],
                'ReturnURL'         => $orderData['return_url'] ?? config('ecpay.payment.RETURN_URL'),
                'TradeDesc'         => $orderData['trade_desc'] ?? 'Order',
                'ItemName'          => $itemNameString,
            ],
            'CardInfo' => [
                'Redeem'         => $orderData['redeem'] ?? 0,
                'OrderResultURL' => $orderData['order_result_url'] ?? config('ecpay.payment.ORDER_RESULT_URL'),
            ],
            'ConsumerInfo' => [
                'Phone'            => $orderData['phone'] ?? '',
            ],
        ];

        $input = [
            'MerchantID' => $this->merchantId,
            'RqHeader'   => ['Timestamp' => time()],
            'Data'       => $data,
        ];

        $url = $this->apiUrl . '/Merchant/GetTokenbyTrade';
        $response = $service->post($input, $url);

        $success = ($response['TransCode'] ?? -1) == 1;
        $payToken = $response['Data']['Token'] ?? null;

        // 使用交易確保付款和發票記錄一起建立
        $payment = null;
        $invoice = null;
        
        DB::transaction(function () use (
            $merchantTradeNo, $orderData, $success, $payToken, 
            $response, $data, $items, $invoiceData, &$payment, &$invoice
        ) {
            // 建立付款記錄
            $payment = EcpayPayment::create([
                'merchant_trade_no' => $merchantTradeNo,
                'total_amount'      => (int) $orderData['total_amount'],
                'trade_status'      => $success ? EcpayPayment::STATUS_PENDING : EcpayPayment::STATUS_FAILED,
                'member_id'         => $orderData['member_id'] ?? null,
                'member_email'      => $orderData['email'] ?? $invoiceData['buyer_email'] ?? null,
                'member_phone'      => $orderData['phone'] ?? null,
                'pay_token'         => $payToken,
                'trade_date'        => now(),
                'rtn_code'          => $response['Data']['RtnCode'] ?? null,
                'rtn_msg'           => $response['Data']['RtnMsg'] ?? null,
                'request_data'      => $data,
                'response_data'     => $response,
            ]);

            // 建立發票記錄（pending 狀態，等付款成功後再開立）
            $invoice = $this->createPendingInvoice($payment, $orderData, $items, $invoiceData);
        });

        $success = ($response['Data']['RtnCode'] ?? -1) == 1;

        return [
            'success'           => $success,
            'response'          => $response,
            'merchant_trade_no' => $merchantTradeNo,
            'payment_id'        => $payment->id,
            'invoice_id'        => $invoice?->id,
        ];
    }

    /**
     * 建立待開立發票記錄
     *
     * @param EcpayPayment $payment 付款記錄
     * @param array $orderData 訂單資料
     * @param array $items 商品明細
     * @param array $invoiceData 發票資料
     * @return EcpayInvoice|null
     */
    protected function createPendingInvoice(
        EcpayPayment $payment, 
        array $orderData, 
        array $items, 
        array $invoiceData
    ): ?EcpayInvoice {
        // 如果沒有發票資料，不建立發票記錄
        if (empty($invoiceData) && empty($orderData['need_invoice'])) {
            return null;
        }

        $totalAmount = (int) $orderData['total_amount'];
        
        // 計算稅額（預設含稅價，稅率 5%）
        $taxRate = $invoiceData['tax_rate'] ?? 0.05;
        $salesAmount = (int) round($totalAmount / (1 + $taxRate));
        $taxAmount = $totalAmount - $salesAmount;

        // 格式化發票品項
        $invoiceItems = $this->formatInvoiceItems($items);

        return EcpayInvoice::create([
            'ecpay_payment_id' => $payment->id,
            'status'           => EcpayInvoice::STATUS_PENDING,
            
            // 發票類型
            'type'             => $invoiceData['type'] ?? EcpayInvoice::TYPE_PERSONAL,
            'carrier_type'     => $invoiceData['carrier_type'] ?? null,
            'carrier_num'      => $invoiceData['carrier_num'] ?? null,
            
            // 公司發票
            'company_name'     => $invoiceData['company_name'] ?? null,
            'tax_id'           => $invoiceData['tax_id'] ?? null,
            
            // 捐贈
            'donation'         => $invoiceData['donation'] ?? false,
            'love_code'        => $invoiceData['love_code'] ?? null,
            
            // 金額
            'sales_amount'     => $salesAmount,
            'tax_amount'       => $taxAmount,
            'total_amount'     => $totalAmount,
            
            // 品項
            'items'            => $invoiceItems,
            
            // 寄送資訊（紙本發票用）
            'print_name'       => $invoiceData['print_name'] ?? null,
            'print_address'    => $invoiceData['print_address'] ?? null,
            'print_phone'      => $invoiceData['print_phone'] ?? null,
            
            // 買受人
            'buyer_name'       => $invoiceData['buyer_name'] ?? $orderData['member_name'] ?? null,
            'buyer_email'      => $invoiceData['buyer_email'] ?? $orderData['email'] ?? null,
            'buyer_phone'      => $invoiceData['buyer_phone'] ?? $orderData['phone'] ?? null,
        ]);
    }

    /**
     * 格式化發票品項
     *
     * @param array $items 商品列表
     * @return array
     */
    protected function formatInvoiceItems(array $items): array
    {
        if (empty($items)) {
            return [[
                'name'     => '商品',
                'quantity' => 1,
                'unit'     => '式',
                'price'    => 0,
                'amount'   => 0,
            ]];
        }

        return array_map(function ($item) {
            return [
                'name'     => mb_substr($item['name'] ?? $item['product_name'] ?? '商品', 0, 100),
                'quantity' => $item['quantity'] ?? $item['qty'] ?? 1,
                'unit'     => $item['unit'] ?? '個',
                'price'    => $item['price'] ?? $item['unit_price'] ?? 0,
                'amount'   => $item['amount'] ?? (($item['price'] ?? 0) * ($item['quantity'] ?? 1)),
            ];
        }, $items);
    }

    /**
     * 執行付款
     *
     * @param string $payToken 付款 Token
     * @param string $merchantTradeNo 交易編號
     * @return array
     */
    public function executePayment(string $payToken, string $merchantTradeNo): array
    {
        $postService = $this->factory->create('PostWithAesJsonResponseService');

        $data = [
            'MerchantID'      => $this->merchantId,
            'PayToken'        => $payToken,
            'MerchantTradeNo' => $merchantTradeNo,
        ];

        $input = [
            'MerchantID' => $this->merchantId,
            'RqHeader'   => ['Timestamp' => time()],
            'Data'       => $data,
        ];

        $url = $this->apiUrl . '/Merchant/CreatePayment';
        
        Log::info('綠界執行付款請求', [
            'url' => $url,
            'merchant_trade_no' => $merchantTradeNo,
            'pay_token' => $payToken,
        ]);
        
        $response = $postService->post($input, $url);
        
        Log::info('綠界執行付款回應', [
            'response' => $response,
        ]);
       
        $success = ($response['Data']['RtnCode'] ?? -1) == 1;

        // 更新付款記錄狀態為處理中
        $payment = EcpayPayment::byMerchantTradeNo($merchantTradeNo)->first();
        if ($payment && $success) {
            $payment->update([
                'trade_status' => EcpayPayment::STATUS_PROCESSING,
            ]);
        }

        return [
            'success'  => $success,
            'response' => $response,
        ];
    }

    /**
     * 處理付款通知回調 (ReturnURL) 並更新資料庫
     *
     * @param array $payload 綠界回傳資料
     * @return array 解密後的付款結果
     */
    public function handleNotify(array $payload): array
    {
        $aesService = $this->factory->create(AesService::class);
        $decrypt = [];

        if (isset($payload['Data'])) {
            $decrypt = $aesService->decrypt($payload['Data']);
        }

        $rtnCode         = $decrypt['RtnCode'] ?? -1;
        $merchantTradeNo = $decrypt['OrderInfo']['MerchantTradeNo'] ?? '';
        $tradeStatus     = $decrypt['OrderInfo']['TradeStatus'] ?? -1;
        $tradeNo         = $decrypt['OrderInfo']['TradeNo'] ?? null;
        $paymentType     = $decrypt['OrderInfo']['PaymentType'] ?? null;

        Log::info('綠界付款通知', [
            'raw_request' => $payload,
            'decrypted'   => $decrypt,
        ]);

        // 更新付款記錄
        $payment = EcpayPayment::byMerchantTradeNo($merchantTradeNo)->first();
        $isPaid = ($rtnCode == 1 && $tradeStatus == 1);

        if ($payment) {
            // 如果已經是付款成功狀態，不再更新（避免重複通知）
            if (!$payment->isPaid()) {
                $payment->update([
                    'trade_status'  => $isPaid ? EcpayPayment::STATUS_PAID : EcpayPayment::STATUS_FAILED,
                    'trade_no'      => $tradeNo,
                    'payment_type'  => $paymentType,
                    'rtn_code'      => $rtnCode,
                    'rtn_msg'       => $decrypt['RtnMsg'] ?? null,
                    'payment_date'  => $isPaid ? now() : null,
                    'notify_data'   => $decrypt,
                ]);
            }
        }

        return [
            'success'           => $isPaid,
            'rtn_code'          => $rtnCode,
            'trade_status'      => $tradeStatus,
            'merchant_trade_no' => $merchantTradeNo,
            'payment'           => $payment,
            'data'              => $decrypt,
        ];
    }

    /**
     * 處理付款結果導向 (OrderResultURL)
     *
     * @param array $payload 綠界回傳資料
     * @return array 解密後的結果
     */
    public function handleResult(array $payload): array
    {
        $aesService = $this->factory->create(AesService::class);

        $resultData = $payload['ResultData'] ?? '';
        if (is_string($resultData)) {
            $resultData = json_decode($resultData, true);
        }

        $decrypt = [];
        if (isset($resultData['Data'])) {
            $decrypt = $aesService->decrypt($resultData['Data']);
        }

        Log::info('綠界付款結果導向', ['payload' => $payload, 'decrypted' => $decrypt]);

        return [
            'merchant_trade_no' => $decrypt['OrderInfo']['MerchantTradeNo'] ?? '',
            'trade_status'      => $decrypt['OrderInfo']['TradeStatus'] ?? -1,
            'data'              => $decrypt,
        ];
    }

    /**
     * 查詢訂單付款狀態
     *
     * @param string $merchantTradeNo 交易編號
     * @return array
     */
    public function queryOrder(string $merchantTradeNo): array
    {
        $postService = $this->factory->create('PostWithAesJsonResponseService');

        $data = [
            'MerchantID'      => $this->merchantId,
            'MerchantTradeNo' => $merchantTradeNo,
        ];

        $input = [
            'MerchantID' => $this->merchantId,
            'RqHeader'   => ['Timestamp' => time()],
            'Data'       => $data,
        ];

        $url = config('ecpay.payment.SEARCH_ORDER_URL');
        $response = $postService->post($input, $url);

        $orderInfo   = $response['Data']['OrderInfo'] ?? [];
        $tradeStatus = $orderInfo['TradeStatus'] ?? -1;

        // 同步更新本地付款記錄
        $payment = EcpayPayment::byMerchantTradeNo($merchantTradeNo)->first();
        if ($payment && !$payment->isPaid()) {
            if ($tradeStatus == 1) {
                $payment->update([
                    'trade_status' => EcpayPayment::STATUS_PAID,
                    'payment_date' => now(),
                ]);
            } elseif ($tradeStatus == 0) {
                // 綠界建議 10 分鐘後才確定付款狀態
                $createdAt = $payment->created_at;
                if ($createdAt && $createdAt->diffInMinutes(now()) >= 10) {
                    $payment->update([
                        'trade_status' => EcpayPayment::STATUS_FAILED,
                    ]);
                }
            }
        }

        return [
            'success'           => isset($orderInfo['TradeStatus']),
            'merchant_trade_no' => $orderInfo['MerchantTradeNo'] ?? $merchantTradeNo,
            'trade_status'      => $tradeStatus, // 0=未付款, 1=已付款
            'trade_date'        => $orderInfo['TradeDate'] ?? null,
            'payment'           => $payment,
            'response'          => $response,
        ];
    }

    /**
     * 依交易編號取得付款記錄
     *
     * @param string $merchantTradeNo
     * @return EcpayPayment|null
     */
    public function getPaymentByTradeNo(string $merchantTradeNo): ?EcpayPayment
    {
        return EcpayPayment::byMerchantTradeNo($merchantTradeNo)->first();
    }

    /**
     * 依訂單ID取得付款記錄
     *
     * @param int $orderId
     * @return EcpayPayment|null
     */
    public function getPaymentByOrderId(int $orderId): ?EcpayPayment
    {
        return EcpayPayment::where('order_id', $orderId)->latest()->first();
    }

    /**
     * 產生交易編號
     * 
     * 綠界限制：
     * - 最大長度 20 字元
     * - 僅支援英數字 a-zA-Z0-9
     * - 不可重複
     *
     * @param string|null $prefix 前綴（預設 2 字元）
     * @return string
     */
    public function generateMerchantTradeNo(?string $prefix = 'OD'): string
    {
        // 格式：前綴(2) + 日期時間(14) + 隨機數(4) = 20 字元
        // 例如：OD202602011234561234
        $timestamp = now()->format('YmdHis'); // 14 字元
        $random = str_pad(random_int(0, 9999), 4, '0', STR_PAD_LEFT); // 4 字元
        
        // 確保前綴不超過 2 字元，且只包含英數字
        $prefix = preg_replace('/[^a-zA-Z0-9]/', '', $prefix);
        $prefix = substr($prefix, 0, 2);
        
        // 如果前綴不足 2 字元，用 0 補齊
        $prefix = str_pad($prefix, 2, '0', STR_PAD_RIGHT);
        
        return $prefix . $timestamp . $random;
    }

    /**
     * 格式化商品名稱
     *
     * @param array $items 商品列表
     * @return string
     */
    protected function formatItemName(array $items): string
    {
        if (empty($items)) {
            return 'Product';
        }

        $itemNames = array_map(function ($item) {
            $name = $item['name'] ?? $item['product_name'] ?? 'Item';
            return mb_substr($name, 0, 200);
        }, $items);

        $itemNameString = implode('#', $itemNames);

        // 綠界限制 400 字元
        if (mb_strlen($itemNameString) > 400) {
            $itemNameString = mb_substr($itemNameString, 0, 400);
        }

        return $itemNameString;
    }
}
