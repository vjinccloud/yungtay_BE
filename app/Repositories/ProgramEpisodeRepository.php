<?php

namespace App\Repositories;

use App\Models\ProgramEpisode;
use App\Traits\EpisodeRepositoryTrait;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ProgramEpisodeRepository extends BaseRepository
{
    use EpisodeRepositoryTrait;

    public function __construct(ProgramEpisode $programEpisode)
    {
        parent::__construct($programEpisode);
    }

    /**
     * 取得父層欄位名稱
     * @return string
     */
    protected function getParentField(): string
    {
        return 'program_id';
    }

    /**
     * 取得父層關聯名稱
     * @return string
     */
    protected function getParentRelation(): string
    {
        return 'program';
    }

    // 保留原有的 getEpisodesByProgram 方法以維持相容性
    public function getEpisodesByProgram($programId, $season = null)
    {
        return $this->getEpisodesByParent($programId, $season);
    }

    /**
     * 儲存影片（實作 EpisodeRepositoryTrait 需要的方法）
     * 
     * @param array $data
     * @return ProgramEpisode
     */
    public function saveEpisode(array $data): ProgramEpisode
    {
        $id = $data['id'] ?? null;
        return $this->save($data, $id);
    }
    
    /**
     * 儲存影片集數（主要邏輯）
     *
     * @param array $data 驗證後的資料
     * @param int|null $id 若為 null 則新增，否則更新
     * @return ProgramEpisode
     */
    public function save(array $data, $id = null): ProgramEpisode
    {
        return DB::transaction(function () use ($data, $id) {
            // 1. 先取出舊資料（若是編輯）
            $old = $id ? $this->model->find($id) : null;
            $oldPath = $old->video_file_path ?? null;

            // 2. 判斷是否為本機上傳
            $isUpload = (($data['video_type'] ?? '') === 'upload');

            // 3. 處理檔案搬移（若為 upload）
            $newPath = null;
            if ($isUpload && !empty($data['video_file_path'])) {
                $newPath = $this->moveUploadedFile($data['video_file_path']);
            }

            // 4. 刪除舊檔（原本是 upload，且改成 YouTube 或 換檔）
            if ($old && $old->video_file_path && $old->video_type === 'upload') {
                if (! $isUpload || ($newPath && $oldPath !== $newPath)) {
                    Storage::disk('public')->delete($oldPath);
                }
            }

            // 5. 準備 payload，先把共用欄位放好
            $payload = [
                'program_id'   => $data['program_id'],
                'season'       => $data['season'],
                'video_type'   => $data['video_type'],
                'description'  => [
                    'zh_TW' => $data['description']['zh_TW'] ?? '',
                    'en'    => $data['description']['en']    ?? '',
                ],
                'duration_text'=> [
                    'zh_TW' => $data['duration_text']['zh_TW'] ?? '',
                    'en'    => $data['duration_text']['en']    ?? '',
                ],
                'created_by'   => auth('admin')->id(),
                'updated_by'   => auth('admin')->id(),
            ];

            // 6. 只在「新增」時自動帶 seq
            if (! $id) {
                $payload['seq'] = $this->getNextSeq($data['program_id'], $data['season'] ?? null);
            }

            // 7. 根據來源補欄位
            if ($isUpload) {
                // 上傳類型：設定上傳相關欄位，清除 YouTube 欄位
                $payload['video_file_path']   = $newPath;
                $payload['original_filename'] = $data['original_filename'] ?? null;
                $payload['file_size']         = $data['file_size']       ?? null;
                $payload['video_format']      = $data['video_format']    ?? null;
                $payload['youtube_url']       = null; // 明確清除 YouTube URL
            } else {
                // YouTube 類型：設定 YouTube 欄位，清除上傳相關欄位
                $payload['youtube_url']       = $data['youtube_url'] ?? null;
                $payload['video_file_path']   = null;
                $payload['original_filename'] = null;
                $payload['file_size']         = null;
                $payload['video_format']      = null;
            }
            // 8. 呼叫父層 save
            return parent::save($payload, $id);
        });
    }

    /**
     * 取得指定節目和季數的下一個序號
     *
     * @param int $programId 節目 ID
     * @param int|null $season 季數
     * @return int 下一個序號
     */
    public function getNextSeq($programId, $season = null)
    {
        $query = $this->model->newQuery();

        // 使用 Laravel 的條件查詢方法
        $query->when($programId === null, function($q) {
            return $q->whereNull('program_id');
        }, function($q) use ($programId) {
            return $q->where('program_id', $programId);
        });

        // 如果有指定季數，加入季數條件
        if ($season !== null) {
            $query->where('season', $season);
        }

        // 計算目前的數量並加 1
        $count = $query->count();

        return $count + 1;
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
        $finalPath = 'programs/episodes/' . $filename;

        // 確保目錄存在
        Storage::disk('public')->makeDirectory('programs/episodes');

        // 移動檔案
        Storage::disk('public')->move($tempPath, $finalPath);

        return $finalPath;
    }

    /**
     * 刪除影片集數（覆寫父類別的 delete 方法）
     *
     * @param int $id
     * @return bool
     */
    public function delete($id)
    {
        return DB::transaction(function () use ($id) {
            // 1. 找到要刪除的影片集數
            $episode = $this->find($id);
            if (!$episode) {
                throw new \Exception('影片集數不存在');
            }

            // 2. 刪除實體檔案（僅限 upload，而且檔案實際存在）
            if ($episode->video_type === 'upload'
                && $episode->video_file_path
                && Storage::disk('public')->exists($episode->video_file_path)
            ) {
                Storage::disk('public')->delete($episode->video_file_path);
            }

            // 3. 刪除資料庫記錄
            $result = $episode->delete();

            // 4. 撈出同「劇」與同「季」的所有 episode id，支援 program_id/season 為 null
            $query = $this->model->newQuery();

            // program_id 條件
            if (is_null($episode->program_id)) {
                $query->whereNull('program_id');
            } else {
                $query->where('program_id', $episode->program_id);
            }

            $query->where('season', $episode->season);

            $ids = $query
                ->orderBy('seq', 'asc')
                ->pluck('id')    // 取得所有 id
                ->toArray();     // 轉成純陣列

            // 5. 重新排序
            $this->reorderEpisodes($ids);

            return $result;
        });
    }

    /**
     * 接收純 ID 陣列，依序更新 seq
     *
     * @param  int[]  $ids  依照欲排序順序的 episode id 陣列
     * @return void
     */
    public function reorderEpisodes(array $ids)
    {
        foreach ($ids as $index => $id) {
            $this->model
                ->where('id', $id)
                ->update(['seq' => $index + 1]);
        }
    }

    /**
     * 根據節目ID取得分頁影片列表
     *
     * @param int $programId 節目ID
     * @param int $perPage 每頁筆數
     * @param string $sortColumn 排序欄位
     * @param string $sortDirection 排序方向
     * @param array $filters 篩選條件
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function paginateByProgram($programId, $perPage = 15, $sortColumn = 'seq', $sortDirection = 'asc', $filters = [])
    {
        $query = $this->model->newQuery()
            ->where('program_id', $programId)
            ->with(['createdBy:id,name', 'updatedBy:id,name', 'thumbnail']);

        // 篩選條件
        if (!empty($filters['season'])) {
            $query->where('season', $filters['season']);
        }

        if (!empty($filters['video_type'])) {
            $query->where('video_type', $filters['video_type']);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function($q) use ($search) {
                $q->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(description, '$.zh_TW')) LIKE ?", ["%{$search}%"])
                  ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(description, '$.en')) LIKE ?", ["%{$search}%"]);
            });
        }

        // 排序
        $validSortColumns = ['seq', 'created_at', 'updated_at'];
        if (in_array($sortColumn, $validSortColumns)) {
            $query->orderBy($sortColumn, $sortDirection);
        } else {
            $query->orderBy('seq', 'asc');
        }

        return $query->paginate($perPage)->through(function ($episode) {
            return [
                'id' => $episode->id,
                'program_id' => $episode->program_id,
                'season' => $episode->season ?? 1,
                'seq' => $episode->seq,
                'description_zh_tw' => $episode->getTranslation('description', 'zh_TW'),
                'description_en' => $episode->getTranslation('description', 'en'),
                'duration_text_zh_tw' => $episode->getTranslation('duration_text', 'zh_TW'),
                'duration_text_en' => $episode->getTranslation('duration_text', 'en'),
                'video_type' => $episode->video_type,
                'youtube_url' => $episode->youtube_url,
                'video_file_path' => $episode->video_file_path,
                'file_size' => $episode->file_size,
                'video_format' => $episode->video_format,
                'created_by_name' => $episode->createdBy?->name,
                'updated_by_name' => $episode->updatedBy?->name,
                'created_at' => $episode->created_at->format('Y-m-d H:i:s'),
                'updated_at' => $episode->updated_at->format('Y-m-d H:i:s'),
            ];
        });
    }

    /**
     * 取得指定節目的影片季數
     *
     * @param int|null $programId 節目 ID，null 表示暫存影片
     * @return array 有影片的季數陣列
     */
    public function getVideoSeasons($programId)
    {
        try {
            $query = $this->model->newQuery();
            
            // 處理 program_id 可能為 null 的情況
            if (is_null($programId)) {
                $query->whereNull('program_id');
            } else {
                // 如果 programId 不是數字，回傳空陣列
                if (!is_numeric($programId)) {
                    return [];
                }
                $query->where('program_id', $programId);
            }

            $seasons = $query
                ->whereNotNull('season') // 確保季數不為 null
                ->distinct()
                ->pluck('season')
                ->filter() // 過濾掉空值
                ->sort()
                ->values()
                ->toArray();

            return $seasons;
        } catch (\Exception $e) {
            \Log::error("取得節目影片季數失敗 (program_id: {$programId}): " . $e->getMessage());
            return [];
        }
    }

    /**
     * 檢查是否有影片資料
     * - programId 為 null：檢查當前管理員的暫存影片
     * - programId 不為 null：檢查指定節目的影片
     *
     * @param int|null $programId
     * @return bool
     */
    public function hasVideos($programId = null)
    {
        $query = $this->model->newQuery();
        
        if (is_null($programId)) {
            // 檢查暫存影片（當前管理員建立的）
            $adminId = auth('admin')->id();
            $query->whereNull('program_id')
                  ->where('created_by', $adminId);
        } else {
            // 檢查指定節目的影片
            $query->where('program_id', $programId);
        }
        
        return $query->exists();
    }
}