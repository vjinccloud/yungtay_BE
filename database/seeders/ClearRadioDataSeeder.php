<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ClearRadioDataSeeder extends Seeder
{
    /**
     * 清除所有廣播相關資料
     *
     * 執行指令：php artisan db:seed --class=ClearRadioDataSeeder
     */
    public function run(): void
    {
        $this->command->info('開始清除廣播相關資料...');

        // 1. 清除觀看數記錄
        $viewLogsCount = DB::table('view_logs')
            ->where('content_type', 'radio')
            ->count();

        DB::table('view_logs')
            ->where('content_type', 'radio')
            ->delete();

        $this->command->info("✓ 已清除 {$viewLogsCount} 筆觀看記錄 (view_logs)");

        // 2. 清除觀看統計
        $viewStatsCount = DB::table('view_statistics')
            ->where('content_type', 'radio')
            ->count();

        DB::table('view_statistics')
            ->where('content_type', 'radio')
            ->delete();

        $this->command->info("✓ 已清除 {$viewStatsCount} 筆觀看統計 (view_statistics)");

        // 3. 清除人口統計
        if (DB::getSchemaBuilder()->hasTable('view_demographics')) {
            $demographicsCount = DB::table('view_demographics')
                ->where('content_type', 'radio')
                ->count();

            DB::table('view_demographics')
                ->where('content_type', 'radio')
                ->delete();

            $this->command->info("✓ 已清除 {$demographicsCount} 筆人口統計 (view_demographics)");
        }

        // 4. 清除收藏記錄
        $collectionsCount = DB::table('user_collections')
            ->where('content_type', 'radio')
            ->count();

        DB::table('user_collections')
            ->where('content_type', 'radio')
            ->delete();

        $this->command->info("✓ 已清除 {$collectionsCount} 筆收藏記錄 (user_collections)");

        // 5. 清除廣播圖片（先刪除實體檔案，再刪除資料庫記錄）
        $images = DB::table('images')
            ->where('attachable_type', 'App\\Models\\Radio')
            ->get();

        $imagesCount = $images->count();
        $deletedFilesCount = 0;

        foreach ($images as $image) {
            if ($image->path && Storage::disk('public')->exists($image->path)) {
                Storage::disk('public')->delete($image->path);
                $deletedFilesCount++;
            }
        }

        DB::table('images')
            ->where('attachable_type', 'App\\Models\\Radio')
            ->delete();

        $this->command->info("✓ 已清除 {$imagesCount} 筆圖片記錄 (images)");
        $this->command->info("✓ 已刪除 {$deletedFilesCount} 個圖片檔案");

        // 6. 清除廣播資料（使用 delete 避免外鍵約束問題）
        $radiosCount = DB::table('radios')->count();
        DB::table('radios')->delete();
        $this->command->info("✓ 已清除 {$radiosCount} 筆廣播資料 (radios)");

        // 7. 清除實體檔案
        $this->command->info('正在清除實體檔案...');

        // 清除廣播音檔目錄
        if (Storage::disk('public')->exists('radios')) {
            Storage::disk('public')->deleteDirectory('radios');
            $this->command->info('✓ 已清除 storage/app/public/radios 目錄');
        }

        // 清除廣播圖片目錄（如果有獨立目錄）
        if (Storage::disk('public')->exists('images/radios')) {
            Storage::disk('public')->deleteDirectory('images/radios');
            $this->command->info('✓ 已清除 storage/app/public/images/radios 目錄');
        }

        $this->command->info('');
        $this->command->info('🎉 廣播相關資料清除完成！');
    }
}
