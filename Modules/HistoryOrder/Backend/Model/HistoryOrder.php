<?php

namespace Modules\HistoryOrder\Backend\Model;

use Illuminate\Database\Eloquent\Model;

class HistoryOrder extends Model
{
    protected $table = 'history_orders';

    protected $fillable = [
        'order_name',
        'customer_name',
        'contact_phone',
        'contact_email',
        'case_area',
        'elevator_count',
        'elevator_spec',
        'series_model',
        'cabin_specs',
        'entrance_specs',
        'elevator_image',
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
            'ceiling'          => ['label' => '天井',   'icon' => '/images/icon_01.png'],
            'door_panel'       => ['label' => '門板',   'icon' => '/images/icon_03.png'],
            'side_panel_left'  => ['label' => '左側板', 'icon' => '/images/icon_03.png'],
            'side_panel_right' => ['label' => '右側板', 'icon' => '/images/icon_03.png'],
            'side_panel_back'  => ['label' => '後側板', 'icon' => '/images/icon_03.png'],
            'side_panel_front' => ['label' => '前側板', 'icon' => '/images/icon_03.png'],
            'floor'            => ['label' => '地板',   'icon' => '/images/icon_04.png'],
            'control_panel'    => ['label' => '操作盤', 'icon' => '/images/icon_05.png'],
            'handrail'         => ['label' => '扶手',   'icon' => '/images/icon_06.png'],
            'trim'             => ['label' => '飾條',   'icon' => '/images/icon_08.png'],
        ];
    }

    /**
     * 出入口規格欄位定義
     */
    public static function getEntranceSpecFields(): array
    {
        return [
            'door_panel'    => ['label' => '門板',   'icon' => '/images/icon_02.png'],
            'door_frame'    => ['label' => '門框',   'icon' => '/images/icon_09.png'],
            'lantern'       => ['label' => '廳燈',   'icon' => '/images/icon_11.png'],
            'control_panel' => ['label' => '操作盤', 'icon' => '/images/icon_12.png'],
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
