<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * 移除 entrance_specs.door_column（門檻）欄位。
     * down() 無法復原已刪除的值，僅補回 null key 以利舊邏輯兼容。
     */
    public function up(): void
    {
        DB::table('history_orders')->orderBy('id')->each(function ($row) {
            $specs = json_decode($row->entrance_specs ?? '', true);
            if (!is_array($specs) || !array_key_exists('door_column', $specs)) {
                return;
            }

            unset($specs['door_column']);

            DB::table('history_orders')
                ->where('id', $row->id)
                ->update(['entrance_specs' => json_encode($specs, JSON_UNESCAPED_UNICODE)]);
        });
    }

    public function down(): void
    {
        DB::table('history_orders')->orderBy('id')->each(function ($row) {
            $specs = json_decode($row->entrance_specs ?? '', true);
            if (!is_array($specs) || array_key_exists('door_column', $specs)) {
                return;
            }

            $specs['door_column'] = null;

            DB::table('history_orders')
                ->where('id', $row->id)
                ->update(['entrance_specs' => json_encode($specs, JSON_UNESCAPED_UNICODE)]);
        });
    }
};
