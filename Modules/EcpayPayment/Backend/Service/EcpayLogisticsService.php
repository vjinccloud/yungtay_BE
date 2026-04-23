<?php

namespace Modules\EcpayPayment\Backend\Service;

use Ecpay\Sdk\Factories\Factory;
use Ecpay\Sdk\Response\ArrayResponse;
use Illuminate\Support\Facades\Log;
use Modules\EcpayPayment\Backend\Model\EcpayLogistics;
use Modules\EcpayPayment\Backend\Model\EcpayCvsStore;

/**
 * 綠界物流服務 (C2C 超商取貨)
 * 
 * 負責處理超商物流相關功能：
 * 1. 選擇門市地圖
 * 2. 建立物流訂單
 * 3. 列印托運單
 * 4. 物流狀態回調
 */
class EcpayLogisticsService
{
    protected Factory $factory;
    protected string $merchantId;
    protected string $hashKey;
    protected string $hashIv;
    protected string $apiUrl;

    /**
     * 物流子類型對應表
     */
    protected const LOGISTICS_SUB_TYPES = [
        '711'     => 'UNIMARTC2C',
        'unimart' => 'UNIMARTC2C',
        'family'  => 'FAMIC2C',
        'fami'    => 'FAMIC2C',
        'hilife'  => 'HILIFEC2C',
        'okmart'  => 'OKMARTC2C',
    ];

    /**
     * 列印動作對應表
     */
    protected const PRINT_ACTIONS = [
        '711'     => 'PrintUniMartC2COrderInfo',
        'unimart' => 'PrintUniMartC2COrderInfo',
        'family'  => 'PrintFAMIC2COrderInfo',
        'fami'    => 'PrintFAMIC2COrderInfo',
        'hilife'  => 'PrintHILIFEC2COrderInfo',
        'okmart'  => 'PrintOKMARTC2COrderInfo',
    ];

    public function __construct()
    {
        $this->merchantId = config('ecpay.logistics.MERCHANT_ID');
        $this->hashKey    = config('ecpay.logistics.HASH_KEY');
        $this->hashIv     = config('ecpay.logistics.HASH_IV');
        $this->apiUrl     = config('ecpay.logistics.API_URL');

        $this->factory = new Factory([
            'hashKey'    => $this->hashKey,
            'hashIv'     => $this->hashIv,
            'hashMethod' => 'md5',
        ]);
    }

    /**
     * 產生前往綠界門市地圖的自動提交表單
     *
     * @param string $logisticsSubType 超商類型 (711/family/hilife/okmart 或 UNIMARTC2C 等)
     * @param string $merchantTradeNo 交易編號
     * @param string|null $serverReplyUrl 回調網址
     * @return string HTML 表單
     */
    public function generateMapForm(
        string $logisticsSubType,
        string $merchantTradeNo,
        ?string $serverReplyUrl = null
    ): string {
        $autoSubmitFormService = $this->factory->create('AutoSubmitFormWithCmvService');

        $subType = $this->resolveLogisticsSubType($logisticsSubType);

        $input = [
            'MerchantID'       => $this->merchantId,
            'MerchantTradeNo'  => $merchantTradeNo,
            'LogisticsType'    => 'CVS',
            'LogisticsSubType' => $subType,
            'IsCollection'     => 'Y',
            'ServerReplyURL'   => $serverReplyUrl ?? route('ecpay.logistics.map.callback'),
        ];

        return $autoSubmitFormService->generate($input, $this->apiUrl . '/Express/map');
    }

