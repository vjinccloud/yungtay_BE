<?php

namespace App\Listeners;

use App\Events\AdminLoggedIn;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use App\Models\OperationLog;

class LogAdminLogin
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        
    }

    /**
     * Handle the event.
     */
    public function handle(AdminLoggedIn $event): void
    {
        $model = $event->model->user();
        OperationLog::create([
            'created_by' => $model->id,
            'action_type' => 'Login',
            'message' =>  "[登入]管理系統",
            'ip_address' => request()->ip(),
            'attachable_type' => get_class($model),
            'attachable_id' =>  $model->id,
            'details' => json_encode($model->toArray()),
        ]);
    }
}
