<?php

namespace Modules\HistoryOrder\Backend\Controller;

use App\Http\Controllers\Controller;
use Modules\HistoryOrder\Backend\Service\HistoryOrderService;
use Modules\HistoryOrder\Backend\Model\HistoryOrder;
use Modules\HistoryOrder\Backend\Export\HistoryOrdersExport;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class HistoryOrderController extends Controller
{
    public function __construct(
        protected HistoryOrderService $service
    ) {}

    /**
     * 歷史訂單列表頁
     */
    public function index(Request $request)
    {
        $items = $this->service->getListPaginated($request);
        $seriesModelOptions = $this->service->getSeriesModelOptions();

        return Inertia::render('Admin/HistoryOrder/Index', [
            'items'              => $items,
            'seriesModelOptions' => $seriesModelOptions,
            'cabinSpecFields'    => HistoryOrder::getCabinSpecFields(),
            'entranceSpecFields' => HistoryOrder::getEntranceSpecFields(),
            'filters'            => [
                'date'           => $request->input('date', ''),
                'case_area'      => $request->input('case_area', ''),
                'series_model'   => $request->input('series_model', ''),
            ],
        ]);
    }

    /**
     * 檢視歷史訂單詳情
     */
    public function show($id)
    {
        $order = $this->service->getDetail((int) $id);

        if (!$order) {
            return redirect()->route('admin.history-order.index')
                             ->with('result', ['status' => false, 'msg' => '訂單不存在']);
        }

        return Inertia::render('Admin/HistoryOrder/Detail', [
            'order'              => $order,
            'cabinSpecFields'    => HistoryOrder::getCabinSpecFields(),
            'entranceSpecFields' => HistoryOrder::getEntranceSpecFields(),
        ]);
    }

    /**
     * 匯出 Excel
     */
    public function export(Request $request)
    {
        $items = $this->service->getFilteredList($request);
        $filename = '歷史訂單_' . now()->format('Ymd_His') . '.xlsx';

        return Excel::download(new HistoryOrdersExport($items), $filename);
    }

    /**
     * 匯出 PDF
     */
    public function exportPdf($id)
    {
        $order = $this->service->getDetail((int) $id);

        if (!$order) {
            return redirect()->route('admin.history-order.index')
                             ->with('result', ['status' => false, 'msg' => '訂單不存在']);
        }

        // 電梯渲染圖：直接給 mPDF 本機檔案路徑（讓 mPDF 自己讀檔），
        // 不用 base64 內嵌，避免大張圖把 HTML 撐破 pcre.backtrack_limit。
        $elevatorImagePath = '';
        if ($order->elevator_image) {
            $imagePath = public_path(ltrim($order->elevator_image, '/'));
            if (file_exists($imagePath)) {
                $elevatorImagePath = $imagePath;
            }
        }

        // 將 icon PNG 轉為 base64
        // 注意：cabin 和 entrance 有同名欄位(door_panel/floor/control_panel)，不能用 array_merge
        $iconBase64Map = [];
        $allIconPaths = array_unique(array_merge(
            array_column(array_values(HistoryOrder::getCabinSpecFields()), 'icon'),
            array_column(array_values(HistoryOrder::getEntranceSpecFields()), 'icon')
        ));
        foreach (array_filter($allIconPaths) as $iconPath) {
            $fullPath = public_path(ltrim($iconPath, '/'));
            if (file_exists($fullPath)) {
                $mime = mime_content_type($fullPath);
                $iconBase64Map[$iconPath] = "data:{$mime};base64," . base64_encode(file_get_contents($fullPath));
            }
        }

        $html = view('pdf.history-order', [
            'order'               => $order,
            'cabinSpecFields'     => HistoryOrder::getCabinSpecFields(),
            'entranceSpecFields'  => HistoryOrder::getEntranceSpecFields(),
            'elevatorImagePath'   => $elevatorImagePath,
            'iconBase64Map'       => $iconBase64Map,
        ])->render();

        // 移除 BOM 及無效 UTF-8 字元
        $html = preg_replace('/\xEF\xBB\xBF/', '', $html);
        $html = mb_convert_encoding($html, 'UTF-8', 'UTF-8');

        // 放寬 PCRE 限制，避免長 HTML 觸發 mPDF 的 backtrack_limit 例外（保險用）
        ini_set('pcre.backtrack_limit', '10000000');
        ini_set('pcre.recursion_limit', '10000000');

        $mpdf = new \Mpdf\Mpdf([
            'format'        => 'A4-L',
            'margin_left'   => 8,
            'margin_right'  => 8,
            'margin_top'    => 8,
            'margin_bottom' => 8,
            'mode'          => 'utf-8',
            'fontDir'       => [storage_path('fonts')],
            'fontdata'      => [
                'notosanstc' => ['R' => 'NotoSansTC.ttf'],
            ],
            'default_font'  => 'notosanstc',
        ]);

        $mpdf->WriteHTML($html);

        $filename = "訂單_{$order->order_name}.pdf";

        return response($mpdf->Output('', 'S'), 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . rawurlencode($filename) . '"',
        ]);
    }
}