    /**
     * 處理門市地圖選擇回調並存入資料庫
     *
     * @param array $postData POST 資料
     * @return array
     */
    public function handleMapCallback(array $postData): array
    {
        try {
            $response = $this->factory->create(ArrayResponse::class);
            $data = $response->get($postData);

            if (empty($data['MerchantTradeNo']) || empty($data['CVSStoreID'])) {
                Log::warning('CVS Map Callback 缺少必要欄位', ['data' => $data]);
                return [
                    'success' => false,
                    'message' => '門市資料異常',
                ];
            }

            // 儲存門市資訊到資料庫
            $cvsStore = EcpayCvsStore::updateOrCreateByTradeNo($data['MerchantTradeNo'], [
                'logistics_sub_type' => $data['LogisticsSubType'] ?? null,
                'cvs_store_id'       => $data['CVSStoreID'],
                'cvs_store_name'     => $data['CVSStoreName'] ?? null,
                'cvs_address'        => $data['CVSAddress'] ?? null,
                'cvs_telephone'      => $data['CVSTelephone'] ?? null,
                'cvs_outside'        => $data['CVSOutSide'] ?? '0',
                'extra_data'         => $data['ExtraData'] ?? null,
            ]);

            return [
                'success'           => true,
                'merchant_trade_no' => $data['MerchantTradeNo'],
                'store_id'          => $data['CVSStoreID'],
                'store_name'        => $data['CVSStoreName'] ?? '',
                'store_address'     => $data['CVSAddress'] ?? '',
                'store_telephone'   => $data['CVSTelephone'] ?? '',
                'sub_type'          => $data['LogisticsSubType'] ?? '',
                'outside'           => $data['CVSOutSide'] ?? '',
                'extra_data'        => $data['ExtraData'] ?? '',
                'cvs_store'         => $cvsStore,
                'raw_data'          => $data,
            ];
        } catch (\Throwable $e) {
            Log::error('CVS Map Callback 失敗：' . $e->getMessage(), [
                'exception' => $e,
                'post'      => $postData,
            ]);

            return [
                'success' => false,
                'message' => '處理門市回調失敗',
            ];
        }
    }

    /**
     * 建立超商物流訂單
     *
     * @param array $orderData 訂單資料
     * @return array
     */
    public function createLogisticsOrder(array $orderData): array
    {
        $postService = $this->factory->create('PostWithCmvEncodedStrResponseService');

        $logisticsSubType = $this->resolveLogisticsSubType($orderData['shipping_type'] ?? '711');
        
        // 判斷是否為貨到付款
        $isCollection = ($orderData['payment_type'] ?? '') === 'cod' ? 'Y' : 'N';
        $isCollectionValue = $orderData['is_collection'] ?? $isCollection;

        $input = [
            'MerchantID'         => $this->merchantId,
            'MerchantTradeNo'    => $orderData['order_number'],
            'MerchantTradeDate'  => $orderData['trade_date'] ?? now()->format('Y/m/d H:i:s'),
            'IsCollection'       => $isCollectionValue,
            'LogisticsType'      => 'CVS',
            'LogisticsSubType'   => $logisticsSubType,
            'GoodsAmount'        => (int) $orderData['amount'],
            'GoodsName'          => $orderData['goods_name'] ?? '商品',
            'SenderName'         => $orderData['sender_name'] ?? config('app.name'),
            'SenderCellPhone'    => $orderData['sender_phone'] ?? '',
            'ReceiverName'       => $orderData['receiver_name'],
            'ReceiverCellPhone'  => $orderData['receiver_phone'],
            'ServerReplyURL'     => $orderData['server_reply_url'] ?? route('ecpay.logistics.callback'),
            'ReceiverStoreID'    => $orderData['store_id'],
        ];

        $response = $postService->post($input, $this->apiUrl . '/Express/Create');

        $success = ($response['RtnCode'] ?? -1) == 1;

        // 建立物流記錄
        $logistics = EcpayLogistics::create([
            'order_id'               => $orderData['order_id'] ?? null,
            'order_number'           => $orderData['order_number'],
            'merchant_trade_no'      => $orderData['order_number'],
            'all_pay_logistics_id'   => $response['AllPayLogisticsID'] ?? null,
            'cvs_payment_no'         => $response['CVSPaymentNo'] ?? null,
            'cvs_validation_no'      => $response['CVSValidationNo'] ?? null,
            'logistics_type'         => 'CVS',
            'logistics_sub_type'     => $logisticsSubType,
            'goods_amount'           => (int) $orderData['amount'],
            'is_collection'          => $isCollectionValue,
            'sender_name'            => $orderData['sender_name'] ?? config('app.name'),
            'sender_phone'           => $orderData['sender_phone'] ?? null,
            'receiver_name'          => $orderData['receiver_name'],
            'receiver_phone'         => $orderData['receiver_phone'],
            'receiver_store_id'      => $orderData['store_id'],
            'receiver_store_name'    => $orderData['store_name'] ?? null,
            'receiver_store_address' => $orderData['store_address'] ?? null,
            'rtn_code'               => $response['RtnCode'] ?? null,
            'rtn_msg'                => $response['RtnMsg'] ?? null,
            'request_data'           => $input,
            'response_data'          => $response,
        ]);

        return [
            'success'              => $success,
            'rtn_code'             => $response['RtnCode'] ?? -1,
            'rtn_msg'              => $response['RtnMsg'] ?? '',
            'all_pay_logistics_id' => $response['AllPayLogisticsID'] ?? '',
            'cvs_payment_no'       => $response['CVSPaymentNo'] ?? '',
            'cvs_validation_no'    => $response['CVSValidationNo'] ?? '',
            'logistics'            => $logistics,
            'response'             => $response,
        ];
    }

