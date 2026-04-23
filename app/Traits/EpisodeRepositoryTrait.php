<?php

namespace App\Traits;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * 影片集數 Repository 共用邏輯
 * 用於 DramaEpisodeRepository 和 ProgramEpisodeRepository
 */
trait EpisodeRepositoryTrait
{
    /**
     * 取得父層欄位名稱 (drama_id 或 program_id)
     * @return string
     */
    abstract protected function getParentField(): string;

    /**
     * 取得父層關聯名稱 (drama 或 program)
     * @return string
     */
    abstract protected function getParentRelation(): string;

    /**
     * DataTable 分頁查詢（共用邏輯）
     */
    public function getDataTableData(
        $perPage = 15,
        $sortColumn = 'seq',
        $sortDirection = 'asc',
        $filters = []
    ) {
        $parentField = $this->getParentField();
        $parentRelation = $this->getParentRelation();

        $query = $this->model->newQuery()
            ->with([$parentRelation . ':id,title', 'thumbnail'])
            ->select([
                'id', $parentField, 'season', 'seq',
                'video_type', 'youtube_url', 'video_file_path',
                'duration_text', 'description',
                'updated_at', 'created_at'
            ]);

        // —— parent_id 過濾 ——
        if (array_key_exists($parentField, $filters)) {
            // PHP 的 null → 撈出 parent_id IS NULL
            if (is_null($filters[$parentField]) || $filters[$parentField] === 'null') {
                $query->whereNull($parentField);
            }
            // 其他非空字串或數字 → 一般比對
            elseif ($filters[$parentField] !== '') {
                $query->where($parentField, $filters[$parentField]);
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
                ->orWhere('seq', 'like', "%{$search}%");
            });
        }

        // —— 排序 ——
        $validSortColumns = ['seq', 'season', 'updated_at', 'created_at'];
        if (in_array($sortColumn, $validSortColumns)) {
            $query->orderBy($sortColumn, $sortDirection);
        } else {
            $query->orderBy('season', 'asc')->orderBy('seq', 'asc');
        }

        return $query
        ->paginate($perPage)
        ->through(function ($episode) use ($parentField) {
            return [
                'id'               => $episode->id,
                $parentField       => $episode->$parentField,
                'season'           => $episode->season,
                'seq'              => $episode->seq,
                'episode_title'    => "第{$episode->seq}集",
                'season_title'     => "第{$episode->season}季",
                'video_type'       => $episode->video_type,
                'video_source'     => $episode->video_type === 'youtube' ? 'YouTube' : '本機上傳',
                'duration_text_zh' => $episode->getTranslation('duration_text', 'zh_TW'),
                'duration_text_en' => $episode->getTranslation('duration_text', 'en'),
                'description_zh'   => $episode->getTranslation('description', 'zh_TW'),
                'description_en'   => $episode->getTranslation('description', 'en'),
                'updated_at'       => $episode->updated_at->format('Y-m-d H:i:s'),
                'created_at'       => $episode->created_at->format('Y-m-d H:i:s'),
                'youtube_url'      => $episode->youtube_url,
                'video_url'        => $episode->video_file_path ? asset('storage/' .$episode->video_file_path) : null,
            ];
        });
    }

    /**
     * 根據父層ID和季數取得影片列表
     *
     * @param int $parentId 父層ID (drama_id 或 program_id)
     * @param int|null $season 季數
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getEpisodesByParent($parentId, $season = null)
    {
        $query = $this->model->where($this->getParentField(), $parentId)
            ->with('thumbnail');

        if ($season !== null) {
            $query->where('season', $season);
        }

        return $query->orderBy('season', 'asc')
            ->orderBy('seq', 'asc')
            ->get();
    }

    /**
     * 儲存影片（共用邏輯）
     *
     * @param array $data
     * @return mixed
     */
    public function saveEpisode(array $data)
    {
        // 根據影片類型清理不需要的欄位
        if ($data['video_type'] === 'youtube') {
            $data['video_file_path'] = null;
            $data['original_filename'] = null;
            $data['file_size'] = null;
            $data['video_format'] = null;
        } else {
            $data['youtube_url'] = null;
        }

        if (!empty($data['id'])) {
            $episode = $this->model->find($data['id']);
            if ($episode) {
                $episode->update($data);
                return $episode;
            }
        }

        return $this->model->create($data);
    }

    /**
     * 根據多個 ID 查詢影片
     *
     * @param array $ids
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function findByIds(array $ids)
    {
        return $this->model->whereIn('id', $ids)->get();
    }

    // updateSort 方法已移至 BaseRepository::batchUpdateSort

    /**
     * 刪除影片及其檔案
     *
     * @param int $id
     * @return bool
     */
    public function deleteEpisode($id): bool
    {
        $episode = $this->model->find($id);
        
        if (!$episode) {
            return false;
        }

        // 刪除實體檔案
        if ($episode->video_file_path) {
            Storage::disk('public')->delete($episode->video_file_path);
        }

        return $episode->delete();
    }

    /**
     * 取得下一個序號
     *
     * @param int $parentId
     * @param int|null $season
     * @return int
     */
    public function getNextSeq($parentId, $season = null): int
    {
        $query = $this->model->where($this->getParentField(), $parentId);

        if ($season !== null) {
            $query->where('season', $season);
        }

        $maxSeq = $query->max('seq') ?? 0;

        return $maxSeq + 1;
    }

    /**
     * 重新排序指定季數的集數
     * 針對同一個父層（影音/節目）的同一季進行排序重整
     *
     * @param int|null $parentId 父層ID（drama_id 或 program_id），null 表示暫存影片
     * @param int|null $season 季數
     * @param string|null $column 排序欄位名稱（預設為 'seq'）
     * @param bool $force 是否強制重整（預設為 true）
     * @return void
     */
    public function normalizeSortOrdersForSeason(
        ?int $parentId,
        ?int $season,
        string $column = null,
        bool $force = true
    ): void
    {
        $column = $column ?: 'seq';

        // 建立查詢：只處理指定父層和季數的集數
        $query = $this->model->whereNotNull($column);

        // 處理 parent_id：NULL、0、或正整數
        if ($parentId === null) {
            $query->whereNull($this->getParentField());
        } else {
            $query->where($this->getParentField(), $parentId);
        }

        $query->orderBy($column, 'asc')->orderBy('id', 'asc');

        // 如果有指定季數，加入季數篩選
        if ($season !== null) {
            $query->where('season', $season);
        }

        $items = $query->get(['id', $column]);

        // 如果沒有資料，直接返回
        if ($items->isEmpty()) {
            return;
        }

        DB::beginTransaction();
        try {
            $index = 1;
            foreach ($items as $item) {
                // 使用連續排序（1, 2, 3, 4...）
                $newSortOrder = $index;

                // 只更新需要變更的記錄
                if ((int) $item->{$column} !== $newSortOrder) {
                    DB::table($this->model->getTable())
                        ->where('id', $item->id)
                        ->update([$column => $newSortOrder]);
                }
                $index++;
            }
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }
}