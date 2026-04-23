<?php

namespace Modules\OrderManagement\Backend\Controller;

use App\Http\Controllers\Controller;
use Modules\OrderManagement\Backend\Service\OrderService;
use Modules\OrderManagement\Backend\Model\Order;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

/**
 * 前台訂單 API Controller
 */
class OrderApiController extends Controller
{
    public function __construct(
        protected OrderService $orderService
    ) {}

    /**
     * 取得商品列表（供前台下單頁使用）
     *
     * GET /api/v1/orders/products
     */
    public function products(): JsonResponse
    {
        $products = \Modules\ProductListing\Model\Product::with(['mainImage', 'skus'])
            ->active()
            ->ordered()
            ->get()
            ->map(function ($p) {
                $name = is_array($p->name)
                    ? ($p->name['zh_TW'] ?? reset($p->name))
                    : $p->name;

                return [
                    'id'         => $p->id,
                    'name'       => $name,
                    'price'      => (float) $p->price,
                    'image'      => $p->mainImage?->image_path,
                    'skus'       => $p->skus->where('status', true)->map(fn ($sku) => [
                        'id'                => $sku->id,
                        'combination_label' => $sku->combination_label,
                        'sku'               => $sku->sku,
                        'price'             => (float) $sku->price,
                        'stock'             => $sku->stock,
                    ])->values(),
                ];
            });

        return response()->json([
            'success' => true,
            'data'    => $products,
        ]);
    }

    /**
     * 建立訂單
     *
     * POST /api/v1/orders
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            // 買家資訊
            'buyer_name'         => 'required|string|max:50',
            'buyer_phone'        => 'required|string|max:20',
            'buyer_email'        => 'nullable|email|max:100',
            'buyer_note'         => 'nullable|string|max:500',

            // 付款 / 物流
            'payment_method'     => 'required|string|in:credit_card,atm,cvs,cod',
            'shipping_method'    => 'required|string|in:cvs_711,cvs_family,cvs_hilife,home',

            // 收件人
            'receiver_name'      => 'required|string|max:50',
            'receiver_phone'     => 'required|string|max:20',
            'receiver_address'   => 'nullable|string|max:255',
            'receiver_store_id'  => 'nullable|string|max:20',
            'receiver_store_name'=> 'nullable|string|max:50',

            // 商品
            'items'                    => 'required|array|min:1',
            'items.*.product_id'       => 'required|integer|exists:products,id',
            'items.*.product_sku_id'   => 'nullable|integer|exists:product_skus,id',
            'items.*.quantity'         => 'required|integer|min:1',

            // 發票（選填）
            'invoice_carrier_type'     => 'nullable|integer|in:1,2,3,4',
            'invoice_carrier_num'      => 'nullable|string|max:64',
            'invoice_company_name'     => 'nullable|string|max:100',
            'invoice_tax_id'           => 'nullable|string|size:8',
            'invoice_love_code'        => 'nullable|string|max:10',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => '驗證失敗',
                'errors'  => $validator->errors(),
            ], 422);
        }

        // 宅配必須有地址
        $data = $validator->validated();
        if (in_array($data['shipping_method'], ['home']) && empty($data['receiver_address'])) {
            return response()->json([
                'success' => false,
                'message' => '宅配到府須填寫收件地址',
            ], 422);
        }

        // 超取必須有門市
        if (in_array($data['shipping_method'], ['cvs_711', 'cvs_family', 'cvs_hilife']) && empty($data['receiver_store_id'])) {
            return response()->json([
                'success' => false,
                'message' => '超商取貨須選擇門市',
            ], 422);
        }

        try {
            $result = $this->orderService->createOrder($data);

            $response = [
                'success'  => true,
                'message'  => '訂單建立成功',
                'order_no' => $result['order']->order_no,
                'order_id' => $result['order']->id,
                'total_amount' => $result['order']->total_amount,
            ];

            // 如果需要付款（非 COD），附帶付款資訊
            if ($result['payment'] && $result['payment']['success']) {
                $response['payment'] = [
                    'merchant_trade_no' => $result['payment']['merchant_trade_no'],
                    'token'             => $result['payment']['response']['Data']['Token'] ?? null,
                    'token_expire_date' => $result['payment']['response']['Data']['TokenExpireDate'] ?? null,
                ];
            }

            return response()->json($response, 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * 查詢訂單（前台用，需訂單編號 + 手機）
     *
     * GET /api/v1/orders/query?order_no=xxx&phone=xxx
     */
    public function query(Request $request): JsonResponse
    {
        $request->validate([
            'order_no' => 'required|string',
            'phone'    => 'required|string',
        ]);

        $order = $this->orderService->queryOrderForFrontend(
            $request->input('order_no'),
            $request->input('phone')
        );

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => '查無訂單，請確認訂單編號與手機號碼',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => $order,
        ]);
    }

    /**
     * 取得付款方式 / 物流方式 / 狀態等選項
     *
     * GET /api/v1/orders/options
     */
    public function options(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => [
                'payment_methods'  => Order::getPaymentMethodOptions(),
                'shipping_methods' => Order::getShippingMethodOptions(),
                'statuses'         => Order::getStatusOptions(),
            ],
        ]);
    }
}