    /**
     * 處理物流狀態回調並更新資料庫
     *
     * @param array $postData POST 資料
     * @return array
     */
    public function handleLogisticsCallback(array $postData): array
    {
        $merchantTradeNo  = $postData['MerchantTradeNo'] ?? null;
        $rtnCode          = $postData['RtnCode'] ?? null;
        $rtnMsg           = $postData['RtnMsg'] ?? null;
        $logisticsStatus  = $postData['LogisticsStatus'] ?? null;
        $allPayLogisticsId = $postData['AllPayLogisticsID'] ?? null;

        if (!$merchantTradeNo || !$rtnCode) {
            Log::warning('綠界物流通知缺少必要欄位', $postData);
            return [
                'success' => false,
                'message' => 'Missing required fields',
            ];
        }

        Log::info('綠界物流狀態背景通知', $postData);

        // 更新物流記錄
        $logistics = EcpayLogistics::byMerchantTradeNo($merchantTradeNo)->first();
        if ($logistics) {
            $logistics->update([
                'logistics_status'      => $logisticsStatus,
                'logistics_status_name' => EcpayLogistics::STATUS_MAP[$logisticsStatus] ?? null,
                'rtn_code'              => $rtnCode,
                'rtn_msg'               => $rtnMsg,
                'update_status_date'    => isset($postData['UpdateStatusDate']) 
                    ? \Carbon\Carbon::parse($postData['UpdateStatusDate']) 
                    : now(),
                'callback_data'         => $postData,
            ]);
        }

        return [
            'success'            => true,
            'merchant_trade_no'  => $merchantTradeNo,
            'rtn_code'           => $rtnCode,
            'rtn_msg'            => $rtnMsg,
            'logistics_status'   => $logisticsStatus,
            'goods_amount'       => $postData['GoodsAmount'] ?? 0,
            'update_status_date' => $postData['UpdateStatusDate'] ?? '',
            'logistics'          => $logistics,
            'raw_data'           => $postData,
        ];
    }

    /**
     * 依物流編號取得物流記錄
     *
     * @param string $logisticsId
     * @return EcpayLogistics|null
     */
    public function getLogisticsByLogisticsId(string $logisticsId): ?EcpayLogistics
    {
        return EcpayLogistics::byLogisticsId($logisticsId)->first();
    }

    /**
     * 依訂單編號取得物流記錄
     *
     * @param string $orderNumber
     * @return EcpayLogistics|null
     */
    public function getLogisticsByOrderNumber(string $orderNumber): ?EcpayLogistics
    {
        return EcpayLogistics::where('order_number', $orderNumber)->latest()->first();
    }

    /**
     * 產生列印托運單表單
     *
     * @param array $logisticsIds 物流編號列表
     * @param array $paymentNos CVS 付款編號列表
     * @param array $validationNos CVS 驗證碼列表 (711 專用)
     * @param string $shippingType 超商類型
     * @return string HTML 表單
     */
    public function generatePrintForm(
        array $logisticsIds,
        array $paymentNos,
        array $validationNos,
        string $shippingType
    ): string {
        $autoSubmitFormService = $this->factory->create('AutoSubmitFormWithCmvService');
        $action = self::PRINT_ACTIONS[strtolower($shippingType)] ?? 'PrintUniMartC2COrderInfo';

        $input = [
            'MerchantID'        => $this->merchantId,
            'AllPayLogisticsID' => implode(',', $logisticsIds),
            'CVSPaymentNo'      => implode(',', $paymentNos),
        ];

        // 711 需要額外帶 CVSValidationNo
        if (in_array(strtolower($shippingType), ['711', 'unimart'])) {
            $input['CVSValidationNo'] = implode(',', $validationNos);
        }

        return $autoSubmitFormService->generate($input, $this->apiUrl . '/Express/' . $action);
    }

    /**
     * 解析物流子類型
     *
     * @param string $type
     * @return string
     */
    protected function resolveLogisticsSubType(string $type): string
    {
        $type = strtolower($type);
        
        return self::LOGISTICS_SUB_TYPES[$type] ?? $type;
    }

    /**
     * 產生物流交易編號
     *
     * @param string|null $prefix
     * @return string
     */
    public function generateMerchantTradeNo(?string $prefix = 'LG'): string
    {
        return $prefix . now()->format('YmdHis') . str_pad(random_int(0, 9999), 4, '0', STR_PAD_LEFT);
    }
}
