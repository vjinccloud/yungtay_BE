<?php

namespace App\Repositories;

use App\Models\Live;
use App\Repositories\BaseRepository;
use App\Repositories\ImageRepository;
use App\Services\Thumbnail\Strategies\YouTubeThumbnailStrategy;
use Illuminate\Support\Facades\DB;
use App\Traits\CommonTrait;
use Illuminate\Support\Facades\Storage;

class LiveRepository extends BaseRepository
{
    // 覆寫排序設定（Live 表使用 sort_order 欄位）
    protected $sortColumn = 'sort_order';
    protected $sortGap = 1;  // 改為連續排序，避免跳號
    
    public function __construct(
        Live $live,
        private ImageRepository $imgRepository,
        private YouTubeThumbnailStrategy $thumbnailStrategy
    ) {
        parent::__construct($live);
    }

    public function save(array $attributes = [], $id = null)
    {
        // 判斷是否為新增，並於未提供 sort_order 時自動接到列表最後
        $isCreating = is_null($id);
        if ($isCreating && empty($attributes['sort_order'])) {
            // 使用 Base 的方法取得下一個排序值
            $attributes['sort_order'] = $this->getNextSortOrder();
        }

        // 1. 儲存主資料
        $live = parent::save($attributes, $id);

        // 2. 如果有 YouTube URL，生成縮圖
        if (!empty($attributes['youtube_url'])) {
            try {
                $this->generateYouTubeThumbnail($live, $attributes['youtube_url']);
            } catch (\Exception $e) {
                // 記錄錯誤但不中斷流程
                \Log::error('Live YouTube thumbnail generation failed', [
                    'live_id' => $live->id,
                    'youtube_url' => $attributes['youtube_url'],
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $live;
    }


    public function paginate($perPage, $sortColumn = 'sort_order', $sortDirection = 'asc', $filters = [])
    {
        return $this->model->orderBy($sortColumn, $sortDirection)
            ->filter($filters)
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn ($live) => [
                'id' => $live->id,
                // 取出各語系的標題
                'title_zh' => $live->getTranslation('title', 'zh_TW'),
                'title_en' => $live->getTranslation('title', 'en'),
                'youtube_url' => $live->youtube_url,
                'is_active' => (bool) $live->is_active,
                'sort_order' => $live->sort_order,
                'thumbnail' => $live->thumbnail,
                'created_at' => $live->created_at->toDateTimeString(),
                'updated_at' => $live->updated_at->toDateTimeString(),
            ]);
    }

    /**
     * 前台取得所有啟用的直播列表
     *
     * @return \Illuminate\Support\Collection
     */
    public function getActiveLives()
    {
        return $this->model
            ->with('images')  // 預載入 images 關聯
            ->active()
            ->orderBy('sort_order', 'asc')
            ->orderBy('id', 'desc')
            ->get()
            ->map(function ($live) {
                return [
                    'id' => $live->id,
                    'title' => $live->getTranslation('title', app()->getLocale()),
                    'youtube_url' => $live->youtube_url,
                    'thumbnail' => $live->thumbnail,  // 現在會正確取得縮圖
                    'sort_order' => $live->sort_order,
                ];
            });
    }

    /**
     * 取得編輯資料
     */
    public function getEditData($id)
    {
        $live = $this->find($id);
        
        if (!$live) {
            return null;
        }

        return [
            'id' => $live->id,
            'title' => [
                'zh_TW' => $live->getTranslation('title', 'zh_TW'),
                'en' => $live->getTranslation('title', 'en'),
            ],
            'youtube_url' => $live->youtube_url,
            'is_active' => (bool) $live->is_active,
            'sort_order' => $live->sort_order,
            'thumbnail' => $live->thumbnail,
        ];
    }

    /**
     * 首頁取得直播列表（按修改時間排序）
     * 
     * @param int $limit 限制數量
     * @return \Illuminate\Support\Collection
     */
    public function getForHomePage(int $limit = 5)
    {
        $locale = app()->getLocale();
        $index = 0;
        
        return $this->model
            ->active()
            ->orderBy('updated_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($live) use ($locale, &$index) {
                // 根據位置決定使用哪種品質的 YouTube 縮圖
                $thumbnail = $live->thumbnail;
                if ($live->youtube_url) {
                    // 統一使用高解析度 16:9 (1280x720) 確保清晰度
                    $thumbnail = $this->getYouTubeThumbnail($live->youtube_url, 'maxresdefault') ?? $live->thumbnail;
                }
                $index++;
                
                return [
                    'id' => $live->id,
                    'title' => $live->getTranslation('title', $locale),
                    'youtube_url' => $live->youtube_url,
                    'thumbnail' => $thumbnail,
                    'updated_at' => $live->updated_at,
                ];
            });
    }
    
    

    /**
     * 生成 YouTube 縮圖 (使用YouTubeThumbnailStrategy)
     */
    protected function generateYouTubeThumbnail($live, $youtubeUrl)
    {
        // 1) 先刪除舊縮圖（包含 Storage 實體檔）
        $oldImages = $live->images()->where('image_type', 'video_thumbnail')->get();
        foreach ($oldImages as $old) {
            if (!empty($old->path) && Storage::disk('public')->exists($old->path)) {
                Storage::disk('public')->delete($old->path);
            }
            $old->delete();
        }

        // 2) 建立一個明確定義屬性的 subject（避免動態屬性 deprecate）
        $fakeEpisode = new class($live->id, $youtubeUrl) {
            public int $id;
            public string $video_type = 'youtube';
            public string $youtube_url;
            // 覆蓋 Strategy 的存檔位置與命名前綴
            public string $storage_subdir = 'lives/thumbnails';
            public string $filename_prefix;

            public function __construct(int $id, string $url)
            {
                $this->id = $id;
                $this->youtube_url = $url;
                $this->filename_prefix = "live_{$id}_thumb";
            }
        };

        // 使用YouTubeThumbnailStrategy生成縮圖
        if ($this->thumbnailStrategy->supports($fakeEpisode)) {
            $thumbnailPath = $this->thumbnailStrategy->generate($fakeEpisode);
            
            if ($thumbnailPath) {
                // 儲存縮圖記錄 (Strategy已經處理圖片下載和處理)
                \App\Models\ImageManagement::create([
                    'attachable_type' => get_class($live),
                    'attachable_id' => $live->id,
                    'image_type' => 'video_thumbnail',
                    'path' => $thumbnailPath,
                    'filename' => basename($thumbnailPath),
                    'ext' => 'jpg',
                    'title' => $live->getTranslation('title', 'zh_TW') . ' 縮圖',
                    'seq' => 1
                ]);

                // 若 lives 表有 thumbnail 欄位，順手更新，方便列表直接使用
                try {
                    $live->thumbnail = $thumbnailPath;
                    $live->save();
                } catch (\Throwable $e) {
                    // 若無欄位或不需更新，忽略
                }

                \Log::info('Live YouTube 縮圖生成成功', [
                    'live_id' => $live->id,
                    'thumbnail_path' => $thumbnailPath
                ]);
            }
        }
    }
}