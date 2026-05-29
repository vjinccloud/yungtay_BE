<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * 把 JSON 內欄位 key 改名：
     *   cabin_specs.side_panel_rear  → cabin_specs.side_panel_back
     *   entrance_specs.floor         → entrance_specs.lantern
     */
    public function up(): void
    {
        DB::table('history_orders')->orderBy('id')->each(function ($row) {
            $changed = false;
            $cabin = json_decode($row->cabin_specs ?? '', true);
            $entrance = json_decode($row->entrance_specs ?? '', true);

            if (is_array($cabin) && array_key_exists('side_panel_rear', $cabin)) {
                $cabin['side_panel_back'] = $cabin['side_panel_rear'];
                unset($cabin['side_panel_rear']);
                $changed = true;
            }

            if (is_array($entrance) && array_key_exists('floor', $entrance)) {
                $entrance['lantern'] = $entrance['floor'];
                unset($entrance['floor']);
                $changed = true;
            }

            if (!$changed) {
                return;
            }

            DB::table('history_orders')
                ->where('id', $row->id)
                ->update([
                    'cabin_specs'    => is_array($cabin) ? json_encode($cabin, JSON_UNESCAPED_UNICODE) : $row->cabin_specs,
                    'entrance_specs' => is_array($entrance) ? json_encode($entrance, JSON_UNESCAPED_UNICODE) : $row->entrance_specs,
                ]);
        });
    }

    public function down(): void
    {
        DB::table('history_orders')->orderBy('id')->each(function ($row) {
            $changed = false;
            $cabin = json_decode($row->cabin_specs ?? '', true);
            $entrance = json_decode($row->entrance_specs ?? '', true);

            if (is_array($cabin) && array_key_exists('side_panel_back', $cabin)) {
                $cabin['side_panel_rear'] = $cabin['side_panel_back'];
                unset($cabin['side_panel_back']);
                $changed = true;
            }

            if (is_array($entrance) && array_key_exists('lantern', $entrance)) {
                $entrance['floor'] = $entrance['lantern'];
                unset($entrance['lantern']);
                $changed = true;
            }

            if (!$changed) {
                return;
            }

            DB::table('history_orders')
                ->where('id', $row->id)
                ->update([
                    'cabin_specs'    => is_array($cabin) ? json_encode($cabin, JSON_UNESCAPED_UNICODE) : $row->cabin_specs,
                    'entrance_specs' => is_array($entrance) ? json_encode($entrance, JSON_UNESCAPED_UNICODE) : $row->entrance_specs,
                ]);
        });
    }
};
