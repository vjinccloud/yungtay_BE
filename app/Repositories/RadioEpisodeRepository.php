<?php

namespace App\Repositories;

use App\Models\RadioEpisode;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class RadioEpisodeRepository extends BaseRepository
{
    public function __construct(RadioEpisode $radioEpisode)
    {
        parent::__construct($radioEpisode);
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
        $query = $this->model->newQuery()
            ->with(['radio:id,title', 'created_user:id,name', 'updated_user:id,name'])
            ->select([
                'id', 'radio_id', 'season', 'episode_number',
                'audio_path', 'duration', 'duration_text', 'description',
                'sort_order', 'is_active',
                'created_by', 'updated_by',
                'updated_at', 'created_at'
            ]);

        // —— radio_id 過濾 ——
        if (array_key_exists('radio_id', $filters)) {
            // PHP 的 null → 撈出 radio_id IS NULL
            if (is_null($filters['radio_id']) || $filters['radio_id'] === 'null') {
                $query->whereNull('radio_id');
            }
            // 其他非空字串或數字 → 一般比對
            elseif ($filters['radio_id'] !== '') {
                $query->where('radio_id', $filters['radio_id']);
            }
            // 空字串就略過
        }

        // —— season 過濾 ——
        if (array_key_exists('season', $filters) && $filters['season'] !== '') {
            $query->where('season', $filters['season']);
        }

        // —— 關鍵字搜尋 ——
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function($q) use ($search) {
                $q->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(description, '$.zh_TW')) LIKE ?", ["%{$search}%"])
                  ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(description, '$.en')) LIKE ?", ["%{$search}%"])
                  ->orWhere('episode_number', 'like', "%{$search}%");
            });
        }

        // —— 排序 ——
        $validSortColumns = ['sort_order', 'episode_number', 'season', 'updated_at', 'created_at'];
        if (in_array($sortColumn, $validSortColumns)) {
            $query->orderBy($sortColumn, $sortDirection);
        } else {
            $query->orderBy('season', 'asc')->orderBy('sort_order', 'asc');
        }

        return $query
            ->paginate($perPage)
            ->through(function ($episode) {
                return [
                    'id'                => $episode->id,
                    'radio_id'          => $episode->radio_id,
                    'radio_title'       => $episode->radio?->getTranslation('title', app()->getLocale()),
                    'season'            => $episode->season,
                    'episode_number'    => $episode->episode_number,
                    'episode_title'     => "第{$episode->episode_number}集",
                    'season_title'      => "第{$episode->season}季",
                    'duration_text_zh'  => $episode->getTranslation('duration_text', 'zh_TW'),
                    'duration_text_en'  => $episode->getTranslation('duration_text', 'en'),
                    'description_zh'    => $episode->getTranslation('description', 'zh_TW'),
                    'description_en'    => $episode->getTranslation('description', 'en'),
                    'audio_path'        => $episode->audio_path,
                    'audio_url'         => $episode->audio_path ? asset('storage/' . $episode->audio_path) : null,
                    'duration'          => $episode->duration,
                    'sort_order'        => $episode->sort_order,
                    'is_active'         => $episode->is_active,
                    'created_by_name'   => $episode->created_user?->name,
                    'updated_by_name'   => $episode->updated_user?->name,
                    'updated_at'        => $episode->updated_at->format('Y-m-d H:i:s'),
                    'created_at'        => $episode->created_at->format('Y-m-d H:i:s'),
                ];
            });
    }

    /**
     * 取得指定廣播和季數的所有集數
     *
     * @param int $radioId 廣播 ID
     * @param int|null $season 季數
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getEpisodesByRadioAndSeason($radioId, $season = null)
    {
        $query = $this->model->newQuery()
            ->where('radio_id', $radioId)
            ->with(['created_user:id,name', 'updated_user:id,name']);

        if ($season !== null) {
            $query->where('season', $season);
        }

        return $query->orderBy('sort_order', 'asc')->get();
    }

    /**
     * 取得指定廣播和季數的最大集數編號
     *
     * @param int $radioId 廣播 ID
     * @param int $season 季數
     * @return int
     */
    public function getMaxEpisodeNumber($radioId, $season)
    {
        $query = $this->model->newQuery();

        // 處理 null radio_id（暫存集數）
        if (is_null($radioId)) {
            $query->whereNull('radio_id');
        } else {
            $query->where('radio_id', $radioId);
        }

        return (int) $query->where('season', $season)
            ->max('episode_number') ?? 0;
    }

    /**
     * 取得指定廣播和季數的最大排序值
     *
     * @param int $radioId 廣播 ID
     * @param int $season 季數
     * @return int
     */
    public function getMaxSortOrder($radioId, $season)
    {
        $query = $this->model->newQuery();

        // 處理 null radio_id（暫存集數）
        if (is_null($radioId)) {
            $query->whereNull('radio_id');
        } else {
            $query->where('radio_id', $radioId);
        }

        return (int) $query->where('season', $season)
            ->max('sort_order') ?? 0;
    }

    /**
     * 儲存廣播集數
     *
     * @param array $data 資料
     * @param int|null $id 若為 null 則新增，否則更新
     * @return RadioEpisode
     */
    public function save(array $data, $id = null): RadioEpisode
    {
        return DB::transaction(function () use ($data, $id) {
            // 1. 先取出舊資料（若是編輯）
            $old = $id ? $this->model->find($id) : null;
            $oldPath = $old->audio_path ?? null;

            // 2. 處理音檔搬移（從暫存區到正式區）
            $newPath = null;
            if (!empty($data['audio_path'])) {
                $newPath = $this->moveUploadedFile($data['audio_path']);
            }

            // 3. 刪除舊音檔（若有新音檔且舊路徑不同）
            if ($old && $oldPath && $newPath && $oldPath !== $newPath) {
                if (Storage::disk('public')->exists($oldPath)) {
                    Storage::disk('public')->delete($oldPath);
                }
            }

            // 4. 準備 payload
            $payload = [
                'radio_id' => $data['radio_id'],
                'season' => $data['season'],
                'episode_number' => $data['episode_number'],
                'duration_text' => [
                    'zh_TW' => $data['duration_text']['zh_TW'] ?? '',
                    'en' => $data['duration_text']['en'] ?? '',
                ],
                'description' => [
                    'zh_TW' => $data['description']['zh_TW'] ?? '',
                    'en' => $data['description']['en'] ?? '',
                ],
                'audio_path' => $newPath ?? $oldPath,  // 使用新路徑或保留舊路徑
                'duration' => $data['duration'] ?? null,
                'is_active' => $data['is_active'] ?? true,
            ];

            // 5. 只在新增時設定排序值
            if (!$id) {
                $payload['sort_order'] = $this->getMaxSortOrder($data['radio_id'], $data['season']) + 1;
            }

            return parent::save($payload, $id);
        });
    }

    /**
     * 移動上傳檔案從暫存到正式目錄
     *
     * @param string $tempPath 暫存路徑
     * @return string 正式路徑
     */
    protected function moveUploadedFile($tempPath)
    {
        if (!Storage::disk('public')->exists($tempPath)) {
            return $tempPath; // 檔案不存在，返回原路徑
        }

        // 生成正式路徑
        $filename = basename($tempPath);
        $finalPath = 'radios/episodes/' . $filename;

        // 確保目錄存在
        Storage::disk('public')->makeDirectory('radios/episodes');

        // 移動檔案
        Storage::disk('public')->move($tempPath, $finalPath);

        return $finalPath;
    }

    /**
     * 更新排序
     *
     * @param int $radioId 廣播 ID
     * @param int $season 季數
     * @param array $sortData 排序資料 [['id' => 1, 'order' => 1], ...]
     * @return void
     */
    public function updateSortOrders($radioId, $season, array $sortData)
    {
        DB::beginTransaction();
        try {
            foreach ($sortData as $item) {
                $this->model->newQuery()
                    ->where('id', $item['id'])
                    ->where('radio_id', $radioId)
                    ->where('season', $season)
                    ->update([
                        'sort_order' => $item['order'],
                        'episode_number' => $item['order']  // 同時更新集數編號，讓排序順序 = 集數編號
                    ]);
            }
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * 刪除集數（覆寫父類別的 delete 方法）
     *
     * @param int $id
     * @return bool
     */
    public function delete($id)
    {
        $episode = $this->find($id);
        if (!$episode) {
            return false;
        }

        // 刪除音檔
        if ($episode->audio_path && Storage::disk('public')->exists($episode->audio_path)) {
            Storage::disk('public')->delete($episode->audio_path);
        }

        // 刪除資料庫記錄
        $result = $episode->delete();

        // 重新整理同季數的排序
        if ($result) {
            $this->reorderEpisodes($episode->radio_id, $episode->season);
        }

        return $result;
    }

    /**
     * 重新排序指定廣播和季數的集數
     *
     * @param int|null $radioId 廣播 ID
     * @param int $season 季數
     * @return void
     */
    public function reorderEpisodes($radioId, $season)
    {
        $query = $this->model->newQuery();

        // 處理 null radio_id（暫存集數）
        if (is_null($radioId)) {
            $query->whereNull('radio_id');
        } else {
            $query->where('radio_id', $radioId);
        }

        $episodes = $query
            ->where('season', $season)
            ->orderBy('sort_order', 'asc')
            ->get(['id']);

        foreach ($episodes as $index => $episode) {
            $this->model->newQuery()
                ->where('id', $episode->id)
                ->update([
                    'episode_number' => $index + 1,  // 同時更新集數編號
                    'sort_order' => $index + 1
                ]);
        }
    }

    /**
     * 取得指定廣播的所有季數
     *
     * @param int $radioId 廣播 ID
     * @return array
     */
    public function getSeasons($radioId)
    {
        return $this->model->newQuery()
            ->where('radio_id', $radioId)
            ->distinct()
            ->pluck('season')
            ->sort()
            ->values()
            ->toArray();
    }

    /**
     * 格式化集數資料給前端使用
     *
     * @param RadioEpisode $episode
     * @return array
     */
    public function formatForFrontend(RadioEpisode $episode)
    {
        return [
            'id' => $episode->id,
            'radio_id' => $episode->radio_id,
            'season' => $episode->season,
            'episode_number' => $episode->episode_number,
            'audio_path' => $episode->audio_path,
            'audio_url' => $episode->audio_path ? asset('storage/' . $episode->audio_path) : null,
            'duration' => $episode->duration,
            'duration_text_zh_tw' => $episode->getTranslation('duration_text', 'zh_TW'),
            'duration_text_en' => $episode->getTranslation('duration_text', 'en'),
            'description_zh_tw' => $episode->getTranslation('description', 'zh_TW'),
            'description_en' => $episode->getTranslation('description', 'en'),
            'sort_order' => $episode->sort_order,
            'is_active' => $episode->is_active,
            'created_by_name' => $episode->created_user?->name,
            'updated_by_name' => $episode->updated_user?->name,
            'created_at' => $episode->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $episode->updated_at->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * 取得有集數資料的最大季數
     * - radioId 為 null：檢查當前管理員的暫存集數
     * - radioId 不為 null：檢查指定廣播的集數
     *
     * @param int|null $radioId
     * @return int 有集數的最大季數，若無集數則返回 0
     */
    public function getMaxSeasonWithEpisodes($radioId = null)
    {
        $query = $this->model->newQuery();

        if (is_null($radioId)) {
            // 檢查暫存集數（當前管理員建立的）
            $adminId = auth('admin')->id();
            $query->whereNull('radio_id')
                  ->where('created_by', $adminId);
        } else {
            // 檢查指定廣播的集數
            $query->where('radio_id', $radioId);
        }

        return (int) $query->max('season') ?? 0;
    }

    /**
     * 檢查是否有集數資料
     * - radioId 為 null：檢查當前管理員的暫存集數
     * - radioId 不為 null：檢查指定廣播的集數
     *
     * @param int|null $radioId
     * @return bool
     */
    public function hasEpisodes($radioId = null)
    {
        $query = $this->model->newQuery();

        if (is_null($radioId)) {
            // 檢查暫存集數（當前管理員建立的）
            $adminId = auth('admin')->id();
            $query->whereNull('radio_id')
                  ->where('created_by', $adminId);
        } else {
            // 檢查指定廣播的集數
            $query->where('radio_id', $radioId);
        }

        return $query->exists();
    }

    /**
     * 更新暫存集數的 radio_id
     * 當新增廣播成功後，將暫存的集數（radio_id = null）更新為實際的 radio_id
     *
     * @param int $radioId 新建立的廣播 ID
     * @return int 更新的集數數量
     */
    public function updateTempEpisodesRadioId($radioId)
    {
        $adminId = auth('admin')->id();

        return $this->model
            ->whereNull('radio_id')
            ->where('created_by', $adminId)
            ->update(['radio_id' => $radioId]);
    }
}
