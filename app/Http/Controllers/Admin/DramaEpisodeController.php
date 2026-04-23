<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Http\Requests\DramaEpisode\DramaEpisodeRequest;
use App\Services\DramaEpisodeService;
use App\Http\Resources\Admin\DramaEpisode\EditResource;
class DramaEpisodeController extends Controller
{
    public function __construct(protected DramaEpisodeService $dramaEpisodeService)
    {
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $filters = $request->only(['search', 'drama_id', 'season']);
        $sortColumn = $request->input('sortColumn') ?? 'seq';
        $sortDirection = $request->input('sortDirection') ?? 'asc';
        $perPage = $request->input('length') ?? 10;

        $episodes = $this->dramaEpisodeService->getDataTableData(
            $perPage,
            $sortColumn,
            $sortDirection,
            $filters
        );
        // 判斷請求類型
        if ($request->wantsJson() || $request->ajax() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
            // AJAX 請求：直接返回分頁數據的 JSON
            return response()->json($episodes);
        }
        
        return Inertia::render('Admin/Dramas/Components/DramaVideoUpload', [
            'episodes' => $episodes,
        ]);
    }
    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // 新增影片表單通常在 Modal 中，不需要單獨頁面
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(DramaEpisodeRequest $request)
    {
        $validated = $request->validated();
        $result = $this->dramaEpisodeService->save($validated);

        return redirect()
            ->back()
            ->with('result', $result);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $episode = $this->dramaEpisodeService->find($id);

        if (!$episode) {
            return response()->json([
                'error' => '找不到該影片'
            ], 404);
        }

        // 使用 Resource 回傳格式化資料
        return new EditResource($episode);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        // 編輯影片表單通常在 Modal 中，不需要單獨頁面
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(DramaEpisodeRequest $request, string $id)
    {
        $validated = $request->validated();
        $result = $this->dramaEpisodeService->save($validated, $id);

        return redirect()
            ->back()
            ->with('result', $result);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $result = $this->dramaEpisodeService->delete($id);

        return redirect()
            ->back()
            ->with('result', $result);
    }

    /**
     * 取得編輯資料 (AJAX)
     */
    public function getEditData(string $id)
    {
        $episode = $this->dramaEpisodeService->getEditData($id);

        if (!$episode) {
            return response()->json(['error' => '影片不存在'], 404);
        }

        return response()->json($episode);
    }

    /**
     * 重新排序影片
     */
    public function sort(Request $request)
    {
        // 調試：記錄收到的請求數據
        \Log::info('DramaEpisode Sort Request:', $request->all());
        
        // 驗證請求
        $validated = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer',
            'drama_id' => 'nullable|integer',
            'season' => 'nullable|integer',
        ]);

        \Log::info('DramaEpisode Sort Validated:', $validated);

        try {
            $result = $this->dramaEpisodeService->sortEpisodes(
                $validated['ids'],
                $validated['drama_id'] ?? null,
                $validated['season'] ?? null
            );
            
            \Log::info('DramaEpisode Sort Result:', $result);
            
            return redirect()->back()->with('result', $result);
        } catch (\Exception $e) {
            \Log::error('DramaEpisode Sort Error:', ['message' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            
            return redirect()->back()->with('result', [
                'status' => false,
                'msg' => '排序失敗：' . $e->getMessage()
            ]);
        }
    }

    /**
     * 取得季數統計資料 (AJAX)
     */
    public function getSeasonStats(Request $request)
    {
        $validated = $request->validate([
            'drama_id' => 'required|integer',
            'season' => 'required|integer',
        ]);

        $stats = $this->dramaEpisodeService->getSeasonStats(
            $validated['drama_id'],
            $validated['season']
        );

        return response()->json($stats);
    }
}
