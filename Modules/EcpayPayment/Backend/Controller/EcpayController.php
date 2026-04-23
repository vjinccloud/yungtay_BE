<?php

namespace Modules\EcpayPayment\Backend\Controller;

use App\Http\Controllers\Controller;
use Modules\EcpayPayment\Backend\Service\EcpayPaymentService;
use Modules\EcpayPayment\Backend\Service\EcpayLogisticsService;
use Modules\EcpayPayment\Backend\Service\EcpayInvoiceService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * 綠界金流 API Controller
 */
class EcpayController extends Controller
{
    public function __construct(
        protected EcpayPaymentService $paymentService,
        protected EcpayLogisticsService $logisticsService,
        protected EcpayInvoiceService $invoiceService
    ) {}

    /**
     * 建立付款 Token
     * 
     * POST /api/ecpay/payment/create-token
     */
    public function createPaymentToken(Request $request): JsonResponse
    {
        $validated = $request->validate([
            // 付款資訊
            'total_amount'     => 'required|numeric|min:1',
            'member_id'        => 'nullable|string',
            'phone'            => 'nullable|string',
            'email'            => 'nullable|email',
            'trade_desc'       => 'nullable|string|max:200',
            'items'            => 'nullable|array',
            'items.*.name'     => 'nullable|string',
            'items.*.quantity' => 'nullable|integer|min:1',
            'items.*.price'    => 'nullable|numeric|min:0',
            'items.*.unit'     => 'nullable|string',
            'return_url'       => 'nullable|url',
            'order_result_url' => 'nullable|url',
            
            // 發票資訊
            // invoice_carrier_type: 1=會員載具, 2=手機載具, 3=統編, 4=捐贈
            'invoice_carrier_type' => 'required|integer|in:1,2,3,4',
            'invoice_carrier_num'  => 'nullable|string|max:64',          // 手機載具號碼
            'invoice_company_name' => 'nullable|string|max:100',         // 發票抬頭（公司名稱）
            'invoice_tax_id'       => 'nullable|string|size:8',          // 統一編號
            'invoice_love_code'    => 'nullable|string|max:10',          // 愛心碼
            'invoice_buyer_name'   => 'nullable|string|max:100',         // 買受人姓名
            'invoice_print_address'=> 'nullable|string|max:200',         // 發票寄送地址（紙本用）
        ]);

        // phone 和 email 至少要有一個
        if (empty($validated['phone']) ) {
            return response()->json([
                'success' => false,
                'message' => 'phone為必填',
            ], 422);
        }

         if (empty($validated['email'])) {
            return response()->json([
                'success' => false,
                'message' => 'email為必填',
            ], 422);
        }

        $carrierType = $validated['invoice_carrier_type'] ?? null;

        // 統編發票（3）必須填統編和抬頭
        if ($carrierType === 3) {
            if (empty($validated['invoice_tax_id'])) {
                return response()->json([
                    'success' => false,
                    'message' => '統編發票必須填寫統一編號',
                ], 422);
            }
            if (empty($validated['invoice_company_name'])) {
                return response()->json([
                    'success' => false,
                    'message' => '統編發票必須填寫發票抬頭',
                ], 422);
            }
        }

        // 捐贈發票（4）必須填愛心碼
        if ($carrierType === 4 && empty($validated['invoice_love_code'])) {
            return response()->json([
                'success' => false,
                'message' => '捐贈發票必須填寫愛心碼',
            ], 422);
        }

        // 手機載具（2）格式驗證
        if ($carrierType === 2) {
            $carrierNum = $validated['invoice_carrier_num'] ?? '';
            if (!preg_match('/^\/[0-9A-Z.+-]{7}$/', $carrierNum)) {
                return response()->json([
                    'success' => false,
                    'message' => '手機條碼格式錯誤，應為 / 開頭加 7 碼英數字',
                ], 422);
            }
        }

        $orderData = [
            'total_amount'     => $validated['total_amount'],
            'member_id'        => $validated['member_id'] ?? 'guest',
            'phone'            => $validated['phone'] ?? '',
            'email'            => $validated['email'] ?? '',
            'trade_desc'       => $validated['trade_desc'] ?? 'Order',
            'return_url'       => $validated['return_url'] ?? null,
            'order_result_url' => $validated['order_result_url'] ?? null,
            'need_invoice'     => $carrierType !== null,  // 有填載具類型就需要發票
        ];

        $items = $validated['items'] ?? [['name' => 'Product']];

        // 發票資料
        $invoiceData = [];
        if ($carrierType !== null) {
            $invoiceData = [
                'type'          => $carrierType === 3 ? 2 : 1,  // 3=統編→公司(2), 其他→個人(1)
                'carrier_type'  => $carrierType,
                'carrier_num'   => $validated['invoice_carrier_num'] ?? null,
                'company_name'  => $validated['invoice_company_name'] ?? null,
                'tax_id'        => $validated['invoice_tax_id'] ?? null,
                'donation'      => $carrierType === 4,  // 4=捐贈
                'love_code'     => $validated['invoice_love_code'] ?? null,
                'buyer_name'    => $validated['invoice_buyer_name'] ?? null,
                'buyer_email'   => $validated['email'] ?? null,
                'buyer_phone'   => $validated['phone'] ?? null,
                'print_address' => $validated['invoice_print_address'] ?? null,
            ];
        }

        $result = $this->paymentService->createToken($orderData, $items, $invoiceData);

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => '建立付款 Token 失敗',
                'error'   => $result['response']['Data']['RtnMsg'] ?? 'Unknown error',
            ], 400);
        }

        return response()->json([
            'success' => true,
            'data'    => [
                'merchant_trade_no' => $result['merchant_trade_no'],
                'token'             => $result['response']['Data']['Token'] ?? null,
                'token_expire_date' => $result['response']['Data']['TokenExpireDate'] ?? null,
                'invoice_id'        => $result['invoice_id'] ?? null,
            ],
        ]);
    }

    /**
     * 執行付款
     * 
     * POST /api/ecpay/payment/execute
     */
    public function executePayment(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'pay_token'         => 'required|string',
            'merchant_trade_no' => 'required|string',
        ]);

        $result = $this->paymentService->executePayment(
            $validated['pay_token'],
            $validated['merchant_trade_no']
        );

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => '執行付款失敗',
                'error'   => $result['response']['Data']['RtnMsg'] ?? 'Unknown error',
            ], 400);
        }

        return response()->json([
            'success' => true,
            'data'    => $result['response']['Data'] ?? [],
        ]);
    }

    /**
     * 付款通知回調 (ReturnURL)
     * 
     * POST /api/ecpay/payment/notify
     */
    public function paymentNotify(Request $request): string
    {
        $result = $this->paymentService->handleNotify($request->all());

        // 付款成功時更新訂單狀態
        if ($result['success'] && !empty($result['merchant_trade_no'])) {
            try {
                $orderService = app(\Modules\OrderManagement\Backend\Service\OrderService::class);
                $orderService->handlePaymentSuccess($result['merchant_trade_no'], $result['data'] ?? []);
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('訂單狀態更新失敗', [
                    'merchant_trade_no' => $result['merchant_trade_no'],
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // 綠界要求回傳 1|OK
        return '1|OK';
    }

    /**
     * 付款結果導向 (OrderResultURL)
     * 
     * POST /api/ecpay/payment/result
     */
    public function paymentResult(Request $request): JsonResponse
    {
        $result = $this->paymentService->handleResult($request->all());

        return response()->json([
            'success'           => true,
            'merchant_trade_no' => $result['merchant_trade_no'],
            'trade_status'      => $result['trade_status'],
            'data'              => $result['data'],
        ]);
    }

    /**
     * 查詢訂單付款狀態
     * 
     * GET /api/ecpay/payment/query/{merchantTradeNo}
     */
    public function queryPayment(string $merchantTradeNo): JsonResponse
    {
        $result = $this->paymentService->queryOrder($merchantTradeNo);

        return response()->json([
            'success'           => $result['success'],
            'merchant_trade_no' => $result['merchant_trade_no'],
            'trade_status'      => $result['trade_status'],
            'trade_status_text' => $result['trade_status'] == 1 ? '已付款' : '未付款',
            'trade_date'        => $result['trade_date'],
        ]);
    }

    /**
     * 產生門市地圖表單
     * 
     * POST /api/ecpay/logistics/map
     */
    public function generateMapForm(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'logistics_sub_type' => 'required|string|in:711,family,hilife,okmart,UNIMARTC2C,FAMIC2C,HILIFEC2C,OKMARTC2C',
            'merchant_trade_no'  => 'nullable|string',
            'server_reply_url'   => 'nullable|url',
        ]);

        $merchantTradeNo = $validated['merchant_trade_no'] 
            ?? $this->logisticsService->generateMerchantTradeNo();

        $form = $this->logisticsService->generateMapForm(
            $validated['logistics_sub_type'],
            $merchantTradeNo,
            $validated['server_reply_url'] ?? null
        );

        return response()->json([
            'success'           => true,
            'merchant_trade_no' => $merchantTradeNo,
            'form_html'         => $form,
        ]);
    }

    /**
     * 門市地圖選擇回調
     * 
     * POST /api/ecpay/logistics/map-callback
     */
    public function mapCallback(Request $request)
    {
        $result = $this->logisticsService->handleMapCallback($request->all());

        // 如果需要跳轉，可以在這裡處理
        // return redirect()->route('cart.step1')->with('store_data', $result);

        return response()->json($result);
    }

    /**
     * 建立物流訂單
     * 
     * POST /api/ecpay/logistics/create
     */
    public function createLogisticsOrder(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'order_number'   => 'required|string',
            'amount'         => 'required|numeric|min:1',
            'shipping_type'  => 'required|string|in:711,family,hilife,okmart',
            'payment_type'   => 'required|string|in:credit_card,cod',
            'goods_name'     => 'nullable|string|max:50',
            'sender_name'    => 'nullable|string|max:10',
            'sender_phone'   => 'nullable|string',
            'receiver_name'  => 'required|string|max:10',
            'receiver_phone' => 'required|string',
            'store_id'       => 'required|string',
        ]);

        $result = $this->logisticsService->createLogisticsOrder($validated);

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => '建立物流訂單失敗',
                'error'   => $result['rtn_msg'],
            ], 400);
        }

        return response()->json([
            'success'              => true,
            'all_pay_logistics_id' => $result['all_pay_logistics_id'],
            'cvs_payment_no'       => $result['cvs_payment_no'],
            'cvs_validation_no'    => $result['cvs_validation_no'],
        ]);
    }

    /**
     * 物流狀態回調
     * 
     * POST /api/ecpay/logistics/callback
     */
    public function logisticsCallback(Request $request): string
    {
        $result = $this->logisticsService->handleLogisticsCallback($request->all());

        // 這裡可以加入你的訂單物流狀態更新邏輯
        // 例如：$this->updateOrderShippingStatus($result);

        // 綠界要求回傳 1|OK
        return '1|OK';
    }

    /**
     * 產生列印托運單表單
     * 
     * POST /api/ecpay/logistics/print
     */
    public function printLogistics(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'logistics_ids'  => 'required|array',
            'payment_nos'    => 'required|array',
            'validation_nos' => 'nullable|array',
            'shipping_type'  => 'required|string|in:711,family,hilife,okmart',
        ]);

        $form = $this->logisticsService->generatePrintForm(
            $validated['logistics_ids'],
            $validated['payment_nos'],
            $validated['validation_nos'] ?? [],
            $validated['shipping_type']
        );

        return response()->json([
            'success'   => true,
            'form_html' => $form,
        ]);
    }

    // ==========================================
    // 發票相關 API
    // ==========================================

    /**
     * 驗證統一編號
     * 
     * POST /api/ecpay/invoice/verify-tax-id
     */
    public function verifyTaxId(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tax_id' => 'required|string|size:8',
        ]);

        $result = $this->invoiceService->verifyTaxId($validated['tax_id']);

        return response()->json([
            'success' => $result['success'],
            'valid'   => $result['valid'] ?? false,
            'data'    => $result['data'] ?? null,
            'message' => $result['rtn_msg'] ?? null,
        ]);
    }

    /**
     * 驗證手機條碼
     * 
     * POST /api/ecpay/invoice/verify-mobile-carrier
     */
    public function verifyMobileCarrier(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'carrier_num' => 'required|string|max:10',
        ]);

        // 基本格式檢查
        if (!preg_match('/^\/[0-9A-Z.+-]{7}$/', $validated['carrier_num'])) {
            return response()->json([
                'success' => true,
                'valid'   => false,
                'message' => '手機條碼格式錯誤，應為 / 開頭加 7 碼英數字',
            ]);
        }

        $result = $this->invoiceService->verifyMobileCarrier($validated['carrier_num']);

        return response()->json([
            'success' => $result['success'],
            'valid'   => $result['valid'] ?? false,
            'message' => $result['rtn_msg'] ?? null,
        ]);
    }

    /**
     * 驗證愛心碼
     * 
     * POST /api/ecpay/invoice/verify-love-code
     */
    public function verifyLoveCode(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'love_code' => 'required|string|min:3|max:7',
        ]);

        // 基本格式檢查：3-7 碼數字或英文
        if (!preg_match('/^[0-9A-Za-z]{3,7}$/', $validated['love_code'])) {
            return response()->json([
                'success' => true,
                'valid'   => false,
                'message' => '愛心碼格式錯誤，應為 3-7 碼數字或英文',
            ]);
        }

        $result = $this->invoiceService->verifyLoveCode($validated['love_code']);

        return response()->json([
            'success' => $result['success'],
            'valid'   => $result['valid'] ?? false,
            'message' => $result['rtn_msg'] ?? null,
        ]);
    }

    /**
     * 查詢發票狀態
     * 
     * GET /api/ecpay/invoice/query/{invoiceNo}
     */
    public function queryInvoice(string $invoiceNo): JsonResponse
    {
        $result = $this->invoiceService->queryInvoice($invoiceNo);

        return response()->json([
            'success'      => $result['success'],
            'invoice_no'   => $invoiceNo,
            'data'         => $result['data'] ?? null,
            'message'      => $result['rtn_msg'] ?? null,
        ]);
    }
}
