<?php

namespace Modules\NewsManagement\Backend\Repository;

use App\Models\News;
use App\Repositories\BaseRepository;
use App\Repositories\ImageRepository;

class NewsManagementRepository extends BaseRepository
{
    public function __construct(
        News $model,
        private ImageRepository $imgRepository
    ) {
        parent::__construct($model);
    }

    /**
     * 取得列表（分頁）
     */
    public function getListPaginated($request, int $perPage = 20)
    {
        $query = $this->model->newQuery()
            ->with(['image', 'category'])
            ->orderBy('updated_at', 'desc');

        if ($request->filled('keyword')) {
            $keyword = $request->input('keyword');
            $query->where(function ($q) use ($keyword) {
                $q->where('title->zh_TW', 'like', "%{$keyword}%")
                  ->orWhere('description', 'like', "%{$keyword}%");
            });
        }

        if ($request->filled('status') && $request->input('status') !== '') {
            $query->where('is_active', $request->input('status'));
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->input('category_id'));
        }

        return $query->paginate($perPage);
    }

    /**
     * 取得詳情（編輯用）
     */
    public function getDetail(int $id): array
    {
        $item = $this->model->with(['image', 'category'])->findOrFail($id);

        return [
            'id' => $item->id,
            'category_id' => $item->category_id,
            'title' => [
                'zh_TW' => $item->getTranslation('title', 'zh_TW'),
            ],
            'content' => [
                'zh_TW' => $item->getTranslation('content', 'zh_TW'),
            ],
            'description' => $item->description,
            'tags' => $item->tags,
            'is_active' => (bool) $item->is_active,
            'is_homepage_featured' => (bool) $item->is_homepage_featured,
            'is_pinned' => (bool) $item->is_pinned,
            'published_date' => $item->published_date?->toDateString(),
            'img' => $item->image ? '/' . $item->image->path : null,
        ];
    }

    /**
     * 儲存（含圖片處理）
     */
    public function store(array $attributes): News
    {
        return $this->saveWithImage($attributes, null);
    }

    /**
     * 更新（含圖片處理）
     */
    public function updateById(int $id, array $attributes): News
    {
        return $this->saveWithImage($attributes, $id);
    }

    /**
     * 儲存資料與圖片
     */
    protected function saveWithImage(array $attributes, ?int $id): News
    {
        $slimData = null;

        if (array_key_exists('slim', $attributes)) {
            if (!is_null($attributes['slim'])) {
                $slimData = $attributes['slim'];
            }
            unset($attributes['slim']);
        }

        unset($attributes['slimCleared']);

        // 儲存主資料
        if ($id) {
            $record = $this->model->findOrFail($id);
            $record->update($attributes);
        } else {
            $record = $this->model->create($attributes);
        }

        // 處理圖片
        if ($slimData) {
            $record = $record->fresh(['image']);
            $this->imgRepository->saveSlimFile(
                ['image' => $slimData],
                $record,
                $record->image ?? null,
                'news'
            );
        }

        return $record;
    }

    /**
     * 刪除（含圖片清理）
     */
    public function deleteWithImages(int $id): bool
    {
        $record = $this->model->with(['image'])->findOrFail($id);

        if ($record->image) {
            $this->imgRepository->deleteImgFile($record->image);
            $this->imgRepository->deleteImg($record->image);
        }

        return $record->delete();
    }

    /**
     * 計算首頁曝光文章數量
     */
    public function countHomepageFeatured(): int
    {
        return $this->model->where('is_homepage_featured', true)->count();
    }

    /**
     * 計算置頂文章數量
     */
    public function countPinned(): int
    {
        return $this->model->where('is_pinned', true)->count();
    }
}
