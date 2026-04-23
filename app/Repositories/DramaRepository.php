<?php

namespace App\Repositories;

use App\Models\Drama;
use App\Repositories\BaseRepository;
use Illuminate\Support\Facades\DB;
use App\Repositories\ImageRepository;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Traits\ContentRepositoryTrait;

class DramaRepository extends BaseRepository
{
    use ContentRepositoryTrait;
    
    public function __construct(
        Drama $drama,
        private ImageRepository $imgRepository
    ) {
        parent::__construct($drama);
    }
    
    /**
     * 實作 ContentRepositoryTrait 所需的抽象方法
     */
    protected function getContentType(): string
    {
        return 'drama';
    }
    
    protected function getVideoIdField(): string
    {
        return 'drama_id';
    }

    // save 方法已由 ContentRepositoryTrait 提供

    // handleImages 方法已由 ContentRepositoryTrait 提供

    // updateVideosWithContentId 方法已由 ContentRepositoryTrait 提供
    // 但需要保留一個包裝方法以保持向後相容
    private function updateVideosWithDramaId($dramaId)
    {
        return $this->updateVideosWithContentId($dramaId);
    }


    // paginate 方法已由 ContentRepositoryTrait 提供
    


    /**
     * 前台篩選影音
     * 使用 Trait 的共用方法
     *
     * @param array $filters 篩選條件
     * @param int $perPage 每頁數量
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getFilteredDramas(array $filters = [], $perPage = 18)
    {
        return $this->getFilteredContent($filters, $perPage);
    }


    // getEditFormData 方法已由 ContentRepositoryTrait 提供


    // delete 方法已由 ContentRepositoryTrait 提供

    // getAllForSelect 方法已由 ContentRepositoryTrait 提供

    /**
     * 取得前台影音詳細資料（含 banner 圖片）
     * 使用 Trait 的共用方法
     *
     * @param int $dramaId
     * @return array|null
     */
    public function getDramaDetailForFrontend($dramaId)
    {
        return $this->getContentDetailForFrontend($dramaId);
    }

    /**
     * 取得影音和指定集數資料
     * 使用 Trait 的共用方法
     *
     * @param int $dramaId
     * @param int $episodeId
     * @return array|null
     */
    public function getDramaWithEpisode($dramaId, $episodeId)
    {
        return $this->getContentWithEpisode($dramaId, $episodeId);
    }




    // getRecommendations 方法已由 ContentRepositoryTrait 提供

}
