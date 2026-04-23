<?php
namespace App\Services;

use App\Repositories\ProgramRepository;
use App\Repositories\ProgramEpisodeRepository;
use App\Models\Category;
use App\Traits\ContentServiceTrait;

class ProgramService extends BaseService
{
    use ContentServiceTrait;
    
    public function __construct(
        private ProgramRepository $program,
        private ProgramEpisodeRepository $programEpisode,
    ) {
        parent::__construct($program);
    }

    /**
     * 實作 ContentServiceTrait 所需的抽象方法
     */
    protected function getContentType(): string
    {
        return 'program';
    }
    
    protected function getContentTypeChinese(): string
    {
        return '節目';
    }
    
    protected function getEpisodeRepository()
    {
        return $this->programEpisode;
    }
    
    protected function getMainRepository()
    {
        return $this->program;
    }
    
    protected function getListRouteName(): string
    {
        return 'admin.programs';
    }

    /**
     * 前台篩選節目（使用 Trait 的通用方法）
     */
    public function getFilteredPrograms(array $filters = [], $perPage = 18)
    {
        return $this->getFilteredContent($filters, $perPage);
    }

    // getEditData 方法已由 ContentServiceTrait 提供

    /**
     * 取得所有節目選項（使用 Trait 的通用方法，向後相容）
     */
    public function getAllProgramOptions()
    {
        return $this->getAllContentOptions();
    }

    /**
     * 取得節目詳細資料（前台影片列表頁面用）
     *
     * @param int $programId
     * @return array
     * @throws \Exception
     */
    public function getProgramDetailForFrontend($programId)
    {
        $programData = $this->program->getProgramDetailForFrontend($programId);
        
        if (!$programData) {
            throw new \Exception('節目不存在或未發布');
        }
        
        return $programData;
    }

    /**
     * 取得節目和指定集數資料（影片播放頁面用）
     *
     * @param int $programId
     * @param int $episodeId
     * @return array
     * @throws \Exception
     */
    public function getProgramWithEpisode($programId, $episodeId)
    {
        $data = $this->program->getProgramWithEpisode($programId, $episodeId);
        
        if (!$data) {
            throw new \Exception('節目或集數不存在');
        }
        
        return $data;
    }

    /**
     * 取得推薦節目
     * 邏輯：同子分類節目，按上架時間排序取前四則
     *
     * @param int $programId 當前節目ID
     * @param int $limit 推薦數量
     * @return array
     */
    public function getRecommendations($programId, $limit = 4)
    {
        try {
            return $this->program->getRecommendations($programId, $limit);
        } catch (\Exception $e) {
            \Log::error('取得推薦節目失敗: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * 覆寫模組標題
     */
    protected function getModuleTitle()
    {
        return '節目 - 信吉衛視';
    }

    /**
     * 取得精選節目（用於 JSON-LD CollectionPage）
     */
    public function getFeaturedPrograms(int $limit = 3): array
    {
        return $this->program->getForHomePage($limit)->toArray();
    }
}
