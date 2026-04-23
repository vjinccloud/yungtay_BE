<?php

namespace Modules\IntroVideo\Backend\Repository;

use App\Repositories\BaseRepository;
use Illuminate\Support\Facades\Storage;
use Modules\IntroVideo\Model\IntroVideo;

/**
 * IntroVideo 片頭動畫 - Repository
 */
class IntroVideoRepository extends BaseRepository
{
    public function __construct(IntroVideo $model)
    {
        parent::__construct($model);
    }

    /**
     * 取得設定（自動建立）
     */
    public function getSetting()
    {
        return $this->model->firstOrCreate(['id' => 1]);
    }

    /**
     * 取得詳情（編輯用）
     */
    public function getDetail()
    {
        $item = $this->getSetting();

        return [
            'id' => $item->id,
            'video_path' => $item->video_path,
            'video_url' => $item->video_url,
            'video_original_name' => $item->video_original_name,
            'video_size' => $item->video_size,
            'video_size_formatted' => $item->video_size_formatted,
            'is_active' => $item->is_active,
        ];
    }

    /**
     * 儲存設定
     */
    public function saveSetting(array $attributes, $videoFile = null)
    {
        // 取得或建立設定
        $record = $this->getSetting();

        // 處理影片上傳
        if ($videoFile) {
            // 刪除舊影片
            if ($record->video_path) {
                Storage::disk('public')->delete($record->video_path);
            }

            // 儲存新影片
            $path = $videoFile->store('intro-video', 'public');
            
            $attributes['video_path'] = $path;
            $attributes['video_original_name'] = $videoFile->getClientOriginalName();
            $attributes['video_size'] = $videoFile->getSize();
        }

        // 處理刪除影片
        if (isset($attributes['remove_video']) && $attributes['remove_video']) {
            if ($record->video_path) {
                Storage::disk('public')->delete($record->video_path);
            }
            $attributes['video_path'] = null;
            $attributes['video_original_name'] = null;
            $attributes['video_size'] = null;
        }
        unset($attributes['remove_video']);

        // 設定更新者
        $attributes['updated_by'] = auth('admin')->id();

        // 更新資料
        $record->update($attributes);

        return $record->fresh();
    }

    /**
     * 刪除影片
     */
    public function deleteVideo()
    {
        $record = $this->getSetting();
        
        if ($record->video_path) {
            Storage::disk('public')->delete($record->video_path);
            
            $record->update([
                'video_path' => null,
                'video_original_name' => null,
                'video_size' => null,
                'updated_by' => auth('admin')->id(),
            ]);
        }

        return $record->fresh();
    }
}
