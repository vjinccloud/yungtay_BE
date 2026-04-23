<?php

namespace App\Services;

use App\Repositories\RadioEpisodeRepository;
use App\Repositories\RadioRepository;
use App\Services\EventService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class RadioEpisodeService extends BaseService
{
    protected $radioEpisodeRepository;

    public function __construct(RadioEpisodeRepository $radioEpisodeRepository)
    {
        parent::__construct($radioEpisodeRepository);
        $this->radioEpisodeRepository = $radioEpisodeRepository;
        $this->eventService = app(EventService::class);
    }

    /**
     * 取得模組名稱
     * @return string
     */
    protected function getModuleTitle()
    {
        return '廣播集數';
    }

    /**
     * DataTable 分頁查詢
     *
     * @param int $perPage 每頁筆數
     * @param string $sortColumn 排序欄位
     * @param string $sortDirection 排序方向
     * @param array $filters 篩選條件
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getDataTableData(
        $perPage = 10,
        $sortColumn = 'sort_order',
        $sortDirection = 'asc',
        $filters = []
    ) {
        return $this->radioEpisodeRepository->getDataTableData(
            $perPage,
            $sortColumn,
            $sortDirection,
            $filters
        );
    }

    /**
     * 取得指定季數的集數列表
     *
     * @param int $radioId 廣播 ID
     * @param int|null $season 季數
     * @return array
     */
    public function getEpisodesBySeason($radioId, $season = null)
    {
        $episodes = $this->radioEpisodeRepository->getEpisodesByRadioAndSeason($radioId, $season);

        return $episodes->map(function ($episode) {
            return $this->radioEpisodeRepository->formatForFrontend($episode);
        })->toArray();
    }

    /**
     * 新增集數
     *
     * @param int $radioId 廣播 ID
     * @param array $data 資料
     * @return array
     */
    public function createEpisode($radioId, array $data)
    {
        try {
            // 準備資料（Repository 層會處理檔案移動）
            $episodeData = [
                'radio_id' => $radioId,
                'season' => $data['season'],
                'episode_number' => $data['episode_number'],
                'duration_text' => $data['duration_text'] ?? null,
                'description' => $data['description'] ?? null,
                'audio_path' => $data['audio_path'] ?? null,  // 暫存路徑，Repository 會移動到正式區
                'duration' => $data['duration'] ?? null,
                'is_active' => $data['is_active'] ?? true,
            ];

            // 儲存（Repository 的 save 方法會自動處理檔案移動）
            $episode = $this->radioEpisodeRepository->save($episodeData);

            // 自動同步廣播季數：如果新增的集數季數 > 廣播的季數，自動更新廣播季數
            $this->syncRadioSeason($radioId, (int) $data['season']);

            // 載入廣播關聯並設定 event_title
            $episode->load('radio');
            $radioName = $episode->radio
                ? $episode->radio->getTranslation('title', 'zh_TW')
                : '';

            $episode->event_title = '新增廣播集數：'
                . ($radioName ? "《{$radioName}》" : '')
                . '第' . $episode->season . '季第' . $episode->episode_number . '集';

            // 觸發事件
            $this->eventService->fireDataCreated($episode);

            return $this->ReturnHandle(true, '新增成功', null, [
                'episode' => $this->radioEpisodeRepository->formatForFrontend($episode)
            ]);
        } catch (\Exception $e) {
            \Log::error('新增廣播集數失敗', [
                'radio_id' => $radioId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return $this->ReturnHandle(false, '新增失敗：' . $e->getMessage());
        }
    }

    /**
     * 更新集數
     *
     * @param int $id 集數 ID
     * @param array $data 資料
     * @return array
     */
    public function updateEpisode($id, array $data)
    {
        try {
            $episode = $this->radioEpisodeRepository->find($id);
            if (!$episode) {
                return $this->ReturnHandle(false, '集數不存在');
            }

            // 準備資料（Repository 層會處理檔案移動和舊檔刪除）
            $episodeData = [
                'radio_id' => $data['radio_id'] ?? $episode->radio_id,  // 允許更新 radio_id
                'season' => $data['season'] ?? $episode->season,
                'episode_number' => $data['episode_number'] ?? $episode->episode_number,
                'duration_text' => $data['duration_text'] ?? [
                    'zh_TW' => $episode->getTranslation('duration_text', 'zh_TW'),
                    'en' => $episode->getTranslation('duration_text', 'en'),
                ],
                'description' => $data['description'] ?? [
                    'zh_TW' => $episode->getTranslation('description', 'zh_TW'),
                    'en' => $episode->getTranslation('description', 'en'),
                ],
                'audio_path' => $data['audio_path'] ?? null,  // 暫存路徑，Repository 會移動到正式區
                'duration' => $data['duration'] ?? $episode->duration,
                'is_active' => $data['is_active'] ?? $episode->is_active,
            ];

            // 更新（Repository 的 save 方法會自動處理檔案移動和舊檔刪除）
            $updatedEpisode = $this->radioEpisodeRepository->save($episodeData, $id);

            // 載入廣播關聯並設定 event_title
            $updatedEpisode->load('radio');
            $radioName = $updatedEpisode->radio
                ? $updatedEpisode->radio->getTranslation('title', 'zh_TW')
                : '';

            $updatedEpisode->event_title = '更新廣播集數：'
                . ($radioName ? "《{$radioName}》" : '')
                . '第' . $updatedEpisode->season . '季第' . $updatedEpisode->episode_number . '集';

            // 觸發事件
            $this->eventService->fireDataUpdated($updatedEpisode);

            return $this->ReturnHandle(true, '更新成功', null, [
                'episode' => $this->radioEpisodeRepository->formatForFrontend($updatedEpisode)
            ]);
        } catch (\Exception $e) {
            \Log::error('更新廣播集數失敗', [
                'id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return $this->ReturnHandle(false, '更新失敗：' . $e->getMessage());
        }
    }

    /**
     * 刪除集數
     *
     * @param int $id 集數 ID
     * @return array
     */
    public function deleteEpisode($id)
    {
        try {
            return DB::transaction(function () use ($id) {
                $episode = $this->radioEpisodeRepository->find($id);
                if (!$episode) {
                    return $this->ReturnHandle(false, '集數不存在');
                }

                // 載入廣播關聯並設定 event_title（在刪除前）
                $episode->load('radio');
                $radioName = $episode->radio
                    ? $episode->radio->getTranslation('title', 'zh_TW')
                    : '';

                $episode->event_title = '刪除廣播集數：'
                    . ($radioName ? "《{$radioName}》" : '')
                    . '第' . $episode->season . '季第' . $episode->episode_number . '集';

                // 複製事件資料用於觸發事件
                $eventModel = clone $episode;

                // 刪除（Repository 會處理音檔刪除和重新排序）
                $this->radioEpisodeRepository->delete($id);

                // 觸發事件
                $this->eventService->fireDataDeleted($eventModel);

                return $this->ReturnHandle(true, '刪除成功');
            });
        } catch (\Exception $e) {
            \Log::error('刪除廣播集數失敗', [
                'id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return $this->ReturnHandle(false, '刪除失敗：' . $e->getMessage());
        }
    }

    /**
     * 更新集數排序
     *
     * @param int|null $radioId 廣播 ID（可為 null，表示暫存集數）
     * @param int $season 季數
     * @param array $sortData 排序資料
     * @return array
     */
    public function updateEpisodeSort($radioId, $season, array $sortData)
    {
        try {
            $this->radioEpisodeRepository->updateSortOrders($radioId, $season, $sortData);

            // 觸發排序事件
            if (!empty($sortData)) {
                $firstId = $sortData[0]['id'] ?? null;
                if ($firstId) {
                    $episode = $this->radioEpisodeRepository->find($firstId);
                    if ($episode) {
                        $ids = array_column($sortData, 'id');
                        $this->eventService->fireDataSort($episode, $ids, '廣播集數');
                    }
                }
            }

            return $this->ReturnHandle(true, '排序更新成功');
        } catch (\Exception $e) {
            \Log::error('更新廣播集數排序失敗', [
                'radio_id' => $radioId,
                'season' => $season,
                'error' => $e->getMessage()
            ]);
            return $this->ReturnHandle(false, '排序更新失敗：' . $e->getMessage());
        }
    }

    /**
     * 處理音檔上傳
     *
     * @param \Illuminate\Http\UploadedFile $file 上傳的檔案
     * @param int $radioId 廣播 ID
     * @param int $season 季數
     * @param int $episodeNumber 集數編號
     * @return string 儲存路徑
     */
    public function handleAudioUpload($file, $radioId, $season, $episodeNumber)
    {
        // 建立儲存路徑
        $directory = "radios/{$radioId}/season_{$season}";

        // 確保目錄存在
        Storage::disk('public')->makeDirectory($directory);

        // 生成檔案名稱（使用隨機字串避免覆蓋）
        $extension = $file->getClientOriginalExtension();
        $filename = "episode_{$episodeNumber}_" . Str::random(8) . '.' . $extension;

        // 儲存檔案
        $path = $file->storeAs($directory, $filename, 'public');

        return $path;
    }

    /**
     * 取得指定廣播的所有季數
     *
     * @param int $radioId 廣播 ID
     * @return array
     */
    public function getSeasons($radioId)
    {
        return $this->radioEpisodeRepository->getSeasons($radioId);
    }

    /**
     * 取得下一個集數編號
     *
     * @param int $radioId 廣播 ID
     * @param int $season 季數
     * @return int
     */
    public function getNextEpisodeNumber($radioId, $season)
    {
        return $this->radioEpisodeRepository->getMaxEpisodeNumber($radioId, $season) + 1;
    }

    /**
     * 同步廣播季數
     * 當集數的季數 > 廣播的季數時，自動更新廣播季數
     *
     * @param int|null $radioId 廣播 ID（null 表示暫存集數，不需要同步）
     * @param int $episodeSeason 集數的季數
     * @return void
     */
    protected function syncRadioSeason($radioId, int $episodeSeason): void
    {
        // 暫存集數（radioId 為 null）不需要同步
        if (is_null($radioId)) {
            return;
        }

        $radioRepo = app(RadioRepository::class);
        $radio = $radioRepo->find($radioId);

        if (!$radio) {
            return;
        }

        // 如果集數的季數 > 廣播的季數，自動更新廣播季數
        $currentSeason = (int) ($radio->season ?? 1);
        if ($episodeSeason > $currentSeason) {
            $radio->update(['season' => $episodeSeason]);

            \Log::info('自動同步廣播季數', [
                'radio_id' => $radioId,
                'old_season' => $currentSeason,
                'new_season' => $episodeSeason
            ]);
        }
    }

}
