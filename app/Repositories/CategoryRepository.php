<?php

namespace App\Repositories;

use App\Models\Category;
use App\Exceptions\BusinessException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CategoryRepository extends BaseRepository
{
    public function __construct(Category $category)
    {
        parent::__construct($category);
    }


    /**
     * 儲存主分類 & 子分類（不刪除，只更新與新增）
     *
     * @param  array    $data 驗證後資料，包含 children
     * @param  int|null $id   若為 null 則新增，否則更新
     * @return Category
     */
    public function save(array $data, $id = null): Category
    {
        return DB::transaction(function () use ($data, $id) {
            // 1. 拆出子分類
            $children = $data['children'] ?? [];
            unset($data['children']);

            // 2. 主分類 payload
            $payload = [
                'type'   => $data['type'],
                'name'   => [
                    'zh_TW' => $data['parent_name']['zh_TW'],
                    'en'    => $data['parent_name']['en'],
                ],
                'slug'   => Str::slug($data['parent_name']['en']),
                'seq'    => $data['seq'],
                'status' => (bool) $data['status'],
            ];

            // 3. 儲存／更新主分類
            /** @var Category $category */
            $category = parent::save($payload, $id);

            // 4. 依前端順序直接更新或新增子分類
            $this->updateChildren($category, $children);

            return $category;
        });
    }

    /**
     * 依前端傳來的順序，更新 (有 id) 或新增 (無 id) 子分類
     *
     * @param Category $category
     * @param array    $children
     */
    private function updateChildren(Category $category, array $children): void
    {
        foreach ($children as $index => $child) {
            $seq  = $index + 1;
            $slug = Str::slug($child['name']['en']);

            // 共用的更新欄位
            $data = [
                'seq'    => $seq,
                'status' => (bool) ($child['status'] ?? true),
                'slug'   => $slug,
                'name'   => [
                    'zh_TW' => $child['name']['zh_TW'],
                    'en'    => $child['name']['en'],
                ],
            ];

            // 如果有 id，就直接更新（不管 slug 是否改變）
            if (! empty($child['id'])) {
                $category->children()
                    ->where('id', $child['id'])
                    ->update($data);
                continue;
            }

            // 沒有 id 的才是真正的新增
            // 新增子分類
            $category->children()->create(array_merge($data, [
                'parent_id' => $category->id,
                'type'      => $category->type,
                'level'     => 2,
            ]));
        }
    }


    /**
     * 刪除子分類（專門的刪除方法）
     *
     * @param int $childId 子分類 ID
     * @return bool
     * @throws BusinessException
     */
    public function deleteChild($childId)
    {
        // 確保 ID 是整數
        $childId = (int) $childId;

        $child = $this->model->find($childId);

        if (!$child) {
            throw new BusinessException('子分類不存在');
        }

        if (is_null($child->parent_id)) {
            throw new BusinessException('無法透過此方法刪除主分類');
        }

        // 檢查是否為最後一個子分類（只有子分類必填的類型才限制）
        $siblingCount = $this->model
            ->where('parent_id', $child->parent_id)
            ->count();

        // 子分類非必填的類型可以刪除到 0 個
        $typesWithOptionalSubcategories = ['radio'];

        if ($siblingCount <= 1 && !in_array($child->type, $typesWithOptionalSubcategories)) {
            throw new BusinessException('至少要保留一個子分類');
        }

        // 檢查是否被影音使用
        $usedInDramas = DB::table('dramas')
            ->where('subcategory_id', $childId)
            ->exists();

        if ($usedInDramas) {
            $childName = $child->getTranslation('name', 'zh_TW');
            throw new BusinessException("子分類「{$childName}」正在被影音使用，無法刪除");
        }

        // 檢查是否被其他模組使用
        $usedInOtherModules = $this->checkUsageInOtherModules($childId);

        if ($usedInOtherModules) {
            $childName = $child->getTranslation('name', 'zh_TW');
            throw new BusinessException("子分類「{$childName}」正在被其他模組使用，無法刪除");
        }

        $child->delete();
        return true;
    }

    /**
     * 檢查子分類是否被其他模組使用
     *
     * @param int $childId 子分類 ID
     * @return bool
     */
    private function checkUsageInOtherModules($childId)
    {
        $usedInOtherModules = false;

        // 檢查節目是否使用 (如果表格存在)
        try {
            $usedInPrograms = DB::table('programs')
                ->where('subcategory_id', $childId)
                ->exists();
            $usedInOtherModules = $usedInOtherModules || $usedInPrograms;
        } catch (\Exception $e) {
            // 表格不存在，跳過檢查
        }

        // 檢查廣播是否使用 (如果表格存在)
        try {
            $usedInRadio = DB::table('radio')
                ->where('subcategory_id', $childId)
                ->exists();
            $usedInOtherModules = $usedInOtherModules || $usedInRadio;
        } catch (\Exception $e) {
            // 表格不存在，跳過檢查
        }

        return $usedInOtherModules;
    }

    /**
     * 批次檢查多個子分類是否被任何模組使用
     *
     * @param array $childrenIds 子分類 ID 陣列
     * @return void
     * @throws BusinessException 如果任何子分類被使用
     */
    private function checkChildrenUsage(array $childrenIds)
    {
        // 檢查影音是否使用任何子分類
        $usedInDramas = DB::table('dramas')
            ->whereIn('subcategory_id', $childrenIds)
            ->exists();

        if ($usedInDramas) {
            throw new BusinessException('此主分類下有子分類正在被影音使用，無法刪除');
        }

        // 檢查節目是否使用
        try {
            $usedInPrograms = DB::table('programs')
                ->whereIn('subcategory_id', $childrenIds)
                ->exists();

            if ($usedInPrograms) {
                throw new BusinessException('此主分類下有子分類正在被節目使用，無法刪除');
            }
        } catch (\Illuminate\Database\QueryException $e) {
            // 表格不存在，跳過檢查
        }

        // 檢查廣播是否使用
        try {
            $usedInRadio = DB::table('radio')
                ->whereIn('subcategory_id', $childrenIds)
                ->exists();

            if ($usedInRadio) {
                throw new BusinessException('此主分類下有子分類正在被廣播使用，無法刪除');
            }
        } catch (\Illuminate\Database\QueryException $e) {
            // 表格不存在，跳過檢查
        }
    }

    /**
     * 分頁查詢
     */
    public function paginate($perPage = 15, $sortColumn = 'seq', $sortDirection = 'asc', $filters = [])
    {
        $query = $this->model->newQuery();

        // 篩選條件
        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (array_key_exists('parent_id', $filters)) {
            if ($filters['parent_id'] === null || $filters['parent_id'] === 'null') {
                $query->whereNull('parent_id');
            } else {
                $query->where('parent_id', $filters['parent_id']);
            }
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                // 搜尋中文名稱
                $q->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(name, '$.zh_TW')) LIKE ?", ["%{$search}%"])
                    // 搜尋英文名稱
                    ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(name, '$.en')) LIKE ?", ["%{$search}%"]);
            });
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // 排序
        $validSortColumns = ['seq', 'name', 'created_at', 'updated_at', 'status'];
        if (in_array($sortColumn, $validSortColumns)) {
            if ($sortColumn === 'name') {
                // 按中文名稱排序
                $query->orderByRaw("JSON_UNQUOTE(JSON_EXTRACT(name, '$.zh_TW')) {$sortDirection}");
            } else {
                $query->orderBy($sortColumn, $sortDirection);
            }
        } else {
            $query->orderBy('seq', 'asc');
        }

        // 預載關聯
        $query->with(['parent', 'children']);

        return $query->paginate($perPage)->through(function ($category) {
            return [
                'id' => $category->id,
                'name_zh_tw' => $category->getTranslation('name', 'zh_TW'),
                'name_en' => $category->getTranslation('name', 'en'),
                'description' => $category->getTranslations('description'),
                'type' => $category->type,
                'parent_id' => $category->parent_id,
                'parent_name' => $category->parent ? $category->parent->getTranslation('name', 'zh_TW') : null,
                'level' => $category->level,
                'seq' => $category->seq,
                'status' => $category->status,
                'children_count' => $category->children->count(),
                'created_at' => $category->created_at->format('Y-m-d H:i:s'),
                'updated_at' => $category->updated_at->format('Y-m-d H:i:s'),
            ];
        });
    }

    /**
     * 取得指定類型的下一個序號
     */
    public function getNextSeq($type)
    {
        return $this->model->ofType($type)
            ->whereNull('parent_id') // 只算主分類
            ->max('seq') + 1;        // 取最大值 + 1
    }

    /**
     * 取得指定類型的分類樹（含子分類）
     */
    public function getCategoryTree($type)
    {
        return $this->model->ofType($type)
            ->status()
            ->with('children') // 載入第二層子分類
            ->roots()
            ->get();
    }

    /**
     * 根据给定的 ID 数组重新排序主分類
     *
     * @param array $ids 要排序的主分類记录的 ID 数组
     * @return void
     */
    public function sort($ids)
    {
        foreach ($ids as $k => $itemId) {
            $seq = $k + 1;

            // 直接用 find 然後檢查是否為主分類
            $category = $this->model->find($itemId);
            if ($category && is_null($category->parent_id)) {
                $category->seq = $seq;
                $category->save();
            }
        }
    }

    /**
     * 根據 ID 取得編輯表單所需的完整分類資料（包含主分類和子分類）
     *
     * @param int $id 主分類 ID
     * @param string|null $type 分類類型，用於驗證
     * @return array|null 格式化後的分類資料，適用於前端表單
     */
    public function getEditFormData($id, $type = null)
    {
        $query = $this->model->newQuery()
            ->with(['children' => function ($query) {
                $query->orderBy('seq', 'asc');
            }]);

        // 如果指定了類型，加入類型篩選
        if ($type) {
            $query->where('type', $type);
        }

        $category = $query->find($id);

        if (!$category || !is_null($category->parent_id)) {
            // 如果找不到或不是主分類，回傳 null
            return null;
        }

        return [
            'id' => $category->id,
            'type' => $category->type,
            'parent_name' => [
                'zh_TW' => $category->getTranslation('name', 'zh_TW'),
                'en' => $category->getTranslation('name', 'en'),
            ],
            'description' => [
                'zh_TW' => $category->getTranslation('description', 'zh_TW') ?? '',
                'en' => $category->getTranslation('description', 'en') ?? '',
            ],
            'seq' => $category->seq,
            'status' => $category->status,
            'cover_image' => $category->coverImage ? $category->coverImage->path : null,
            'children' => $category->children->map(function ($child, $index) {
                return [
                    'id' => $child->id,
                    'uid' => 'existing_' . $child->id, // 前端需要的 uid
                    'name' => [
                        'zh_TW' => $child->getTranslation('name', 'zh_TW'),
                        'en' => $child->getTranslation('name', 'en'),
                    ],
                    'seq' => $child->seq,
                    'status' => $child->status,
                ];
            })->toArray(),
            'created_at' => $category->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $category->updated_at->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * 覆寫刪除方法，加入額外檢查和完整刪除邏輯
     * @param int $id 分類 ID
     * @return bool
     */
    public function delete($id)
    {
        return DB::transaction(function () use ($id) {
            $category = $this->find($id);

            if (!$category) {
                throw new BusinessException('分類不存在');
            }

            $categoryType = $category->type;
            $isMainCategory = is_null($category->parent_id);

            // 如果是主分類，需要安全檢查
            if ($isMainCategory) {
                // 檢查主分類是否被使用
                $usedInDramas = DB::table('dramas')
                    ->where('category_id', $id)
                    ->exists();

                if ($usedInDramas) {
                    throw new BusinessException('此主分類正在被影音使用，無法刪除');
                }

                // 批次檢查所有子分類是否被使用（效能優化）
                $childrenIds = $category->children()->pluck('id')->toArray();

                if (!empty($childrenIds)) {
                    $this->checkChildrenUsage($childrenIds);

                    // 所有檢查都通過，批次刪除所有子分類
                    $category->children()->delete();
                }
            } else {
                // 如果是子分類，檢查是否被使用
                $usedInDramas = DB::table('dramas')
                    ->where('subcategory_id', $id)
                    ->exists();

                $usedInOtherModules = $this->checkUsageInOtherModules($id);

                if ($usedInDramas || $usedInOtherModules) {
                    throw new BusinessException('此子分類正在被使用，無法刪除');
                }
            }

            // 刪除分類本身
            $category->delete();

            // 只有刪除主分類時才重新排序
            if ($isMainCategory) {
                $this->reorderMainCategories($categoryType);
            }

            return true;
        });
    }

    /**
     * 重新排序主分類
     *
     * @param string $type 分類類型
     * @return void
     */
    protected function reorderMainCategories($type)
    {
        $mainCategories = $this->model->newQuery()
            ->where('type', $type)
            ->whereNull('parent_id')
            ->orderBy('seq', 'asc')
            ->get();

        foreach ($mainCategories as $index => $category) {
            $category->seq = $index + 1;
            $category->save();
        }
    }

    /**
     * 根據分類類型取得主分類和子分類資料
     *
     * @param string $type 分類類型 (drama, program, radio)
     * @return array
     */
    public function getCategoriesWithSubcategories($type)
    {
        // 取得當前語系
        $locale = app()->getLocale();

        // 取得主分類（parent_id 為 null）
        $mainCategories = $this->model->newQuery()
            ->ofType($type)
            ->status()
            ->whereNull('parent_id')
            ->orderBy('seq', 'asc')
            ->get()
            ->map(function ($category) use ($locale) {
                return [
                    'id' => $category->id,
                    'name' => $category->getTranslation('name', $locale),
                    'name_zh_tw' => $category->getTranslation('name', 'zh_TW'),
                    'name_en' => $category->getTranslation('name', 'en'),
                    'translations' => $category->getTranslations('name'),
                ];
            });

        // 取得子分類
        $subCategories = $this->model->newQuery()
            ->ofType($type)
            ->status()
            ->whereNotNull('parent_id')
            ->orderBy('parent_id')
            ->orderBy('seq', 'asc')
            ->get()
            ->map(function ($category) use ($locale) {
                return [
                    'id' => $category->id,
                    'parent_id' => $category->parent_id,
                    'name' => $category->getTranslation('name', $locale),
                    'name_zh_tw' => $category->getTranslation('name', 'zh_TW'),
                    'name_en' => $category->getTranslation('name', 'en'),
                    'translations' => $category->getTranslations('name'),
                ];
            });

        return [
            'main' => $mainCategories,
            'sub' => $subCategories
        ];
    }

    /**
     * 根據分類類型取得分類樹狀結構（含子分類）
     *
     * @param string $type 分類類型
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getCategoryTreeByType($type)
    {
        return $this->model->newQuery()
            ->ofType($type)
            ->status()
            ->with(['children' => function ($query) {
                $query->status()->orderBy('seq', 'asc');
            }])
            ->whereNull('parent_id')
            ->orderBy('seq', 'asc')
            ->get();
    }

    /**
     * 取得特定來源的分類對應關係（RSS 功能使用）
     *
     * @param string $provider 來源提供者（如 'cna'）
     * @return array 格式：['source_code' => category_id]
     */
    public function getSourceMappings(string $provider): array
    {
        return $this->model->newQuery()
            ->where('source_provider', $provider)
            ->whereNotNull('source_code')
            ->pluck('id', 'source_code')
            ->toArray();
    }

    /**
     * 根據來源代碼查找分類ID（RSS 功能使用）
     *
     * @param string $sourceCode 來源代碼（如 'PD', 'ED'）
     * @param string $provider 來源提供者（如 'cna'）
     * @return int|null
     */
    public function findIdBySourceCode(string $sourceCode, string $provider): ?int
    {
        $category = $this->model->newQuery()
            ->where('source_provider', $provider)
            ->where('source_code', $sourceCode)
            ->first();

        return $category ? $category->id : null;
    }

    /**
     * 取得前台新聞分類（只顯示有文章的分類，不含子分類）
     *
     * @param string $locale 語系（預設為當前語系）
     * @return \Illuminate\Support\Collection
     */
    public function getCategoriesWithArticles($locale = null): \Illuminate\Support\Collection
    {
        if (!$locale) {
            $locale = app()->getLocale();
        }

        return $this->model->newQuery()
            ->where('type', 'article')
            ->where('status', 1)
            ->whereNull('parent_id')  // 只取主分類
            ->whereHas('articles', function ($query) {
                $query->where('status', 1)
                    ->whereNotNull('publish_date')
                    ->where('publish_date', '<=', now());
            })
            ->withCount(['articles' => function ($query) {
                $query->where('status', 1)
                    ->whereNotNull('publish_date')
                    ->where('publish_date', '<=', now());
            }])
            ->orderBy('seq')
            ->get()
            ->map(function ($category) use ($locale) {
                return [
                    'id' => $category->id,
                    'name' => $category->getTranslation('name', $locale),
                ];
            });
    }

    /**
     * 取得所有新聞分類（後台使用，不限制是否有文章）
     *
     * @param string $locale 語系（預設為當前語系）
     * @return \Illuminate\Support\Collection
     */
    public function getAllArticleCategories($locale = null): \Illuminate\Support\Collection
    {
        if (!$locale) {
            $locale = app()->getLocale();
        }

        return $this->model->newQuery()
            ->where('type', 'article')
            ->where('status', 1)  // 只顯示啟用的分類
            ->whereNull('parent_id')  // 只取主分類
            ->orderBy('seq')
            ->get()
            ->map(function ($category) use ($locale) {
                return [
                    'id' => $category->id,
                    'name' => $category->getTranslation('name', $locale),
                ];
            });
    }

    /**
     * 依類型取得分類列表（用於下拉選單）
     *
     * @param string $type 分類類型 (news, drama, program, radio, article)
     * @param string|null $locale 語系（預設為當前語系）
     * @return \Illuminate\Support\Collection
     */
    public function getCategoriesByType($type, $locale = null): \Illuminate\Support\Collection
    {
        if (!$locale) {
            $locale = app()->getLocale();
        }

        return $this->model->newQuery()
            ->where('type', $type)
            ->where('status', 1)  // 只顯示啟用的分類
            ->whereNull('parent_id')  // 只取主分類（一層分類）
            ->orderBy('seq')
            ->get()
            ->map(function ($category) use ($locale) {
                return [
                    'id' => $category->id,
                    'name' => $category->getTranslation('name', $locale),
                ];
            });
    }
}
