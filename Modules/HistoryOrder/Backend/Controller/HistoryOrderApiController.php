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
            'project_name'           => 'nullable|string|max:100',
            'construction_location'  => 'nullable|string|max:255',
            'customer_contact_name'  => 'nullable|string|max:50',
            'customer_contact_email' => 'nullable|email|max:100',
            'series_model'           => 'nullable|string|max:50',
            'sales_name'             => 'required|string|max:50',
            'sales_email'            => 'nullable|email|max:100',
            'sales_phone'            => 'nullable|string|max:30',
            'note'                   => 'nullable|string|max:1000',
            'elevator_image'         => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
            'cabin_specs'            => 'nullable|array',
            'cabin_specs.ceiling'    => 'nullable',
            'cabin_specs.door_panel' => 'nullable',
            'cabin_specs.side_panel' => 'nullable',
            'cabin_specs.floor'      => 'nullable',
            'cabin_specs.control_panel' => 'nullable',
            'cabin_specs.handrail'   => 'nullable',
            'cabin_specs.trim'       => 'nullable',
            'entrance_specs'              => 'nullable|array',
            'entrance_specs.door_panel'   => 'nullable',
            'entrance_specs.door_frame'   => 'nullable',
            'entrance_specs.door_column'  => 'nullable',
            'entrance_specs.floor'        => 'nullable',
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
        $doorColumns   = ['硬質鋁合金', '不銹鋼'];

        $customers = [
            ['name' => 'xx股份有限公司',  'project' => '世界明珠',    'location' => '台北市大安區xxcccc123號'],
            ['name' => '永信建設',         'project' => '信義豪邸',    'location' => '台北市信義區松仁路100號'],
            ['name' => '遠雄建設',         'project' => '遠雄富都',    'location' => '新北市汐止區新台五路一段88號'],
            ['name' => '國泰建設',         'project' => '國泰禾',      'location' => '台中市西屯區市政北七路88號'],
            ['name' => '興富發建設',       'project' => '潤隆新世紀',  'location' => '高雄市前鎮區成功二路88號'],
            ['name' => '華固建設',         'project' => '華固天鑄',    'location' => '台北市中山區民生東路二段50號'],
            ['name' => '長虹建設',         'project' => '長虹天際',    'location' => '桃園市中壢區中北路200號'],
            ['name' => '太子建設',         'project' => '太子花園',    'location' => '台南市安平區健康路三段55號'],
            ['name' => '潤泰建設',         'project' => '潤泰敦仁',    'location' => '台北市大安區敦化南路一段233號'],
            ['name' => '寶佳機構',         'project' => '寶佳天悅',    'location' => '新北市新莊區中正路777號'],
        ];

        $salesList = [
            ['name' => '王琳淋', 'email' => 'wang.ll@hitachi-elevator.com.tw',  'phone' => '02-2709-3355#561'],
            ['name' => '林東方', 'email' => 'linett@gmail.com',                   'phone' => '02-2709-3355#562'],
            ['name' => '陳美玲', 'email' => 'chen.ml@hitachi-elevator.com.tw',   'phone' => '02-2709-3355#563'],
            ['name' => '張志豪', 'email' => 'chang.zh@hitachi-elevator.com.tw',  'phone' => '03-3456-7890#101'],
            ['name' => '李佳韻', 'email' => 'lee.jy@hitachi-elevator.com.tw',    'phone' => '04-2345-6789#201'],
        ];

        for ($i = 0; $i < 10; $i++) {
            $customer = $customers[$i];
            $sales    = $salesList[array_rand($salesList)];
            $series   = $seriesModels[array_rand($seriesModels)];

            $sideLabels = [
                '前側板 (中間片)',
                '前側板 (兩側片)',
                '後側板 (中間片)',
                '後側板 (兩側片)',
            ];
            $sideValue = [];
            $baseSide = $sidePanels[array_rand($sidePanels)];
            foreach ($sideLabels as $label) {
                $sideValue[] = "{$baseSide}　{$label}";
            }

            $fakeOrders[] = [
                'order_name'             => "日立永大_{$customer['name']}",
                'customer_name'          => $customer['name'],
                'project_name'           => $customer['project'],
                'construction_location'  => $customer['location'],
                'customer_contact_name'  => '陳委克',
                'customer_contact_email' => 'mcchen@gmail.com',
                'series_model'           => $series,
                'sales_name'             => $sales['name'],
                'sales_email'            => $sales['email'],
                'sales_phone'            => $sales['phone'],
                'elevator_image'         => null,
                'cabin_specs' => [
                    'ceiling'       => $ceilings[array_rand($ceilings)] . "\n髮紋不銹鋼\n燈光：黃",
                    'door_panel'    => $doorPanels[array_rand($doorPanels)],
                    'side_panel'    => implode("\n", $sideValue),
                    'floor'         => $floors[array_rand($floors)],
                    'control_panel' => $controlPanels[array_rand($controlPanels)] . "　車廂操作盤\n無　無障礙操作盤",
                    'handrail'      => $handrails[array_rand($handrails)],
                    'trim'          => $trims[array_rand($trims)],
                ],
                'entrance_specs' => [
                    'door_panel'    => 'NR-108',
                    'door_frame'    => $doorFrames[array_rand($doorFrames)],
                    'door_column'   => $doorColumns[array_rand($doorColumns)],
                    'floor'         => '無',
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
