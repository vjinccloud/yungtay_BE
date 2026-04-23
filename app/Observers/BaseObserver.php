<?php

namespace App\Observers;

use Illuminate\Database\Eloquent\Model;
use App\Services\EventService;

abstract class BaseObserver
{
    protected $eventService;

    public function __construct()
    {
        $this->eventService = app(EventService::class);
    }

    /**
     * 模型建立後觸發
     */
    public function created(Model $model): void
    {
        $this->logEvent($model, $this->getCreatedAction());
    }

    /**
     * 模型更新後觸發
     */
    public function updated(Model $model): void
    {
        // 檢查是否為狀態切換
        if ($model->wasChanged('status') && $this->hasStatusField()) {
            $statusText = $model->status ? '啟用' : '停用';
            $this->logEvent($model, "{$statusText}{$this->getModelName()}");
        } else {
            $this->logEvent($model, $this->getUpdatedAction());
        }
    }

    /**
     * 模型刪除後觸發
     */
    public function deleted(Model $model): void
    {
        $this->logEvent($model, $this->getDeletedAction());
    }

    /**
     * 記錄事件日誌
     */
    protected function logEvent(Model $model, string $action): void
    {
        // 使用臨時屬性避免觸發 updated 事件
        $model->setAttribute('event_type', $action);
        $model->syncOriginal(['event_type']);

        // 根據動作類型調用對應的 EventService 方法
        if (strpos($action, '新增') !== false) {
            $this->eventService->fireDataCreated($model);
        } elseif (strpos($action, '編輯') !== false || strpos($action, '啟用') !== false || strpos($action, '停用') !== false) {
            $this->eventService->fireDataUpdated($model);
        } elseif (strpos($action, '刪除') !== false) {
            $this->eventService->fireDataDeleted($model);
        }
    }

    /**
     * 取得模型名稱（子類別需實作）
     */
    abstract protected function getModelName(): string;

    /**
     * 取得事件標題（子類別需實作）
     */
    abstract protected function getEventTitle(): string;

    /**
     * 取得建立動作名稱
     */
    protected function getCreatedAction(): string
    {
        return '新增' . $this->getModelName();
    }

    /**
     * 取得更新動作名稱
     */
    protected function getUpdatedAction(): string
    {
        return '編輯' . $this->getModelName();
    }

    /**
     * 取得刪除動作名稱
     */
    protected function getDeletedAction(): string
    {
        return '刪除' . $this->getModelName();
    }

    /**
     * 檢查模型是否有 status 欄位
     */
    protected function hasStatusField(): bool
    {
        return true; // 預設為 true，子類別可覆寫
    }
}