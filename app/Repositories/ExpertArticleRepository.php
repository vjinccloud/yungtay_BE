<?php

namespace App\Repositories;

use App\Models\ExpertArticle;
use Illuminate\Support\Facades\DB;

class ExpertArticleRepository extends BaseRepository
{
    public function __construct(
        ExpertArticle $model,
        private ImageRepository $imgRepository
    ) {
        parent::__construct($model);
    }

    /**
     * 儲存文章資料
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

        // 2. 儲存主資料
        $article = parent::save($attributes, $id);

        // 3. 圖片處理
        if ($slimData) {
            $imageData = ['image' => $slimData];
            $oldImage = $article->image ?? null;

            $this->imgRepository->saveSlimFile(
                $imageData,
                $article,
                $oldImage,
                'expert-articles'
            );
        }

        return $article;
    }

    /**
     * 排序欄位映射（前端欄位名 => 資料庫欄位名）
     */
    protected function getSortColumnMap(): array
    {
        return [
            'title_zh' => 'title',
            'expert_name' => 'expert_id',
        ];
    }

    /**
     * 分頁列表
     */
    public function paginate($perPage, $sortColumn = 'updated_at', $sortDirection = 'desc', $filters = [])
    {
        // 轉換排序欄位名稱
        $columnMap = $this->getSortColumnMap();
        $sortColumn = $columnMap[$sortColumn] ?? $sortColumn;

        return $this->model->orderBy($sortColumn, $sortDirection)
            ->with(['expert', 'image'])
            ->filter($filters)
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn($article) => [
                'id' => $article->id,
                'title_zh' => $article->getTranslation('title', 'zh_TW'),
                'expert_name' => $article->expert?->getTranslation('name', 'zh_TW'),
                'published_date' => $article->published_date?->toDateString(),
                'is_active' => (bool) $article->is_active,
                'sort_order' => $article->sort_order,
                'image' => $article->image ? '/' . $article->image->path : null,
                'created_at' => $article->created_at->toDateTimeString(),
                'updated_at' => $article->updated_at->toDateTimeString(),
            ]);
    }

    /**
     * 取得表單用資料
     */
    public function getDetail($id)
    {
        $article = $this->model->with(['expert', 'image'])->find($id);

        if (!$article) {
            return null;
        }

        return [
            'id' => $article->id,
            'expert_id' => $article->expert_id,
            'title' => [
                'zh_TW' => $article->getTranslation('title', 'zh_TW'),
            ],
            'content' => [
                'zh_TW' => $article->getTranslation('content', 'zh_TW'),
            ],
            'description' => $article->description,
            'tags' => $article->tags,
            'sdgs' => $article->sdgs ?? [],
            'published_date' => $article->published_date?->toDateString(),
            'is_active' => (bool) $article->is_active,
            'img' => $article->image ? '/' . $article->image->path : null,
        ];
    }

    /**
     * 刪除文章
     */
    public function delete($id)
    {
        $article = $this->find($id);

        if ($article) {
            // 刪除圖片
            if ($article->image) {
                $this->imgRepository->deleteImgFile($article->image);
                $this->imgRepository->deleteImg($article->image);
            }

            return $article->delete();
        }

        return false;
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
}
