<?php

namespace App\Traits;

use App\Models\AdminUser;
use App\Models\ImageManagement;
use Illuminate\Database\Eloquent\Casts\Attribute;

trait BaseModelTrait{
    public $event_status_title;

    /**
     * Boot the trait
     * 自動設定建立者和修改者
     */
    protected static function bootBaseModelTrait()
    {
        static::creating(function ($model) {
            // 檢查表是否有 created_by 和 updated_by 欄位
            $hasCreatedBy = \Schema::hasColumn($model->getTable(), 'created_by');
            $hasUpdatedBy = \Schema::hasColumn($model->getTable(), 'updated_by');
            
            if (auth('admin')->check()) {
                if ($hasCreatedBy) {
                    $model->created_by = auth('admin')->id();
                }
                if ($hasUpdatedBy) {
                    $model->updated_by = auth('admin')->id();
                }
            }
        });

        static::updating(function ($model) {
            // 檢查表是否有 updated_by 欄位
            $hasUpdatedBy = \Schema::hasColumn($model->getTable(), 'updated_by');
            
            if ($hasUpdatedBy && auth('admin')->check()) {
                $model->updated_by = auth('admin')->id();
            }
        });
    }

    /**
     * 确保多个关联已加载，如果没有，则加载它们。
     *
     * @param array $relations 要加载的关联名称数组
     * @return void
     */
    public function ensureRelationsLoaded(array $relations)
    {
        foreach ($relations as $relation) {
            if (!$this->relationLoaded($relation)) {
                $this->load($relation);
            }
        }
    }


    public function getRelationsLoaded()
    {
        return array_keys($this->getRelations());
    }


    public function event_type()
    {
        return $this->wasRecentlyCreated ? 'Add':'Edit' ;
    }

    // ==================== 共用關聯方法 ====================

    /**
     * 建立者關聯（統一命名為 creator）
     */
    public function creator()
    {
        return $this->belongsTo(AdminUser::class, 'created_by');
    }

    /**
     * 更新者關聯（統一命名為 updater）
     */
    public function updater()
    {
        return $this->belongsTo(AdminUser::class, 'updated_by');
    }

    /**
     * 舊版建立者關聯（相容性）
     * @deprecated 請使用 creator()
     */
    public function createdBy()
    {
        return $this->creator();
    }

    /**
     * 舊版更新者關聯（相容性）
     * @deprecated 請使用 updater()
     */
    public function updatedBy()
    {
        return $this->updater();
    }

    /**
     * 舊版建立者關聯（相容性）
     * @deprecated 請使用 creator()
     */
    public function created_user()
    {
        return $this->creator();
    }

    /**
     * 舊版更新者關聯（相容性）
     * @deprecated 請使用 updater()
     */
    public function updated_user()
    {
        return $this->updater();
    }

    // ==================== 共用 Scopes ====================

    /**
     * 啟用狀態篩選
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Status 欄位篩選（給 Category 等使用 status 欄位的模型）
     * @param $query
     * @param bool $status 預設為 true，可傳入 false 查詢停用的資料
     * @return mixed
     */
    public function scopeStatus($query, $status = true)
    {
        return $query->where('status', $status);
    }

    /**
     * 依排序欄位排序
     */
    public function scopeOrdered($query)
    {
        if ($this->getTable() === 'categories') {
            return $query->orderBy('seq');
        }
        return $query->orderBy('sort_order');
    }

    /**
     * 通用搜尋篩選（針對多語欄位）
     * @param array $fields 要搜尋的欄位名稱
     */
    public function scopeSearchTranslatable($query, $search, array $fields)
    {
        if (!$search) {
            return $query;
        }

        return $query->where(function ($q) use ($search, $fields) {
            foreach ($fields as $field) {
                $q->orWhere("{$field}->zh_TW", 'like', "%{$search}%")
                  ->orWhere("{$field}->en", 'like', "%{$search}%");
            }
        });
    }

    // ==================== 圖片關聯輔助方法 ====================

    /**
     * 建立圖片關聯（多型）
     * @param string $imageType 圖片類型
     */
    protected function morphImage($imageType)
    {
        return $this->morphOne(ImageManagement::class, 'attachable')
                    ->where('image_type', $imageType);
    }

    /**
     * 所有圖片關聯
     */
    public function images()
    {
        return $this->morphMany(ImageManagement::class, 'attachable');
    }

    // ==================== 屬性輔助方法 ====================

    /**
     * 預設的 eventTitle 實作
     * 子類別可以覆寫此方法來自訂
     */
    protected function getDefaultEventTitle(): string
    {
        // 取得 Model 名稱的中文對應
        $modelName = class_basename($this);
        $modelTitles = [
            'Drama' => '影音',
            'Program' => '節目',
            'News' => '最新消息',
            'Radio' => '廣播',
            'Live' => '直播',
            'Category' => '分類',
            'AdminUser' => '管理員',
            'Role' => '角色',
            'DramaTheme' => '影音主題',
            'ProgramTheme' => '節目主題',
            'WebsiteInfo' => '網站設定',
            'ModuleDescription' => '模組說明',
            'Banner' => '首頁輪播',
            'Article' => '新聞',
        ];

        $prefix = $modelTitles[$modelName] ?? $modelName;

        // 如果有 title 欄位，使用 title
        if (property_exists($this, 'translatable') && in_array('title', $this->translatable)) {
            return $prefix . '：' . $this->getTranslation('title', 'zh_TW');
        }

        // 如果有 name 欄位，使用 name
        if (property_exists($this, 'translatable') && in_array('name', $this->translatable)) {
            return $prefix . '：' . $this->getTranslation('name', 'zh_TW');
        }

        // 否則回傳預設
        return $prefix . '：#' . $this->id;
    }

    /**
     * 檢查是否有多語欄位
     */
    protected function hasTranslatableField($field): bool
    {
        return property_exists($this, 'translatable') && 
               in_array($field, $this->translatable);
    }

    // ==================== 共用屬性存取器 ====================

    /**
     * 取得縮圖 URL（用於集數類模型）
     * 
     * @return string|null
     */
    public function getThumbnailUrlAttribute()
    {
        // 確保關聯已載入，避免 N+1
        if ($this->relationLoaded('thumbnail') && $this->thumbnail) {
            return asset('storage/' . $this->thumbnail->path);
        }
        
        // 如果關聯未載入，使用快取的方式查詢
        if (!$this->relationLoaded('thumbnail')) {
            $thumbnail = $this->thumbnail()->first();
            if ($thumbnail) {
                return asset('storage/' . $thumbnail->path);
            }
        }
        
        return null;
    }

}
