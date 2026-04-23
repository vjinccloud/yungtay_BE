<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RadioEpisodeRequest;
use App\Http\Resources\Admin\RadioEpisode\EditResource;
use App\Services\RadioEpisodeService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class RadioEpisodeController extends Controller
{
    protected $radioEpisodeService;

    public function __construct(RadioEpisodeService $radioEpisodeService)
    {
        $this->radioEpisodeService = $radioEpisodeService;
    }

    /**
     * 取得指定廣播的所有集數
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['search', 'radio_id', 'season']);
        $sortColumn = $request->input('sortColumn') ?? 'sort_order';
        $sortDirection = $request->input('sortDirection') ?? 'asc';
        $perPage = $request->input('length') ?? 10;

        $episodes = $this->radioEpisodeService->getDataTableData(
            $perPage,
            $sortColumn,
            $sortDirection,
            $filters
        );

        // 直接返回 Paginator JSON（DataTable AJAX 格式）
        return response()->json($episodes);
    }

    /**
     * 新增集數
     *
     * @param RadioEpisodeRequest $request
     * @return JsonResponse
     */
    public function store(RadioEpisodeRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['audio'] = $request->file('audio');

        $result = $this->radioEpisodeService->createEpisode($data['radio_id'] ?? null, $data);

        return response()->json($result, $result['status'] ? 200 : 422);
    }

    /**
     * 取得單筆集數資料
     *
     * @param int $episode 集數 ID
     * @return JsonResponse
     */
    public function show($episode): JsonResponse
    {
        $episodeModel = $this->radioEpisodeService->find($episode);

        if (!$episodeModel) {
            return response()->json([
                'status' => false,
                'msg' => '集數不存在'
            ], 404);
        }

        return (new EditResource($episodeModel))->response()->setStatusCode(200);
    }

    /**
     * 更新集數
     *
     * @param RadioEpisodeRequest $request
     * @param int $episode 集數 ID
     * @return JsonResponse
     */
    public function update(RadioEpisodeRequest $request, $episode): JsonResponse
    {
        $data = $request->validated();

        // 處理音檔
        if ($request->hasFile('audio')) {
            $data['audio'] = $request->file('audio');
        }

        $result = $this->radioEpisodeService->updateEpisode($episode, $data);

        return response()->json($result, $result['status'] ? 200 : 422);
    }

    /**
     * 刪除集數
     *
     * @param int $episode 集數 ID
     * @return JsonResponse
     */
    public function destroy($episode): JsonResponse
    {
        $result = $this->radioEpisodeService->deleteEpisode($episode);

        return response()->json($result, $result['status'] ? 200 : 422);
    }

    /**
     * 更新排序
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function sort(Request $request): JsonResponse
    {
        $request->validate([
            'radio_id' => 'nullable|integer',
            'season' => 'required|integer|min:1|max:7',
            'sort_data' => 'required|array',
            'sort_data.*.id' => 'required|integer|exists:radio_episodes,id',
            'sort_data.*.order' => 'required|integer|min:1',
        ]);

        $result = $this->radioEpisodeService->updateEpisodeSort(
            $request->input('radio_id'),
            $request->input('season'),
            $request->input('sort_data')
        );

        return response()->json($result, $result['status'] ? 200 : 422);
    }

    /**
     * 取得下一個集數編號
     *
     * @param Request $request
     * @param int|null $radio 從路由參數取得的 radio_id（可為 null）
     * @return JsonResponse
     */
    public function nextEpisodeNumber(Request $request, $radio = null): JsonResponse
    {
        $request->validate([
            'season' => 'required|integer|min:1|max:7',
        ]);

        // 優先使用路由參數，如果為 'null' 字串則轉為 null
        $radioId = ($radio === 'null' || $radio === null) ? null : (int) $radio;

        $nextNumber = $this->radioEpisodeService->getNextEpisodeNumber(
            $radioId,
            $request->input('season')
        );

        return response()->json([
            'status' => true,
            'next_episode_number' => $nextNumber
        ]);
    }
}
