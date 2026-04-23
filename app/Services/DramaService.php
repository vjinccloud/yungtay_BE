<?php
namespace App\Services;

use App\Repositories\DramaRepository;
use App\Repositories\DramaEpisodeRepository;
use App\Traits\ContentServiceTrait;

class DramaService extends BaseService
{
    use ContentServiceTrait;
    
    public function __construct(
        private DramaRepository $drama,
        private DramaEpisodeRepository $dramaEpisode,
    ) {
        parent::__construct($drama);
    }

    /**
     * 實作 ContentServiceTrait 所需的抽象方法
     */
    protected function getContentType(): string
    {
        return 'drama';
    }
    
    protected function getContentTypeChinese(): string
    {
        return '影音';
    }
    
    protected function getEpisodeRepository()
    {
        return $this->dramaEpisode;
    }
    
    protected function getMainRepository()
    {
        return $this->drama;
    }
    
    protected function getListRouteName(): string
    {
        return 'admin.dramas';
    }

    /**
     * 前台篩選影音（使用 Trait 的通用方法）
     */
    public function getFilteredDramas(array $filters = [], $perPage = 18)
    {
        return $this->getFilteredContent($filters, $perPage);
    }

    // getEditData 方法已由 ContentServiceTrait 提供

    /**
     * 取得所有影音選項列表（使用 Trait 的通用方法）
     * 
     * @return \Illuminate\Support\Collection
     */
    public function getAllDramaOptions()
    {
        return $this->getAllContentOptions();
    }

    /**
     * 取得前台影音詳細資料（影片列表頁面用）
     *
     * @param int $dramaId
     * @return array
     * @throws \Exception
     */
    public function getDramaDetailForFrontend($dramaId)
    {
        $dramaData = $this->drama->getDramaDetailForFrontend($dramaId);
        
        if (!$dramaData) {
            throw new \Exception('影音不存在或未發布');
        }

        return $dramaData;
    }

    /**
     * 取得影音和指定集數資料（影片播放頁面用）
     *
     * @param int $dramaId
     * @param int $episodeId
     * @return array
     * @throws \Exception
     */
    public function getDramaWithEpisode($dramaId, $episodeId)
    {
        $data = $this->drama->getDramaWithEpisode($dramaId, $episodeId);
        
        if (!$data) {
            throw new \Exception('影音或集數不存在');
        }

        return $data;
    }

    /**
     * 取得推薦影音
     * 邏輯：同子分類影音，按上架時間排序取前四則
     *
     * @param int $dramaId 當前影音ID
     * @param int $limit 推薦數量
     * @return array
     */
    public function getRecommendations($dramaId, $limit = 4)
    {
        try {
            return $this->drama->getRecommendations($dramaId, $limit);
        } catch (\Exception $e) {
            \Log::error('取得推薦影音失敗: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * 覆寫模組標題
     */
    protected function getModuleTitle()
    {
        return '影音 - 信吉衛視';
    }

    /**
     * 取得精選影音（用於 JSON-LD CollectionPage）
     */
    public function getFeaturedDramas(int $limit = 3): array
    {
        return $this->drama->getForHomePage($limit)->toArray();
    }

}
