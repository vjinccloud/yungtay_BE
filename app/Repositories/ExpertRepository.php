<?php

namespace App\Repositories;

use App\Models\Expert;
use Illuminate\Support\Facades\DB;

class ExpertRepository extends BaseRepository
{
    public function __construct(
        Expert $model,
        private ImageRepository $imgRepository
    ) {
        parent::__construct($model);
    }

    /**
     * 儲存專家資料
     */
    public function save(array $attributes = [], $id = null)
    {
        // 1. 拆出 slim 圖片資料
        $slimData = null;

        if (array_key_exists('slim', $attributes)) {
            if (!is_null($attributes['slim'])) {
                $slimData = $attributes['slim'];
            }
            unset($attributes['slim']);
        }

        // 2. 如果設為首席專家，先取消其他人的首席狀態（首席專家只能有一個）
        if (isset($attributes['is_featured']) && $attributes['is_featured']) {
            $this->model->where('is_featured', true)
                ->when($id, fn($query) => $query->where('id', '!=', $id))
                ->update(['is_featured' => false]);
        }

        // 3. 儲存主資料
        $expert = parent::save($attributes, $id);

        // 4. 圖片處理
        if ($slimData) {
            $imageData = ['image' => $slimData];
            $oldImage = $expert->image ?? null;

            $this->imgRepository->saveSlimFile(
                $imageData,
                $expert,
                $oldImage,
                'experts'
            );
        }

        return $expert;
    }

    /**
     * 排序欄位映射（前端欄位名 => 資料庫欄位名）
     */
    protected function getSortColumnMap(): array
    {
        return [
            'name_zh' => 'name',
            'title_zh' => 'title',
            'category_name' => 'category_id',
        ];
    }

    /**
     * 分頁列表
     */
    public function paginate($perPage, $sortColumn = 'sort_order', $sortDirection = 'asc', $filters = [])
    {
        // 轉換排序欄位名稱
        $columnMap = $this->getSortColumnMap();
        $sortColumn = $columnMap[$sortColumn] ?? $sortColumn;

        return $this->model->orderBy($sortColumn, $sortDirection)
            ->with(['category', 'image'])
            ->withCount('articles')
            ->filter($filters)
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn($expert) => [
                'id' => $expert->id,
                'name_zh' => $expert->getTranslation('name', 'zh_TW'),
                'title_zh' => $expert->getTranslation('title', 'zh_TW'),
                'category_name' => $expert->category?->getTranslation('name', 'zh_TW'),
                'tags' => $expert->tags,
                'is_featured' => (bool) $expert->is_featured,
                'is_active' => (bool) $expert->is_active,
                'sort_order' => $expert->sort_order,
                'articles_count' => $expert->articles_count,
                'image' => $expert->image ? '/' . $expert->image->path : null,
                'created_at' => $expert->created_at->toDateTimeString(),
                'updated_at' => $expert->updated_at->toDateTimeString(),
            ]);
    }

    /**
     * 取得表單用資料
     */
    public function getDetail($id)
    {
        $expert = $this->model->with(['category', 'fields', 'image'])->find($id);

        if (!$expert) {
            return null;
        }

        return [
            'id' => $expert->id,
            'category_id' => $expert->category_id,
            'name' => [
                'zh_TW' => $expert->getTranslation('name', 'zh_TW'),
            ],
            'title' => [
                'zh_TW' => $expert->getTranslation('title', 'zh_TW'),
            ],
            'specialty' => [
                'zh_TW' => $expert->getTranslation('specialty', 'zh_TW'),
            ],
            'bio' => [
                'zh_TW' => $expert->getTranslation('bio', 'zh_TW'),
            ],
            'tags' => $expert->tags,
            'is_featured' => (bool) $expert->is_featured,
            'is_active' => (bool) $expert->is_active,
            'sort_order' => $expert->sort_order,
            'img' => $expert->image ? '/' . $expert->image->path : null,
        ];
    }

    /**
     * 取得所有啟用的專家（給下拉選單用）
     */
    public function getActiveExperts()
    {
        return $this->model->where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->map(fn($expert) => [
                'id' => $expert->id,
                'name_zh' => $expert->getTranslation('name', 'zh_TW'),
                'title_zh' => $expert->getTranslation('title', 'zh_TW'),
            ]);
    }

    /**
     * 更新排序
     */
    public function updateSort(array $sortedIds)
    {
        DB::transaction(function () use ($sortedIds) {
            foreach ($sortedIds as $index => $id) {
                $this->model->where('id', $id)->update(['sort_order' => $index + 1]);
            }
        });
    }

    /**
     * 刪除專家
     */
    public function delete($id)
    {
        $expert = $this->find($id);

        if ($expert) {
            // 刪除圖片
            if ($expert->image) {
                $this->imgRepository->deleteImgFile($expert->image);
                $this->imgRepository->deleteImg($expert->image);
            }

            // 刪除領域關聯
            $expert->fields()->detach();

            return $expert->delete();
        }

        return false;
    }
}
