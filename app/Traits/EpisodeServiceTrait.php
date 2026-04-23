<?php

namespace App\Traits;

use Illuminate\Support\Facades\DB;

/**
 * 影片集數 Service 共用邏輯
 * 用於 DramaEpisodeService 和 ProgramEpisodeService
 */
trait EpisodeServiceTrait
{
    /**
     * 初始化集數排序設定
     * 在 Service 的 constructor 中呼叫
     */
    protected function initializeEpisodeSorting(): void
    {
        $this->autoNormalizeAfterDelete = true;
        $this->sortColumn = 'seq';  // 集數使用 seq 欄位
    }
    
    /**
     * 取得父層欄位名稱 (drama_id 或 program_id)
     * @return string
     */
    abstract protected function getParentField(): string;

    /**
     * 取得內容類型名稱 (影音 或 節目)
     * @return string
     */
    abstract protected function getContentTypeName(): string;
    
    /**
     * 取得內容類型英文名稱 (drama 或 program)
     * @return string
     */
    abstract protected function getContentType(): string;

    /**
     * 根據父層ID和季數取得影片列表
     *
     * @param int $parentId 父層ID
     * @param int|null $season 季數
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getEpisodesByParent($parentId, $season = null)
    {
        return $this->repository->getEpisodesByParent($parentId, $season);
    }

    /**
     * 儲存影片集數（共用邏輯）
     *
     * @param array $attributes 影片資料
     * @param int|null $id 影片ID（編輯時使用）
     * @return array
     */
    public function saveEpisode(array $attributes, $id = null)
    {
        try {
            DB::beginTransaction();

            // 如果是編輯模式，檢查是否切換了影片類型
            if ($id) {
                $oldEpisode = $this->repository->find($id);
                if ($oldEpisode) {
                    // 從 YouTube 切換到 Upload
                    if ($oldEpisode->video_type === 'youtube' && $attributes['video_type'] === 'upload') {
                        $attributes['youtube_url'] = null;
                        $attributes['video_url'] = null;
                    }
                    // 從 Upload 切換到 YouTube
                    elseif ($oldEpisode->video_type === 'upload' && $attributes['video_type'] === 'youtube') {
                        // 注意：不直接刪除檔案，因為可能已經是正式檔案
                        // 只清空相關欄位，讓檔案留在系統中（可透過後續清理作業處理）
                        $attributes['video_file_path'] = null;
                        $attributes['original_filename'] = null;
                        $attributes['file_size'] = null;
                        $attributes['video_format'] = null;
                        $attributes['video_url'] = null;
                    }
                }
            }

            // 如果是上傳類型但沒有檔案路徑
            if ($attributes['video_type'] === 'upload' && empty($attributes['video_file_path'])) {
                throw new \Exception('請選擇要上傳的影片檔案');
            }

            // 如果是 YouTube 類型但沒有 URL
            if ($attributes['video_type'] === 'youtube' && empty($attributes['youtube_url'])) {
                throw new \Exception('請輸入 YouTube 影片網址');
            }

            // 如果沒有提供序號，自動取得下一個序號
            if (empty($attributes['seq'])) {
                $parentId = $attributes[$this->getParentField()];
                $season = $attributes['season'] ?? null;
                $attributes['seq'] = $this->repository->getNextSeq($parentId, $season);
            }

            // 設定 ID
            if ($id) {
                $attributes['id'] = $id;
            }

            // 儲存影片
            $episode = $this->repository->saveEpisode($attributes);

            // 處理縮圖（如果有 ThumbnailHandlerTrait）
            if (method_exists($this, 'handleThumbnailGeneration')) {
                // 使用 handleThumbnailGeneration 而不是 generateThumbnailForEpisode
                // 這樣可以正確處理編輯時的 force 參數
                $this->handleThumbnailGeneration($episode, $attributes, $id);
            }

            // 觸發事件
            if ($this->eventService) {
                // 取得影音/節目名稱（先載入關聯）
                $relationName = $this->getContentTypeName() === '影音' ? 'drama' : 'program';
                $episode->load($relationName);

                $contentName = $episode->{$relationName}
                    ? $episode->{$relationName}->getTranslation('title', 'zh_TW')
                    : '';

                $episode->event_title = ($id ? '更新' : '新增')
                    . $this->getContentTypeName()
                    . '影片：'
                    . ($contentName ? "《{$contentName}》" : '')
                    . '第' . $episode->seq . '集';

                if ($id) {
                    $this->eventService->fireDataUpdated($episode);  // 更新時使用 fireDataUpdated
                } else {
                    $this->eventService->fireDataCreated($episode);  // 新增時使用 fireDataCreated
                }
            }

            DB::commit();

            return $this->ReturnHandle(
                true,
                ($id ? '更新' : '新增') . '成功'
            );

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->ReturnHandle(false, $e->getMessage());
        }
    }

