<?php

namespace App\Repositories;

use App\Models\Program;
use App\Repositories\BaseRepository;
use Illuminate\Support\Facades\DB;
use App\Repositories\ImageRepository;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Traits\ContentRepositoryTrait;

class ProgramRepository extends BaseRepository
{
    use ContentRepositoryTrait;
    
    public function __construct(
        Program $program,
        private ImageRepository $imgRepository
    ) {
        parent::__construct($program);
    }
    
    /**
     * 實作 ContentRepositoryTrait 所需的抽象方法
     */
    protected function getContentType(): string
    {
        return 'program';
    }
    
    protected function getVideoIdField(): string
    {
        return 'program_id';
    }

    // save 方法已由 ContentRepositoryTrait 提供

    // handleImages 方法已由 ContentRepositoryTrait 提供

    // updateVideosWithContentId 方法已由 ContentRepositoryTrait 提供
    // 但需要保留一個包裝方法以保持向後相容
    private function updateVideosWithProgramId($programId)
    {
        return $this->updateVideosWithContentId($programId);
    }


    // paginate 方法已由 ContentRepositoryTrait 提供

    // delete 方法已由 ContentRepositoryTrait 提供

    // getAllForSelect 方法已由 ContentRepositoryTrait 提供
    
    /**
     * 前台篩選節目
     * 使用 Trait 的共用方法
     *
     * @param array $filters 篩選條件
     * @param int $perPage 每頁數量
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getFilteredPrograms(array $filters = [], $perPage = 18)
    {
        return $this->getFilteredContent($filters, $perPage);
    }
    
    /**
     * 取得節目詳細資料（前台影片列表頁面用）
     * 使用 Trait 的共用方法
     */
    public function getProgramDetailForFrontend($programId)
    {
        return $this->getContentDetailForFrontend($programId);
    }

    /**
     * 取得節目和指定集數資料（影片播放頁面用）
     * 使用 Trait 的共用方法
     */
    public function getProgramWithEpisode($programId, $episodeId)
    {
        return $this->getContentWithEpisode($programId, $episodeId);
    }

    /**
     * 取得推薦節目
     * 使用 Trait 的共用方法（繼承 ContentRepositoryTrait 的 getRecommendations）
     */
    // getRecommendations 方法已由 ContentRepositoryTrait 提供
}
