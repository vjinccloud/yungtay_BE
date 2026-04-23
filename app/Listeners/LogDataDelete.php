<?php

namespace App\Listeners;

use App\Events\DataDeleted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use App\Models\OperationLog;

class LogDataDelete
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(DataDeleted $event): void
    {
        $user = auth('admin')->user();
        $model = $event->model;
        $model->getRelationsLoaded();
        $mappedData = [
            'created_by' => $user->id,
            'action_type' => 'Delete',
            'message' =>  '[刪除]'.$model->event_title,
            'ip_address' => request()->ip(),
            'attachable_type' => get_class($model),
            'attachable_id' =>  $model->id,
            'details' => json_encode($model->toArray()),
        ];
        OperationLog::create($mappedData);
    }
}
