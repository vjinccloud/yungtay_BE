<?php
namespace App\Services;

use App\Events\AdminLoggedIn;
use App\Events\DataUpdated;
use App\Events\DataDeleted;
use App\Events\DataSort;
use App\Events\DataCreated;

class EventService {
    public function fireUserLoggedIn($admin)
    {
        event(new AdminLoggedIn($admin));
    }

    public function fireDataCreated($model)
    {
        // 只有後台管理員操作才記錄操作紀錄
        // 前台會員操作（沒有 created_by）不記錄
        if (isset($model->created_by) && $model->created_by) {
            event(new DataCreated($model));
        }
    }

    public function fireDataUpdated($model)
    {
        // 只有後台管理員操作才記錄操作紀錄
        // 前台會員操作（沒有 updated_by）不記錄
        if (isset($model->updated_by) && $model->updated_by) {
            event(new DataUpdated($model));
        }
    }

    public function fireDataDeleted($model)
    {
        event(new DataDeleted($model));
    }

    public function fireDataChangeStatus($model)
    {
        $eventTitle = $model->event_status_title ? $model->event_status_title : '資料狀態變更';
        $model->event_title = $eventTitle;
        event(new DataUpdated($model));
    }

    public function fireDataSort($model,$ids,$title=null)
    {
        $virtualModel = new \stdClass();
        if (!isset($title) && $model && $model->type) {
            $title = $model::getTypeTitle($model->type);
        }
        $virtualModel->details = json_encode([
            'operation' => 'sort',
            'ids' => $ids,
            'category_title' => $title,
            'affected_count' => count($ids),
            'timestamp' => now()->toDateTimeString()
        ], JSON_UNESCAPED_UNICODE);
        $virtualModel->event_title = "{$title}排序調整";
        event(new DataSort($virtualModel));
    }

}
