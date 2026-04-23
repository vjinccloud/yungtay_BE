<?php
// app/Listeners/LogDataUpdate.php

namespace App\Listeners;

use App\Events\DataUpdated;
use App\Models\OperationLog;
use App\Models\Drama;

class LogDataUpdate
{
    public function handle(DataUpdated $event): void
    {
        $m = $event->model;
        $details = $m->toArray();
        if ($m instanceof Drama && isset($m->episodes_by_season)) {
            $details['episodes_by_season'] = $m->episodes_by_season;
        }
        OperationLog::create([
            'created_by'      => auth('admin')->id(),
            'action_type'     => $m->event_type(),
            'message'         => '[編輯]' . $m->event_title,
            'ip_address'      => request()->ip(),
            'attachable_type' => get_class($m),
            'attachable_id'   => $m->id,
            'details'         => json_encode($details, JSON_UNESCAPED_UNICODE),
        ]);
    }
}
