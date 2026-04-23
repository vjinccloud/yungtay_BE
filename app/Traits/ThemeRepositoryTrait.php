<?php

namespace App\Traits;

use Illuminate\Support\Facades\DB;

/**
 * 主題 Repository 共用邏輯
 * 用於 DramaThemeRepository 和 ProgramThemeRepository
 */
trait ThemeRepositoryTrait
{
    /**
     * 取得內容類型（drama 或 program）
     * @return string
     */
    abstract protected function getContentType(): string;

    /**
     * 取得關聯模型類別
     * @return string
     */
    abstract protected function getRelationModelClass(): string;

    /**
     * 取得內容欄位名稱（drama_id 或 program_id）
     * @return string
     */
    abstract protected function getContentIdField(): string;

    /**
     * 儲存時自動處理 sort_order 和內容關聯
     */
    public function save(array $attributes, $id = null)
    {
        // 分離出 content_id (drama_id 或 program_id)
        $contentIdField = $this->getContentIdField();
        $contentId = $attributes[$contentIdField] ?? null;
        unset($attributes[$contentIdField]);

        // 儲存主題
        $theme = parent::save($attributes, $id);
        
        // 重新排序所有主題
        $this->reorderAll();
        
        // 處理內容關聯
        $this->addContentRelation($theme->id, $contentId);

        return $theme;
    }

    /**
     * 新增內容主題關聯
     */
    protected function addContentRelation($themeId, $contentId = null): void
    {
        $relationModel = $this->getRelationModelClass();
        
        // 取得該主題目前的關聯數，決定 sort_order
        $count = $relationModel::where('theme_id', $themeId)->count();

        $relationModel::create([
            'theme_id' => $themeId,
            $this->getContentIdField() => $contentId ?: null,
            'sort_order' => $count + 1,
        ]);
    }

    /**
     * 重新排序所有主題
     */
    protected function reorderAll()
    {
        $themes = $this->model->orderBy('sort_order')->orderBy('id')->get();
        
        foreach ($themes as $index => $theme) {
            $theme->sort_order = $index + 1;
            $theme->save();
        }
    }

    // updateSort 方法已移至 BaseRepository::batchUpdateSort