    /**
     * 刪除影片
     *
     * @param int $id
     * @return array
     */
    public function deleteEpisode($id)
    {
        try {
            $episode = $this->repository->find($id);

            if (!$episode) {
                return $this->ReturnHandle(false, '找不到該影片');
            }

            // 記錄被刪除的集數資訊（供後續使用）
            $deletedSeq = $episode->seq;
            $parentId = $episode->{$this->getParentField()};
            $season = $episode->season;

            // 刪除縮圖（如果有）
            if (method_exists($this, 'deleteThumbnail')) {
                $this->deleteThumbnail($episode);
            }

            // 刪除影片
            $deleted = $this->repository->deleteEpisode($id);

            if ($deleted) {
                // 重新排序：刪除後集數必須保持連續（1, 2, 3...），因為集數等於標題
                // 包含暫存影片（drama_id = NULL 或 0）也需要重新排序
                $this->repository->normalizeSortOrdersForSeason(
                    $parentId,
                    $season,
                    'seq',
                    true
                );

                // 觸發事件
                if ($this->eventService) {
                    $episode->event_title = '刪除' . $this->getContentTypeName() . '影片：第' . $deletedSeq . '集';
                    $this->eventService->fireDataDeleted($episode);
                }

                return $this->ReturnHandle(true, '刪除成功');
            }

            return $this->ReturnHandle(false, '刪除失敗');

        } catch (\Exception $e) {
            return $this->ReturnHandle(false, $e->getMessage());
        }
    }

    /**
     * 更新排序
     * 支援兩種格式：
     * 1. 純 ID 陣列：[1, 2, 3]
     * 2. 物件陣列：[{id: 1}, {id: 2}, {id: 3}]
     *
     * @param array $items 包含 id 的陣列或純 ID 陣列
     * @param int|null $parentId 父層ID（用於驗證）
     * @param int|null $season 季數（用於驗證）
     * @return array
     */
    public function sortEpisodes(array $items, $parentId = null, $season = null)
    {
        try {
            // 如果是純 ID 陣列，轉換成物件陣列
            if (!empty($items) && is_numeric($items[0])) {
                $items = array_map(function($id) {
                    return ['id' => $id];
                }, $items);
            }
            
            // 安全性驗證：確保所有影片屬於同一父層和季數
            if (!empty($items)) {
                $ids = array_column($items, 'id');
                $episodes = $this->repository->findByIds($ids);
                
                if ($episodes->isEmpty()) {
                    throw new \Exception('找不到指定的影片');
                }
                
                // 檢查是否都屬於同一 parent
                $parentField = $this->getParentField();
                if ($parentId) {
                    $invalidEpisodes = $episodes->filter(function($e) use ($parentField, $parentId) {
                        return $e->$parentField != $parentId;
                    });
                    
                    if ($invalidEpisodes->isNotEmpty()) {
                        throw new \Exception('部分影片不屬於指定的' . $this->getContentTypeName());
                    }
                }
                
                // 檢查是否都屬於同一季
                if ($season !== null) {
                    $invalidEpisodes = $episodes->filter(function($e) use ($season) {
                        return $e->season != $season;
                    });
                    
                    if ($invalidEpisodes->isNotEmpty()) {
                        throw new \Exception('部分影片不屬於第' . $season . '季');
                    }
                }
                
                // 確保所有影片都屬於同一父層和同一季（自動檢查）
                $firstEpisode = $episodes->first();
                $firstParentId = $firstEpisode->$parentField;
                $firstSeason = $firstEpisode->season;
                
                $inconsistentEpisodes = $episodes->filter(function($e) use ($parentField, $firstParentId, $firstSeason) {
                    return $e->$parentField != $firstParentId || $e->season != $firstSeason;
                });
                
                if ($inconsistentEpisodes->isNotEmpty()) {
                    throw new \Exception('所有影片必須屬於同一' . $this->getContentTypeName() . '和同一季');
                }
            }
            
            // 使用 BaseService 的 updateSort（已包含事件處理）
            return parent::updateSort($items, 'seq');

        } catch (\Exception $e) {
            return $this->ReturnHandle(false, '排序失敗：' . $e->getMessage());
        }
    }

