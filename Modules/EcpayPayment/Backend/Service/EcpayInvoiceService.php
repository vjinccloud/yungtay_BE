<?php

namespace Modules\EcpayPayment\Backend\Service;

use Ecpay\Sdk\Factories\Factory;
use Illuminate\Support\Facades\Log;
use Modules\EcpayPayment\Backend\Model\EcpayPayment;
use Modules\EcpayPayment\Backend\Model\EcpayInvoice;

/**
 * 綠界電子發票服務
 * 
 * 負責處理發票相關流程：
 * 1. 統一編號驗證
 * 2. 手機條碼驗證
 * 3. 愛心碼驗證
 * 4. 開立發票
 * 5. 作廢發票
 * 6. 查詢發票
 */
class EcpayInvoiceService
{
    protected Factory $factory;
    protected string $apiUrl;
    protected string $merchantId;

    public function __construct()
    {
        $this->factory = new Factory([
            'hashKey' => config('ecpay.invoice.HASH_KEY'),
            'hashIv'  => config('ecpay.invoice.HASH_IV'),
        ]);

        $this->apiUrl     = config('ecpay.invoice.API_URL');
        $this->merchantId = config('ecpay.invoice.MERCHANT_ID');
    }

    /**
     * 驗證統一編號並取得公司名稱
     *
     * @param string $taxId 統一編號（8碼）
     * @return array
     */
    public function verifyTaxId(string $taxId): array
    {
        $postService = $this->factory->create('PostWithAesJsonResponseService');

        $data = [
            'MerchantID'        => $this->merchantId,
            'UnifiedBusinessNo' => $taxId,
        ];

        $input = [
            'MerchantID' => $this->merchantId,
            'RqHeader'   => ['Timestamp' => time()],
            'Data'       => $data,
        ];

        $url = $this->apiUrl . '/B2CInvoice/GetCompanyNameByTaxID';
        $response = $postService->post($input, $url);

        $rtnCode = $response['Data']['RtnCode'] ?? -1;

        return [
            'success'      => $rtnCode == 1,
            'rtn_code'     => $rtnCode,
            'rtn_msg'      => $response['Data']['RtnMsg'] ?? '',
            'company_name' => $response['Data']['CompanyName'] ?? '',
            'can_issue'    => !in_array($rtnCode, [1200125, 2027000]), // 只有格式錯誤才不能開立
        ];
    }

    /**
     * 驗證手機條碼
     *
     * @param string $carrierNum 手機條碼（/開頭+7碼）
     * @return array
     */
    public function verifyMobileCarrier(string $carrierNum): array
    {
        $postService = $this->factory->create('PostWithAesJsonResponseService');

        $data = [
            'MerchantID' => $this->merchantId,
            'BarCode'    => $carrierNum,
        ];

        $input = [
            'MerchantID' => $this->merchantId,
            'RqHeader'   => ['Timestamp' => time()],
            'Data'       => $data,
        ];

        $url = $this->apiUrl . '/B2CInvoice/CheckBarcode';
        $response = $postService->post($input, $url);

        $rtnCode = $response['Data']['RtnCode'] ?? -1;

        return [
            'success'  => $rtnCode == 1,
            'rtn_code' => $rtnCode,
            'rtn_msg'  => $response['Data']['RtnMsg'] ?? '',
            'is_exist' => $response['Data']['IsExist'] ?? 'N',
        ];
    }

    /**
     * 驗證愛心碼
     *
     * @param string $loveCode 愛心碼（3-7碼數字）
     * @return array
     */
    public function verifyLoveCode(string $loveCode): array
    {
        $postService = $this->factory->create('PostWithAesJsonResponseService');

        $data = [
            'MerchantID' => $this->merchantId,
            'LoveCode'   => $loveCode,
        ];

        $input = [
            'MerchantID' => $this->merchantId,
            'RqHeader'   => ['Timestamp' => time()],
            'Data'       => $data,
        ];

        $url = $this->apiUrl . '/B2CInvoice/CheckLoveCode';
        $response = $postService->post($input, $url);

        $rtnCode = $response['Data']['RtnCode'] ?? -1;

        return [
            'success'  => $rtnCode == 1,
            'rtn_code' => $rtnCode,
            'rtn_msg'  => $response['Data']['RtnMsg'] ?? '',
            'is_exist' => $response['Data']['IsExist'] ?? 'N',
        ];
    }

