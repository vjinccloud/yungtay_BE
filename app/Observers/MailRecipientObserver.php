<?php

namespace App\Observers;

use App\Models\MailRecipient;
use App\Observers\BaseObserver;
use Illuminate\Database\Eloquent\Model;

class MailRecipientObserver extends BaseObserver
{
    /**
     * 取得模型名稱
     */
    protected function getModelName(): string
    {
        return '收件信箱';
    }

    /**
     * 取得事件標題
     */
    protected function getEventTitle(): string
    {
        return '收件信箱管理';
    }

    /**
     * 模型更新後觸發（覆寫以處理狀態切換）
     */
    public function updated(Model $model): void
    {
        // 檢查是否為狀態切換
        if ($model->wasChanged('status')) {
            $statusText = $model->status ? '啟用' : '停用';
            // 暫時設定 event_status 屬性，讓 Model 的 getter 能識別
            $model->event_status = $statusText;
        }
        
        // 呼叫父類別的 updated 方法
        parent::updated($model);
    }
}