<?php

namespace App\Services;

use App\Repositories\SearchRepository;
use App\Traits\CommonTrait;

/**
 * SearchService
 * 
 * 負責處理全站搜尋的業務邏輯
 * 遵循 MSR 架構：Controller → Service → Repository
 */
class SearchService
{
    use CommonTrait;

    protected $repository;

    public function __construct(SearchRepository $repository)
    {
        $this->repository = $repository;
    }


    /**
     * 搜尋單一類別或分頁
     *
     * @param string $type 類別類型 (article|drama|program|live|radio|news)
     * @param string $keyword 搜尋關鍵字
     * @param int $page 頁碼
     * @param string $mode 模式 (all|single)
     * @param int|null $perPage 每頁數量 (前端指定)
     * @return array
     */
    public function searchByType($type, $keyword, $page = 1, $mode = 'single', $perPage = null)
    {
        // 優先使用前端傳來的每頁數量，否則使用預設值
        if ($perPage) {
            $limit = $perPage;
        } else {
            // 根據模式決定每頁數量
            if ($mode === 'single') {
                // 單一類別模式
                $limit = ($type === 'radio') ? 20 : 16;
            } else {
                // 全部模式的分頁
                $limit = ($type === 'radio') ? 10 : 8;
            }
        }

        // 調用對應的搜尋方法
        $method = 'search' . ucfirst($type);
        $results = $this->repository->$method($keyword, $page, $limit);
        return $this->ReturnHandle(true, '搜尋完成', null, $results);
    }
}