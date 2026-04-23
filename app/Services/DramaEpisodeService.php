<?php

namespace App\Services;

use App\Repositories\DramaEpisodeRepository;
use App\Services\EventService;
use App\Traits\CommonTrait;
use App\Traits\ThumbnailHandlerTrait;
use App\Traits\EpisodeServiceTrait;
use Illuminate\Support\Facades\DB;

class DramaEpisodeService extends BaseService
{
    use CommonTrait, ThumbnailHandlerTrait, EpisodeServiceTrait;

    public function __construct(
        private DramaEpisodeRepository $dramaEpisodeRepository
    ) {
        parent::__construct($dramaEpisodeRepository);
        $this->eventService = app(EventService::class);
        $this->initializeEpisodeSorting();  // 初始化集數排序設定
    }

    /**
     * 取得父層欄位名稱
     * @return string
     */
    protected function getParentField(): string
    {
        return 'drama_id';
    }

    /**
     * 取得內容類型名稱
     * @return string
     */
    protected function getContentTypeName(): string
    {
        return '影音';
    }
    
    /**
     * 取得內容類型英文名稱
     * @return string
     */
    protected function getContentType(): string
    {
        return 'drama';
    }
    
    /**
     * 取得影片類型（供 ThumbnailHandlerTrait 使用）
     * 
     * @return string
     */
    protected function getEpisodeType(): string
    {
        return 'drama';
    }
    
    /**
     * 取得原始實體（供 ThumbnailHandlerTrait 使用）
     * 
     * @param int $id
     * @return mixed
     */
    protected function getOriginalEntity(int $id)
    {
        return $this->repository->find($id);
    }

    /**
     * 根據影音ID和季數取得影片列表（保留原方法以維持相容性）
     *
     * @param int $dramaId 影音ID
     * @param int|null $season 季數
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getEpisodesByDrama($dramaId, $season = null)
    {
        return $this->getEpisodesByParent($dramaId, $season);
    }

    /**
     * 儲存影片集數（使用 trait 的方法）
     */
    public function save(array $attributes, $id = null)
    {
        return $this->saveEpisode($attributes, $id);
    }

    /**
     * 刪除影片集數（使用 trait 的方法）
     */
    public function delete($id)
    {
        return $this->deleteEpisode($id);
    }



    /**
     * 取得指定季數的影片統計資訊（影音特有方法）
     *
     * @param int $dramaId 影音ID
     * @param int $season 季數
     * @return array
     */
    public function getSeasonStats($dramaId, $season)
    {
        $episodes = $this->getEpisodesByDrama($dramaId, $season);

        return [
            'season' => $season,
            'total_episodes' => $episodes->count(),
            'youtube_episodes' => $episodes->where('video_type', 'youtube')->count(),
            'upload_episodes' => $episodes->where('video_type', 'upload')->count(),
            'total_file_size' => round($episodes->where('video_type', 'upload')->sum('file_size'), 2),
        ];
    }


    /**
     * 清理暫存檔案（影音特有方法）
     *
     * @param array $attributes
     * @return void
     */
    private function cleanupTempFiles($attributes)
    {
        // 如果有暫存檔案路徑且不是正式路徑，則刪除
        if (!empty($attributes['temp_file_path'])) {
            $tempPath = storage_path('app/public/' . $attributes['temp_file_path']);
            if (file_exists($tempPath)) {
                unlink($tempPath);
            }
        }
    }
}