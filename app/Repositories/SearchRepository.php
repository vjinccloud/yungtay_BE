<?php

namespace App\Repositories;

use App\Models\Article;
use App\Models\Drama;
use App\Models\Program;
use App\Models\Live;
use App\Models\Radio;
use App\Models\News;
use App\Traits\CommonTrait;

/**
 * SearchRepository
 * 
 * 負責處理全站搜尋的資料查詢邏輯
 * 所有搜尋方法都使用各 Model 的 scopeFilter，遵循 MSR 架構
 */
class SearchRepository
{
    use CommonTrait;

    /**
     * 搜尋新聞
     * 
     * @param string $keyword 關鍵字
     * @param int $page 頁碼
     * @param int $limit 每頁數量
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function searchArticle($keyword, $page = 1, $limit = 8)
    {
        $query = Article::filter(['search' => $keyword])
            ->where('is_active', true)
            ->where('publish_date', '<=', now())
            ->with(['image', 'image_thumbnail', 'category'])
            ->orderBy('publish_date', 'desc');

        $paginated = $query->paginate($limit, ['*'], 'page', $page);
        
        // 格式化資料
        $paginated->through(function ($item) {
            return $this->formatSearchResultForFrontend($item, 'article');
        });

        return $paginated;
    }

    /**
     * 搜尋影音
     * 
     * @param string $keyword 關鍵字
     * @param int $page 頁碼
     * @param int $limit 每頁數量
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function searchDrama($keyword, $page = 1, $limit = 8)
    {
        $query = Drama::filter(['search' => $keyword])
            ->where('is_active', true)
            ->where('published_date', '<=', now())
            ->with(['posterDesktop', 'posterMobile', 'category'])
            ->orderBy('published_date', 'desc');

        $paginated = $query->paginate($limit, ['*'], 'page', $page);

        // 格式化資料
        $paginated->through(function ($item) {
            return $this->formatSearchResultForFrontend($item, 'drama');
        });

        return $paginated;
    }

    /**
     * 搜尋節目
     * 
     * @param string $keyword 關鍵字
     * @param int $page 頁碼
     * @param int $limit 每頁數量
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function searchProgram($keyword, $page = 1, $limit = 8)
    {
        $query = Program::filter(['search' => $keyword])
            ->where('is_active', true)
            ->where('published_date', '<=', now())
            ->with(['posterDesktop', 'posterMobile', 'category'])
            ->orderBy('published_date', 'desc');

        $paginated = $query->paginate($limit, ['*'], 'page', $page);

        // 格式化資料
        $paginated->through(function ($item) {
            return $this->formatSearchResultForFrontend($item, 'program');
        });

        return $paginated;
    }

    /**
     * 搜尋直播
     * 
     * @param string $keyword 關鍵字
     * @param int $page 頁碼
     * @param int $limit 每頁數量
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function searchLive($keyword, $page = 1, $limit = 8)
    {
        $query = Live::filter(['search' => $keyword])
            ->where('is_active', true)
            ->with('images')
            ->orderBy('sort_order', 'asc');

        $paginated = $query->paginate($limit, ['*'], 'page', $page);

        // 格式化資料
        $paginated->through(function ($item) {
            return $this->formatSearchResultForFrontend($item, 'live');
        });

        return $paginated;
    }

    /**
     * 搜尋廣播
     *
     * @param string $keyword 關鍵字
     * @param int $page 頁碼
     * @param int $limit 每頁數量
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function searchRadio($keyword, $page = 1, $limit = 10)
    {
        $query = Radio::filter(['search' => $keyword])
            ->where('is_active', true)
            ->with(['image', 'category'])
            ->orderBy('updated_at', 'desc');

        $paginated = $query->paginate($limit, ['*'], 'page', $page);

        // 格式化資料
        $paginated->through(function ($item) {
            return $this->formatSearchResultForFrontend($item, 'radio');
        });

        return $paginated;
    }

    /**
     * 搜尋最新消息
     *
     * @param string $keyword 關鍵字
     * @param int $page 頁碼
     * @param int $limit 每頁數量
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function searchNews($keyword, $page = 1, $limit = 8)
    {
        $query = News::filter(['search' => $keyword])
            ->where('is_active', true)
            ->where('published_date', '<=', now())
            ->with(['image'])
            ->orderBy('published_date', 'desc');

        $paginated = $query->paginate($limit, ['*'], 'page', $page);

        // 格式化資料
        $paginated->through(function ($item) {
            return $this->formatSearchResultForFrontend($item, 'news');
        });

        return $paginated;
    }


    /**
     * 統一的搜尋結果格式化方法
     * 
     * @param mixed $item 搜尋結果項目
     * @param string $contentType 內容類型
     * @return array
     */
    private function formatSearchResultForFrontend($item, $contentType)
    {
        $locale = app()->getLocale();
        
        $result = [
            'id' => $item->id,
            'title' => $item->getTranslation('title', $locale),
            'image' => $this->getSearchImage($item, $contentType),
            'url' => $this->getSearchUrl($item, $contentType),
            'category' => $item->category ? $item->category->getTranslation('name', $locale) : null
        ];
        
        // 根據內容類型添加特定欄位
        switch ($contentType) {
            case 'article':
                $result['content'] = $item->getTranslation('content', $locale);
                $result['publish_date'] = $item->publish_date->format('Y.m.d');
                break;
                
            case 'drama':
            case 'program':
                $result['description'] = $item->getTranslation('description', $locale);
                $result['poster_desktop'] = $this->getPosterImage($item, 'posterDesktop');
                $result['poster_mobile'] = $this->getPosterImage($item, 'posterMobile');
                $result['release_year'] = $item->release_year;
                $result['season_number'] = $item->season_number;
                break;
                
            case 'live':
                $result['description'] = $item->getTranslation('description', $locale);
                $result['youtube_url'] = $item->youtube_url;
                $result['thumbnail'] = $result['image']; // 直播使用 thumbnail 字段
                $result['featured_image'] = $result['image']; // 同時提供 featured_image 給前端統一處理
                break;
                
            case 'radio':
                $result['media_name'] = $item->getTranslation('media_name', $locale);
                $result['publish_date'] = $item->publish_date ? $item->publish_date->format('Y.m.d') : null;
                break;

            case 'news':
                $result['content'] = $item->getTranslation('content', $locale);
                $result['publish_date'] = $item->published_date->format('Y.m.d');
                break;
        }

        return $result;
    }