    /**
     * 開立發票
     *
     * @param EcpayInvoice $invoice 發票記錄
     * @return array
     */
    public function issueInvoice(EcpayInvoice $invoice): array
    {
        $postService = $this->factory->create('PostWithAesJsonResponseService');

        // 取得關聯的付款記錄
        $payment = $invoice->payment;

        // 決定 Print 參數
        $print = $this->determinePrintValue($invoice);

        // 決定載具類型對應綠界格式
        $carrierType = $this->mapCarrierType($invoice->carrier_type);

        // 格式化品項
        $items = $this->formatItems($invoice->items ?? [], $invoice->total_amount);

        // 優先使用 Email 通知，沒有 Email 時才用手機
        $customerEmail = $invoice->buyer_email ?? '';
        $customerPhone = empty($customerEmail) ? ($invoice->buyer_phone ?? '') : '';

        $data = [
            'MerchantID'         => $this->merchantId,
            'RelateNumber'       => $this->generateRelateNumber($invoice->id),
            'CustomerID'         => $payment->member_id ?? '',
            'CustomerIdentifier' => $invoice->tax_id ?? '',           // 統編（空=個人）
            'CustomerName'       => $invoice->company_name ?? $invoice->buyer_name ?? '',
            'CustomerAddr'       => $invoice->print_address ?? '',
            //'CustomerPhone'      => $customerPhone,
            'CustomerEmail'      => $customerEmail,
            'Print'              => $print,
            'Donation'           => $invoice->donation ? '1' : '0',
            'LoveCode'           => $invoice->love_code ?? '',
            'CarrierType'        => $carrierType,
            'CarrierNum'         => $invoice->carrier_num ?? '',
            'TaxType'            => '1',  // 應稅
            'SalesAmount'        => $invoice->total_amount,
            'InvoiceRemark'      => '',
            'InvType'            => '07', // 一般稅額
            'vat'                => '1',  // 含稅
            'Items'              => $items,
        ];

        $input = [
            'MerchantID' => $this->merchantId,
            'RqHeader'   => ['Timestamp' => time()],
            'Data'       => $data,
        ];

        $url = $this->apiUrl . '/B2CInvoice/Issue';

        Log::info('綠界開立發票請求', [
            'invoice_id' => $invoice->id,
            'data'       => $data,
        ]);

        $response = $postService->post($input, $url);

        Log::info('綠界開立發票回應', [
            'invoice_id' => $invoice->id,
            'response'   => $response,
        ]);

        $rtnCode   = $response['Data']['RtnCode'] ?? -1;
        $success   = $rtnCode == 1;
        $invoiceNo = $response['Data']['InvoiceNo'] ?? null;

        // 更新發票記錄
        $updateData = [
            'rtn_code'      => $rtnCode,
            'rtn_msg'       => $response['Data']['RtnMsg'] ?? '',
            'request_data'  => $data,
            'response_data' => $response,
        ];

        if ($success && $invoiceNo) {
            $updateData['status']        = EcpayInvoice::STATUS_ISSUED;
            $updateData['invoice_no']    = $invoiceNo;
            $updateData['random_number'] = $response['Data']['RandomNumber'] ?? '';
            $updateData['invoice_date']  = $response['Data']['InvoiceDate'] ?? now();
            $updateData['relate_number'] = $data['RelateNumber'];
        }

        $invoice->update($updateData);

        return [
            'success'       => $success,
            'invoice_no'    => $invoiceNo,
            'random_number' => $response['Data']['RandomNumber'] ?? '',
            'invoice_date'  => $response['Data']['InvoiceDate'] ?? '',
            'rtn_code'      => $rtnCode,
            'rtn_msg'       => $response['Data']['RtnMsg'] ?? '',
            'response'      => $response,
        ];
    }

    /**
     * 批次開立待處理的發票
     *
     * @param int $limit 每次處理數量
     * @return array 處理結果
     */
    public function issuePendingInvoices(int $limit = 50): array
    {
        // 找出已付款但發票待開立的記錄
        $invoices = EcpayInvoice::where('status', EcpayInvoice::STATUS_PENDING)
            ->whereHas('payment', function ($query) {
                $query->where('trade_status', EcpayPayment::STATUS_PAID);
            })
            ->limit($limit)
            ->get();

        $results = [
            'total'   => $invoices->count(),
            'success' => 0,
            'failed'  => 0,
            'details' => [],
        ];

        foreach ($invoices as $invoice) {
            try {
                $result = $this->issueInvoice($invoice);

                if ($result['success']) {
                    $results['success']++;
                } else {
                    $results['failed']++;
                }

                $results['details'][] = [
                    'invoice_id' => $invoice->id,
                    'payment_id' => $invoice->ecpay_payment_id,
                    'success'    => $result['success'],
                    'invoice_no' => $result['invoice_no'],
                    'rtn_msg'    => $result['rtn_msg'],
                ];
            } catch (\Exception $e) {
                $results['failed']++;
                $results['details'][] = [
                    'invoice_id' => $invoice->id,
                    'payment_id' => $invoice->ecpay_payment_id,
                    'success'    => false,
                    'error'      => $e->getMessage(),
                ];

                Log::error('開立發票失敗', [
                    'invoice_id' => $invoice->id,
                    'error'      => $e->getMessage(),
                ]);
            }
        }

        return $results;
    }

