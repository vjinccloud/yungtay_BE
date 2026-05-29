<?php

namespace Modules\HistoryOrder\Backend\Controller;

use App\Http\Controllers\Controller;
use Modules\HistoryOrder\Backend\Model\HistoryOrder;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * 歷史訂單 API Controller
 */
class HistoryOrderApiController extends Controller
{
    /**
     * 新增歷史訂單
     *
     * POST /api/v1/history-orders
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'order_name'             => 'required|string|max:100',
            'customer_name'          => 'required|string|max:50',
            'contact_phone'          => 'nullable|string|max:30',
            'contact_email'          => 'nullable|email|max:100',
            'case_area'              => 'nullable|string|max:100',
            'elevator_count'         => 'nullable|integer|min:0',
            'elevator_spec'          => 'nullable|string|max:100',
            'series_model'           => 'nullable|string|max:50',
            'note'                   => 'nullable|string|max:1000',
            'elevator_image'         => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
            'cabin_specs'            => 'nullable|array',
            'cabin_specs.ceiling'    => 'nullable',
            'cabin_specs.door_panel' => 'nullable',
            'cabin_specs.side_panel_left'  => 'nullable',
            'cabin_specs.side_panel_right' => 'nullable',
            'cabin_specs.side_panel_back'  => 'nullable',
            'cabin_specs.side_panel_front' => 'nullable',
            'cabin_specs.floor'      => 'nullable',
            'cabin_specs.control_panel' => 'nullable',
            'cabin_specs.handrail'   => 'nullable',
            'cabin_specs.trim'       => 'nullable',
            'entrance_specs'              => 'nullable|array',
            'entrance_specs.door_panel'   => 'nullable',
            'entrance_specs.door_frame'   => 'nullable',
            'entrance_specs.lantern'      => 'nullable',
            'entrance_specs.control_panel' => 'nullable',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();

        // 處理圖片上傳
        if ($request->hasFile('elevator_image')) {
            $file = $request->file('elevator_image');
            $filename = 'elevator_' . Str::uuid() . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('history-orders', $filename, 'uploads');
            $data['elevator_image'] = '/uploads/' . $path;
        }

        $order = HistoryOrder::create($data);

        return response()->json([
            'success' => true,
            'data'    => $order,
            'message' => '歷史訂單已建立',
        ], 201);
    }

    /**
     * 取得假資料（測試用）
     *
     * GET /api/v1/history-orders/fake
     */
    public function fake(): JsonResponse
    {
        $fakeOrders = [];

        $seriesModels  = ['EAS', 'HIT', 'EAS-II', 'HIT-V'];
        $ceilings      = ['CH5', 'CH3', 'CH7', 'CH8'];
        $doorPanels    = ['髮紋不銹鋼', '鏡面不銹鋼', '烤漆鋼板', '彩繪鋼板'];
        $sidePanels    = ['不銹鋼板-鏡面不銹鋼', '彩繪鋼板', '髮紋不銹鋼'];
        $floors        = ['8TB', 'PVC', '花崗石', '大理石'];
        $controlPanels = ['BL-C2', 'BL-C3', 'BL-C5'];
        $handrails     = ['NR-108', 'NR-106', 'NR-200'];
        $trims         = ['鏡面不銹鋼', '髮紋不銹鋼', '無'];
        $doorFrames    = ["窄型門框\n鏡面不銹鋼", "標準門框\n髮紋不銹鋼"];

        $customers = [
            ['name' => 'xx股份有限公司',  'area' => '台北市大安區'],
            ['name' => '永信建設',         'area' => '台北市信義區'],
            ['name' => '遠雄建設',         'area' => '新北市汐止區'],
            ['name' => '國泰建設',         'area' => '台中市西屯區'],
            ['name' => '興富發建設',       'area' => '高雄市前鎮區'],
            ['name' => '華固建設',         'area' => '台北市中山區'],
            ['name' => '長虹建設',         'area' => '桃園市中壢區'],
            ['name' => '太子建設',         'area' => '台南市安平區'],
            ['name' => '潤泰建設',         'area' => '台北市大安區'],
            ['name' => '寶佳機構',         'area' => '新北市新莊區'],
        ];

        $elevatorSpecs = [
            '客梯 800kg / 11 人乘',
            '客梯 1000kg / 13 人乘',
            '客貨梯 1600kg',
            '客梯 600kg / 8 人乘',
            '無障礙客梯 750kg',
        ];

        for ($i = 0; $i < 10; $i++) {
            $customer = $customers[$i];
            $series   = $seriesModels[array_rand($seriesModels)];

            $baseSide = $sidePanels[array_rand($sidePanels)];

            $fakeOrders[] = [
                'order_name'             => "日立永大_{$customer['name']}",
                'customer_name'          => $customer['name'],
                'contact_phone'          => '0900-' . str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT),
                'contact_email'          => 'contact' . ($i + 1) . '@example.com',
                'case_area'              => $customer['area'],
                'elevator_count'         => random_int(1, 6),
                'elevator_spec'          => $elevatorSpecs[array_rand($elevatorSpecs)],
                'series_model'           => $series,
                'elevator_image'         => null,
                'cabin_specs' => [
                    'ceiling'           => $ceilings[array_rand($ceilings)] . "\n髮紋不銹鋼\n燈光：黃",
                    'door_panel'        => $doorPanels[array_rand($doorPanels)],
                    'side_panel_left'   => "{$baseSide}　左側板",
                    'side_panel_right'  => "{$baseSide}　右側板",
                    'side_panel_back'   => "{$baseSide}　後側板",
                    'side_panel_front'  => "{$baseSide}　前側板",
                    'floor'             => $floors[array_rand($floors)],
                    'control_panel'     => $controlPanels[array_rand($controlPanels)] . "　車廂操作盤\n無　無障礙操作盤",
                    'handrail'          => $handrails[array_rand($handrails)],
                    'trim'              => $trims[array_rand($trims)],
                ],
                'entrance_specs' => [
                    'door_panel'    => 'NR-108',
                    'door_frame'    => $doorFrames[array_rand($doorFrames)],
                    'lantern'       => '無',
                    'control_panel' => "BL-C2　乘場操作盤\nHF-LM5(LED)　乘場指示器",
                ],
            ];
        }

        return response()->json([
            'success' => true,
            'data'    => $fakeOrders,
            'message' => '已產生 10 筆假資料',
        ]);
    }

    /**
     * 用假資料批次寫入資料庫（先清空再寫入）
     *
     * POST /api/v1/history-orders/seed-fake
     */
    public function seedFake(): JsonResponse
    {
        HistoryOrder::truncate();

        $fakeResponse = $this->fake();
        $fakeData = json_decode($fakeResponse->getContent(), true)['data'];

        $created = [];
        foreach ($fakeData as $data) {
            $created[] = HistoryOrder::create($data);
        }

        return response()->json([
            'success' => true,
            'count'   => count($created),
            'message' => '已清空並重新寫入 ' . count($created) . ' 筆假資料',
        ], 201);
    }
}