    /**
     * 取得搜尋結果的圖片
     * 
     * @param mixed $item 搜尋結果項目
     * @param string $contentType 內容類型
     * @return string|null
     */
    private function getSearchImage($item, $contentType)
    {
        switch ($contentType) {
            case 'article':
                // 優先使用縮圖，如果沒有縮圖則使用原圖
                if ($item->image_thumbnail) {
                    return asset($this->resolveImageUrl($item->image_thumbnail->path));
                } elseif ($item->image) {
                    return asset($this->resolveImageUrl($item->image->path));
                }
                return null;
                
            case 'drama':
            case 'program':
                // Drama/Program 優先使用 posterDesktop
                $posterDesktop = $item->posterDesktop;
                return $posterDesktop ? asset($this->resolveImageUrl($posterDesktop->path)) : null;
                
            case 'live':
                // 使用高品質 YouTube 縮圖
                return $this->getYouTubeThumbnail($item->youtube_url, 'maxresdefault');
                
            case 'radio':
                $image = $item->image;
                return $image ? asset($this->resolveImageUrl($image->path)) : null;

            case 'news':
                $image = $item->image;
                return $image ? asset($this->resolveImageUrl($image->path)) : null;

            default:
                return null;
        }
    }

    /**
     * 取得 Poster 圖片
     * 
     * @param mixed $item 項目
     * @param string $relation 關聯名稱
     * @return string|null
     */
    private function getPosterImage($item, $relation)
    {
        $poster = $item->{$relation};
        return $poster ? asset($this->resolveImageUrl($poster->path)) : null;
    }

    /**
     * 取得搜尋結果的 URL
     * 
     * @param mixed $item 搜尋結果項目
     * @param string $contentType 內容類型
     * @return string
     */
    private function getSearchUrl($item, $contentType)
    {
        switch ($contentType) {
            case 'article':
                return route('articles.show', $item->id);
            case 'drama':
                return route('drama.videos.index', $item->id);
            case 'program':
                return route('program.videos.index', $item->id);
            case 'live':
                return route('live.index', $item->id);
            case 'radio':
                return route('radio.show', $item->id);
            case 'news':
                return route('news.show', $item->id);
            default:
                return '#';
        }
    }

}