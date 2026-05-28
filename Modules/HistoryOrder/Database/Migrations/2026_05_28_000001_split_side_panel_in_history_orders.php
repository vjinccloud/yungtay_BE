<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * 把 cabin_specs.side_panel 單一字串（\n 分隔 4 行）拆成 4 個獨立 key：
     *   side_panel_left  / side_panel_right
     *   side_panel_rear  / side_panel_front
     * 舊資料 4 行以位置對應到新 4 個方位（line 0→左, 1→右, 2→後, 3→前）。
     */
    public function up(): void
    {
        DB::table('history_orders')->orderBy('id')->each(function ($row) {
            $specs = json_decode($row->cabin_specs ?? '', true);
            if (!is_array($specs) || !array_key_exists('side_panel', $specs)) {
                return;
            }

            $raw = $specs['side_panel'];
            $lines = is_array($raw)
                ? array_values($raw)
                : preg_split('/\r\n|\r|\n/', (string) $raw);

            $specs['side_panel_left']  = $lines[0] ?? null;
            $specs['side_panel_right'] = $lines[1] ?? null;
            $specs['side_panel_rear']  = $lines[2] ?? null;
            $specs['side_panel_front'] = $lines[3] ?? null;

            unset($specs['side_panel']);

            DB::table('history_orders')
                ->where('id', $row->id)
                ->update(['cabin_specs' => json_encode($specs, JSON_UNESCAPED_UNICODE)]);
        });
    }

    public function down(): void
    {
        DB::table('history_orders')->orderBy('id')->each(function ($row) {
            $specs = json_decode($row->cabin_specs ?? '', true);
            if (!is_array($specs)) {
                return;
            }

            $keys = [
                'side_panel_left',
                'side_panel_right',
                'side_panel_rear',
                'side_panel_front',
            ];

            $hasAny = false;
            foreach ($keys as $k) {
                if (array_key_exists($k, $specs)) {
                    $hasAny = true;
                    break;
                }
            }
            if (!$hasAny) {
                return;
            }

            $lines = [];
            foreach ($keys as $k) {
                if (isset($specs[$k]) && $specs[$k] !== '') {
                    $lines[] = $specs[$k];
                }
                unset($specs[$k]);
            }
            $specs['side_panel'] = implode("\n", $lines);

            DB::table('history_orders')
                ->where('id', $row->id)
                ->update(['cabin_specs' => json_encode($specs, JSON_UNESCAPED_UNICODE)]);
        });
    }
};
