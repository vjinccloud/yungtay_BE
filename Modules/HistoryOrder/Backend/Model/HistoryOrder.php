<?php

namespace Modules\HistoryOrder\Backend\Model;

use Illuminate\Database\Eloquent\Model;

class HistoryOrder extends Model
{
    protected $table = 'history_orders';

    protected $fillable = [
        'order_name',
        'customer_name',
        'project_name',
        'construction_location',
        'customer_contact_name',
        'customer_contact_email',
        'series_model',
        'cabin_specs',
        'entrance_specs',
        'elevator_image',
        'sales_name',
        'sales_email',
        'sales_phone',
        'note',
    ];

    protected $casts = [
        'cabin_specs'    => 'array',
        'entrance_specs' => 'array',
    ];

    /**
     * 車廂規格欄位定義
     */
    public static function getCabinSpecFields(): array
    {
        return [
            'ceiling'       => ['label' => '天井',   'icon' => 'fa-lightbulb'],
            'door_panel'    => ['label' => '門板',   'icon' => 'fa-door-closed'],
            'side_panel'    => ['label' => '側板',   'icon' => 'fa-columns'],
            'floor'         => ['label' => '地板',   'icon' => 'fa-square'],
            'control_panel' => ['label' => '操作盤', 'icon' => 'fa-th-list'],
            'handrail'      => ['label' => '扶手',   'icon' => 'fa-grip-lines'],
            'trim'          => ['label' => '飾條',   'icon' => 'fa-minus'],
        ];
    }

    /**
     * 出入口規格欄位定義
     */
    public static function getEntranceSpecFields(): array
    {
        return [
            'door_panel'    => ['label' => '門板',   'icon' => 'fa-door-open'],
            'door_frame'    => ['label' => '門框',   'icon' => 'fa-border-style'],
            'door_column'   => ['label' => '門柱',   'icon' => 'fa-grip-vertical'],
            'floor'         => ['label' => '地板',   'icon' => 'fa-square'],
            'control_panel' => ['label' => '操作盤', 'icon' => 'fa-th-list'],
        ];
    }

    /**
     * 系列型號選項
     */
    public static function getSeriesModelOptions(): array
    {
        return self::select('series_model')
            ->whereNotNull('series_model')
            ->distinct()
            ->orderBy('series_model')
            ->pluck('series_model')
            ->map(fn ($v) => ['value' => $v, 'label' => $v])
            ->toArray();
    }
}