    /**
     * 分頁查詢
     */
    public function paginate($perPage = 10, $sortColumn = 'sort_order', $sortDirection = 'asc', $filters = [])
    {
        $query = $this->model->newQuery();

        // 搜尋
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function($q) use ($search) {
                $q->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(name, '$.zh_TW')) LIKE ?", ["%{$search}%"])
                  ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(name, '$.en')) LIKE ?", ["%{$search}%"]);
            });
        }

        // 啟用狀態過濾
        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        // 排序白名單
        $allowedSortColumns = ['id', 'sort_order', 'is_active', 'created_at', 'updated_at'];
        if (!in_array($sortColumn, $allowedSortColumns)) {
            $sortColumn = 'sort_order';
        }

        // 動態計算關聯數量
        $contentRelation = $this->getContentType() . 's'; // dramas 或 programs
        
        return $query->withCount($contentRelation)
            ->orderBy($sortColumn, $sortDirection)
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn($theme) => [
                'id' => $theme->id,
                'name' => [
                    'zh_TW' => $theme->getTranslation('name', 'zh_TW'),
                    'en' => $theme->getTranslation('name', 'en'),
                ],
                'sort_order' => $theme->sort_order,
                'is_active' => (bool)$theme->is_active,
                "{$contentRelation}_count" => $theme->{"{$contentRelation}_count"} ?? 0,
                'created_at' => $theme->created_at->format('Y-m-d H:i:s'),
                'updated_at' => $theme->updated_at->format('Y-m-d H:i:s'),
            ]);
    }

    /**
     * 取得前台啟用的主題（含內容）
     */
    public function getActiveThemesWithContent($contentPerTheme = 8)
    {
        $contentRelation = $this->getContentType() . 's'; // dramas 或 programs
        
        return $this->model->where('is_active', 1)
            ->orderBy('sort_order')
            ->get()
            ->map(function ($theme) use ($contentPerTheme, $contentRelation) {
                $contents = $theme->$contentRelation()
                    ->where('is_active', 1)
                    ->whereNotNull('published_date')
                    ->where('published_date', '<=', now())
                    ->with(['posterDesktop', 'posterMobile'])
                    ->take($contentPerTheme)
                    ->get();
                
                return [
                    'id' => $theme->id,
                    'name' => $theme->getTranslation('name', app()->getLocale()),
                    $contentRelation => $contents->map(function ($content) {
                        return [
                            'id' => $content->id,
                            'title' => $content->getTranslation('title', app()->getLocale()),
                            'poster_desktop' => $content->posterDesktop?->url,
                            'poster_mobile' => $content->posterMobile?->url,
                        ];
                    })
                ];
            });
    }

    /**
     * 取得前台顯示的主題列表（包含內容）
     *
     * @param int|null $contentsPerTheme 每個主題顯示的內容數量，null 表示不限制
     * @return \Illuminate\Support\Collection
     */
    public function getActiveThemesWithContents($contentsPerTheme = null)
    {
        $contentType = $this->getContentType();
        $contentRelation = $contentType . 's';
        $relationTable = $contentType . '_theme_relations';

        // 廣播使用不同的日期欄位和圖片關聯
        $isRadio = ($contentType === 'radio');
        $dateField = $isRadio ? 'publish_date' : 'published_date';
        $imageRelations = $isRadio ? ['image'] : ['posterDesktop', 'posterMobile'];

        return $this->model
            ->where('is_active', true)
            ->orderBy('sort_order', 'asc')
            ->with([$contentRelation => function($query) use ($contentsPerTheme, $contentType, $relationTable, $dateField, $imageRelations) {
                $query->where($contentType . 's.is_active', true)
                    ->whereNotNull($dateField)
                    ->where($dateField, '<=', now())
                    ->with($imageRelations)
                    ->orderBy($relationTable . '.sort_order', 'asc');

                // 影音需要額外載入集數計數
                if ($contentType === 'drama') {
                    $query->withCount('episodes');
                }

                // 如果有指定數量限制才加上 limit
                if ($contentsPerTheme !== null && $contentsPerTheme > 0) {
                    $query->limit($contentsPerTheme);
                }
            }])
            ->get()
            ->map(function($theme) use ($contentRelation, $contentType, $isRadio) {
                $contents = $theme->$contentRelation->map(function($content) use ($contentType, $isRadio) {
                    // 廣播使用單一圖片，影音/節目使用雙版本圖片
                    if ($isRadio) {
                        $data = [
                            'id' => $content->id,
                            'title' => $content->getTranslation('title', app()->getLocale()),
                            'media_name' => $content->getTranslation('media_name', app()->getLocale()),
                            'image' => $content->image
                                ? asset($content->image->path)
                                : asset('frontend/images/default.webp'),
                            'year' => $content->year,
                        ];
                    } else {
                        $data = [
                            'id' => $content->id,
                            'title' => $content->getTranslation('title', app()->getLocale()),
                            'poster_desktop' => $content->posterDesktop
                                ? asset($content->posterDesktop->path)
                                : asset('frontend/images/' . ($contentType === 'drama' ? 'hot_drama_img_01.jpg' : 'popular_programs_img_01.jpg')),
                            'poster_mobile' => $content->posterMobile
                                ? asset($content->posterMobile->path)
                                : asset('frontend/images/mobile_01.jpg'),
                            'release_year' => $content->release_year,
                            'season_number' => $content->season_number,
                        ];

                        // 影音需要集數計數
                        if ($contentType === 'drama') {
                            $data['episodes_count'] = $content->episodes_count;
                        }
                    }

                    return $data;
                });

                return [
                    'id' => $theme->id,
                    'name' => $theme->getTranslation('name', app()->getLocale()),
                    $contentRelation => $contents
                ];
            })
            ->filter(function($theme) use ($contentRelation) {
                // 只返回有內容的主題
                return $theme[$contentRelation]->count() > 0;
            })
            ->values();
    }

    /**
     * 取得所有啟用的主題（用於篩選）
     * 
     * @return \Illuminate\Support\Collection
     */
    public function getActiveThemesForFilter()
    {
        return $this->model
            ->where('is_active', true)
            ->orderBy('sort_order', 'asc')
            ->get()
            ->map(function($theme) {
                return [
                    'id' => $theme->id,
                    'name' => $theme->getTranslation('name', app()->getLocale())
                ];
            });
    }
}