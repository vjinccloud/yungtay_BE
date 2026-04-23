<?php

namespace Modules\HomeVideoSetting\Backend\Repository;

use App\Repositories\BaseRepository;
use Modules\HomeVideoSetting\Model\HomeVideoSetting;
use Illuminate\Support\Facades\Storage;

/**
 * HomeVideoSetting 首頁影片管理 - Repository
 */
class HomeVideoSettingRepository extends BaseRepository
{
    public function __construct(HomeVideoSetting $model)
    {
        parent::__construct($model);
    }

    /**
     * 取得列表（DataTables 用）
     */
    public function getList()
    {
        return $this->model->ordered()->get()->map(function ($item) {
            return [
                'id' => $item->id,
                'title_zh' => $item->getTranslation('title', 'zh_TW'),
                'title_en' => $item->getTranslation('title', 'en'),
                'video_zh' => $item->video_zh_path ? true : false,
                'video_en' => $item->video_en_path ? true : false,
                'sort' => $item->sort,
                'is_enabled' => $item->is_enabled,
                'created_at' => $item->created_at?->format('Y-m-d H:i'),
            ];
        });
    }

    /**
     * 取得詳情（編輯用）
     */
    public function getDetail($id)
    {
        $item = $this->model->findOrFail($id);

        return [
            'id' => $item->id,
            'title' => [
                'zh_TW' => $item->getTranslation('title', 'zh_TW'),
                'en' => $item->getTranslation('title', 'en'),
            ],
            'video_zh' => [
                'path' => $item->video_zh_path ?: null,
                'name' => $item->video_zh_name,
            ],
            'video_en' => [
                'path' => $item->video_en_path ?: null,
                'name' => $item->video_en_name,
            ],
            'sort' => $item->sort ?? 0,
            'is_enabled' => $item->is_enabled ?? true,
        ];
    }

    /**
     * 新增
     */
    public function store(array $attributes)
    {
        // 自動取得最大排序值 +1
        $maxSort = $this->model->max('sort') ?? 0;
        
        $record = $this->model->create([
            'title' => $attributes['title'] ?? [],
            'sort' => $attributes['sort'] ?? ($maxSort + 1),
            'is_enabled' => $attributes['is_enabled'] ?? true,
        ]);

        $this->handleVideoUpload($record, $attributes);

        return $record;
    }

    /**
     * 更新
     */
    public function updateRecord($id, array $attributes)
    {
        $record = $this->model->findOrFail($id);

        $record->update([
            'title' => $attributes['title'] ?? $record->title,
            'sort' => $attributes['sort'] ?? $record->sort,
            'is_enabled' => $attributes['is_enabled'] ?? $record->is_enabled,
        ]);

        $this->handleVideoUpload($record, $attributes);

        return $record;
    }

    /**
     * 刪除
     */
    public function destroy($id)
    {
        $record = $this->model->findOrFail($id);

        // 刪除影片檔案
        if ($record->video_zh_path && Storage::disk('public')->exists($record->video_zh_path)) {
            Storage::disk('public')->delete($record->video_zh_path);
        }
        if ($record->video_en_path && Storage::disk('public')->exists($record->video_en_path)) {
            Storage::disk('public')->delete($record->video_en_path);
        }

        return $record->delete();
    }

    /**
     * 處理影片上傳
     */
    protected function handleVideoUpload($record, array $attributes)
    {
        // 處理中文版影片
        if (!empty($attributes['video_zh_path'])) {
            $incomingPath = $this->ensureStoragePath($attributes['video_zh_path']);

            if ($incomingPath !== $record->video_zh_path) {
                // 刪除舊影片
                if ($record->video_zh_path && Storage::disk('public')->exists($this->stripStoragePrefix($record->video_zh_path))) {
                    Storage::disk('public')->delete($this->stripStoragePrefix($record->video_zh_path));
                }
                $record->video_zh_path = $incomingPath;
                $record->video_zh_name = $attributes['video_zh_name'] ?? null;
            }
        }

        // 處理英文版影片
        if (!empty($attributes['video_en_path'])) {
            $incomingPath = $this->ensureStoragePath($attributes['video_en_path']);

            if ($incomingPath !== $record->video_en_path) {
                // 刪除舊影片
                if ($record->video_en_path && Storage::disk('public')->exists($this->stripStoragePrefix($record->video_en_path))) {
                    Storage::disk('public')->delete($this->stripStoragePrefix($record->video_en_path));
                }
                $record->video_en_path = $incomingPath;
                $record->video_en_name = $attributes['video_en_name'] ?? null;
            }
        }

        // 清除中文版影片
        if (!empty($attributes['video_zh_cleared']) && empty($attributes['video_zh_path'])) {
            if ($record->video_zh_path && Storage::disk('public')->exists($this->stripStoragePrefix($record->video_zh_path))) {
                Storage::disk('public')->delete($this->stripStoragePrefix($record->video_zh_path));
            }
            $record->video_zh_path = null;
            $record->video_zh_name = null;
        }

        // 清除英文版影片
        if (!empty($attributes['video_en_cleared']) && empty($attributes['video_en_path'])) {
            if ($record->video_en_path && Storage::disk('public')->exists($this->stripStoragePrefix($record->video_en_path))) {
                Storage::disk('public')->delete($this->stripStoragePrefix($record->video_en_path));
            }
            $record->video_en_path = null;
            $record->video_en_name = null;
        }

        $record->save();
    }

    /**
     * 確保路徑帶有 /storage/ 前綴
     * 例如：tmp/videos/xxx.mp4 → /storage/tmp/videos/xxx.mp4
     * 例如：/storage/tmp/videos/xxx.mp4 → /storage/tmp/videos/xxx.mp4（不變）
     * 例如：http://domain/storage/tmp/videos/xxx.mp4 → /storage/tmp/videos/xxx.mp4
     */
    protected function ensureStoragePath(string $path): string
    {
        // 處理完整 URL
        if (str_starts_with($path, 'http')) {
            $parsed = parse_url($path);
            $path = $parsed['path'] ?? $path;
        }

        // 已經有 /storage/ 前綴，直接回傳
        if (str_starts_with($path, '/storage/')) {
            return $path;
        }

        // 去掉開頭的 /
        $path = ltrim($path, '/');

        return '/storage/' . $path;
    }

    /**
     * 去掉 /storage/ 前綴（用於 Storage::disk('public') 操作）
     * 例如：/storage/tmp/videos/xxx.mp4 → tmp/videos/xxx.mp4
     */
    protected function stripStoragePrefix(string $path): string
    {
        return preg_replace('#^/?storage/#', '', $path);
    }

    /**
     * 更新排序
     */
    public function updateSort(array $items)
    {
        foreach ($items as $item) {
            $this->model->where('id', $item['id'])->update(['sort' => $item['sort']]);
        }
    }
}