    /**
     * 作廢發票
     *
     * @param EcpayInvoice $invoice 發票記錄
     * @param string $reason 作廢原因
     * @return array
     */
    public function voidInvoice(EcpayInvoice $invoice, string $reason = ''): array
    {
        if ($invoice->status !== EcpayInvoice::STATUS_ISSUED) {
            return [
                'success' => false,
                'rtn_msg' => '只能作廢已開立的發票',
            ];
        }

        $postService = $this->factory->create('PostWithAesJsonResponseService');

        $data = [
            'MerchantID' => $this->merchantId,
            'InvoiceNo'  => $invoice->invoice_no,
            'VoidReason' => $reason ?: '作廢',
        ];

        $input = [
            'MerchantID' => $this->merchantId,
            'RqHeader'   => ['Timestamp' => time()],
            'Data'       => $data,
        ];

        $url = $this->apiUrl . '/B2CInvoice/Invalid';
        $response = $postService->post($input, $url);

        $rtnCode = $response['Data']['RtnCode'] ?? -1;
        $success = $rtnCode == 1;

        if ($success) {
            $invoice->update([
                'status'      => EcpayInvoice::STATUS_VOID,
                'void_date'   => now(),
                'void_reason' => $reason,
            ]);
        }

        return [
            'success'  => $success,
            'rtn_code' => $rtnCode,
            'rtn_msg'  => $response['Data']['RtnMsg'] ?? '',
        ];
    }

    /**
     * 查詢發票
     *
     * @param string $relateNumber 關聯編號
     * @return array
     */
    public function queryInvoice(string $relateNumber): array
    {
        $postService = $this->factory->create('PostWithAesJsonResponseService');

        $data = [
            'MerchantID'   => $this->merchantId,
            'RelateNumber' => $relateNumber,
        ];

        $input = [
            'MerchantID' => $this->merchantId,
            'RqHeader'   => ['Timestamp' => time()],
            'Data'       => $data,
        ];

        $url = $this->apiUrl . '/B2CInvoice/GetIssue';
        $response = $postService->post($input, $url);

        return [
            'success'  => ($response['Data']['RtnCode'] ?? -1) == 1,
            'response' => $response,
        ];
    }

    /**
     * 決定 Print 參數值
     * 
     * carrier_type: 1=會員載具, 2=手機載具, 3=統編, 4=捐贈
     * 
     * 規則：
     * - 捐贈(4) → 不列印
     * - 會員載具(1) → 不列印（存綠界載具）
     * - 手機載具(2) → 不列印（存手機條碼）
     * - 統編(3) → 列印（公司需要紙本）
     */
    protected function determinePrintValue(EcpayInvoice $invoice): string
    {
        $carrierType = (int) $invoice->carrier_type;

        // 捐贈 → 不列印
        if ($carrierType === 4 || $invoice->donation) {
            return '0';
        }

        // 統編 → 列印
        if ($carrierType === 3 || !empty($invoice->tax_id)) {
            return '1';
        }

        // 會員載具或手機載具 → 不列印
        if (in_array($carrierType, [1, 2])) {
            return '0';
        }

        // 預設不列印
        return '0';
    }

    /**
     * 對應載具類型到綠界格式
     * 
     * 我們的格式: 1=會員載具, 2=手機載具, 3=統編, 4=捐贈
     * 綠界格式: ''=無, '1'=綠界會員, '2'=自然人憑證, '3'=手機條碼
     * 
     * 統編(3)和捐贈(4)不需要載具類型
     */
    protected function mapCarrierType(?int $type): string
    {
        $map = [
            1 => '1',  // 會員載具 → 綠界會員載具
            2 => '3',  // 手機載具 → 綠界手機條碼
            3 => '',   // 統編 → 無載具
            4 => '',   // 捐贈 → 無載具
        ];

        return $map[$type] ?? '';
    }

    /**
     * 格式化發票品項
     */
    protected function formatItems(?array $items, int $totalAmount): array
    {
        // 如果沒有品項，或品項沒有有效價格，建立一筆預設品項
        if (empty($items) || !$this->hasValidItemPrices($items)) {
            return [[
                'ItemSeq'    => 1,
                'ItemName'   => '商品',
                'ItemCount'  => 1,
                'ItemWord'   => '式',
                'ItemPrice'  => $totalAmount,
                'ItemAmount' => $totalAmount,
            ]];
        }

        return array_map(function ($item, $index) {
            $price    = (int) ($item['price'] ?? 0);
            $quantity = (int) ($item['quantity'] ?? 1);
            $amount   = $price * $quantity;

            return [
                'ItemSeq'    => $index + 1,
                'ItemName'   => mb_substr($item['name'] ?? '商品', 0, 100),
                'ItemCount'  => $quantity,
                'ItemWord'   => $item['unit'] ?? '個',
                'ItemPrice'  => $price,
                'ItemAmount' => $amount,
            ];
        }, $items, array_keys($items));
    }

    /**
     * 檢查品項是否有有效價格
     */
    protected function hasValidItemPrices(array $items): bool
    {
        foreach ($items as $item) {
            if (isset($item['price']) && $item['price'] > 0) {
                return true;
            }
        }
        return false;
    }

    /**
     * 產生發票關聯編號
     */
    protected function generateRelateNumber(int $invoiceId): string
    {
        // 格式：INV + 日期(8) + ID補齊(8) = 19字元
        return 'INV' . now()->format('Ymd') . str_pad($invoiceId, 8, '0', STR_PAD_LEFT);
    }
}
