<?php
// app/Listeners/LogDataCreated.php

namespace App\Listeners;

use App\Events\DataCreated;
use App\Models\OperationLog;
use App\Models\Drama;
class LogDataCreated
{
    public function handle(DataCreated $event): void
    {
        $m = $event->model;
        $details = $m->toArray();
        if ($m instanceof Drama && isset($m->episodes_by_season)) {
            $details['episodes_by_season'] = $m->episodes_by_season;
        }
        OperationLog::create([
            'created_by'      => auth('admin')->id(),
            'action_type'     => $m->event_type(),   // event_type 只會回傳「主分類／子分類」
            'message'         => '[新增]' . $m->event_title,  // event_title 只會回傳「主分類：XXX／子分類：YYY」
            'ip_address'      => request()->ip(),
            'attachable_type' => get_class($m),
            'attachable_id'   => $m->id,
            'details'         => json_encode($details, JSON_UNESCAPED_UNICODE),
        ]);
    }
}
