<?php
// app/Listeners/LogDataUpdate.php

namespace App\Listeners;

use App\Events\DataSort;
use App\Models\OperationLog;

class LogDataSort
{
    public function handle(DataSort $event): void
    {
        $m = $event->sortData;

        OperationLog::create([
            'created_by'      => auth('admin')->id(),
            'action_type'     => 'Edit',
            'message'         => '[編輯]' . $m->event_title,
            'ip_address'      => request()->ip(),
            'attachable_type' => 'Category Sort',
            'attachable_id'   => null,
            'details' => $sortData->details ?? null,
        ]);
    }
}