    /**
     * 覆寫排序事件標題格式
     * 
     * @param \Illuminate\Database\Eloquent\Model $model
     * @return string
     */
    protected function getSortEventTitle($model)
    {
        // 取得父層資料（影音或節目）
        $parentField = $this->getParentField();
        $parent = $model->$parentField ? $this->getParentModel($model->$parentField) : null;
        
        // 組合標題：影音:某某影片 或 影音影片
        // EventService 會自動在後面加上 "排序調整"
        $moduleTitle = $this->getContentTypeName();
        $parentTitle = $parent ? ":{$parent->getTranslation('title', 'zh_TW')}" : '';
        
        return "{$moduleTitle}{$parentTitle}影片";
    }

    /**
     * 取得父層模型（影音或節目）
     * 
     * @param int $parentId
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    protected function getParentModel($parentId)
    {
        $contentType = $this->getContentType();
        
        if ($contentType === 'drama') {
            return app(\App\Repositories\DramaRepository::class)->find($parentId);
        } elseif ($contentType === 'program') {
            return app(\App\Repositories\ProgramRepository::class)->find($parentId);
        }
        
        return null;
    }

    /**
     * 取得表格資料（使用陣列參數）
     * 注意：此方法與 Service 層的 getDataTableData 不同
     * 這個是使用陣列參數的版本，供未來統一使用
     *
     * @param array $params 包含 length, sortColumn, sortDirection, filters 等參數
     * @return array
     */
    public function fetchTableData(array $params)
    {
        // 設定父層欄位的篩選條件
        if (!isset($params[$this->getParentField()]) && isset($params['parent_id'])) {
            $params[$this->getParentField()] = $params['parent_id'];
        }

        return $this->repository->getDataTableData(
            $params['length'] ?? 15,
            $params['sortColumn'] ?? 'seq',
            $params['sortDirection'] ?? 'asc',
            $params
        );
    }

    /**
     * 取得影片季數列表
     * 用於新增/編輯表單的季數下拉選單
     *
     * @param int|null $parentId 父層ID（影音ID或節目ID）
     * @return array
     */
    public function getVideoSeasons($parentId = null)
    {
        return $this->repository->getVideoSeasons($parentId);
    }

    /**
     * 取得 DataTable 資料
     * 
     * @param int $perPage 每頁筆數
     * @param string $sortColumn 排序欄位
     * @param string $sortDirection 排序方向
     * @param array $filters 篩選條件
     * @return array
     */
    public function getDataTableData($perPage, $sortColumn = 'seq', $sortDirection = 'asc', $filters = [])
    {
        return $this->repository->getDataTableData($perPage, $sortColumn, $sortDirection, $filters);
    }

    /**
     * 取得編輯表單需要的資料
     *
     * @param int $id 影片ID
     * @return array|null
     */
    public function getEditData($id)
    {
        $episode = $this->find($id);

        if (!$episode) {
            return null;
        }

        $parentField = $this->getParentField();

        return [
            'id' => $episode->id,
            $parentField => $episode->$parentField,
            'season' => $episode->season ?? 1,
            'seq' => $episode->seq,
            'video_type' => $episode->video_type,
            'youtube_url' => $episode->youtube_url,
            'video_file_path' => $episode->video_file_path,
            'original_filename' => $episode->original_filename,
            'file_size' => $episode->file_size,
            'video_format' => $episode->video_format,
            'duration' => $episode->duration,
            'duration_text' => $episode->duration_text,
            'description' => $episode->description,
            'thumbnail' => $episode->thumbnail_url ?? null,
        ];
    }
}