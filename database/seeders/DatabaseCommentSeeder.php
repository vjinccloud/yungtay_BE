<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseCommentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->info('開始設定資料表備註...');

        // 系統核心表
        $this->setTableComment('admin_users', '後台管理員');
        $this->setTableComment('admin_menu', '後台選單');
        $this->setTableComment('users', '會員資料');
        $this->setTableComment('password_reset_tokens', '密碼重設令牌');

        $this->setTableComment('jobs', '佇列任務');
        $this->setTableComment('job_batches', '批次任務');
        $this->setTableComment('failed_jobs', '失敗任務');

        // 權限管理
        $this->setTableComment('permissions', '權限設定');
        $this->setTableComment('roles', '角色設定');
        $this->setTableComment('model_has_permissions', '模型權限關聯');
        $this->setTableComment('model_has_roles', '模型角色關聯');
        $this->setTableComment('role_has_permissions', '角色權限關聯');

        // 內容管理
        $this->setTableComment('dramas', '影音管理');
        $this->setTableComment('drama_episodes', '影音集數');
        $this->setTableComment('programs', '節目管理');
        $this->setTableComment('program_episodes', '節目集數');
        $this->setTableComment('articles', '新聞文章');
        $this->setTableComment('news', '最新消息');
        $this->setTableComment('lives', '直播頻道');
        $this->setTableComment('radios', '廣播節目');
        $this->setTableComment('banners', '首頁輪播');

        // 分類與主題
        $this->setTableComment('categories', '分類管理');
        $this->setTableComment('subcategories', '子分類（已棄用，整合至categories）');
        $this->setTableComment('themes', '主題管理');
        $this->setTableComment('drama_themes', '影音主題');
        $this->setTableComment('drama_theme_relations', '影音主題關聯表');
        $this->setTableComment('program_themes', '節目主題');
        $this->setTableComment('program_theme_relations', '節目主題關聯表');
        // 多媒體
        $this->setTableComment('images', '圖片管理');


        // 觀看統計
        $this->setTableComment('view_logs', '觀看記錄（原始數據）');
        $this->setTableComment('view_statistics', '觀看統計（聚合數據）');
        $this->setTableComment('view_demographics', '觀看人口統計');
        $this->setTableComment('view_rankings', '觀看排行快取');

        // 系統設定
        $this->setTableComment('website_infos', '網站設定');
        $this->setTableComment('module_descriptions', '模組說明');


        // 地區資料
        $this->setTableComment('list_city', '縣市列表');
        $this->setTableComment('list_area', '區域列表');

        // Laravel 預設表
        $this->setTableComment('migrations', '資料庫遷移記錄');
        $this->setTableComment('personal_access_tokens', '個人存取令牌');

        // 會員相關
        $this->setTableComment('email_verifications', 'Email驗證記錄');
        $this->setTableComment('user_collections', '會員收藏');
        $this->setTableComment('social_accounts', '第三方登入帳號');

        // 會員通知系統
        $this->setTableComment('member_notifications', '會員通知主表');
        $this->setTableComment('member_notification_recipients', '會員通知接收者記錄');

        // 收件信箱管理
        $this->setTableComment('mail_types', '收件類型管理');
        $this->setTableComment('mail_recipients', '收件信箱管理');

        $this->info('資料表備註設定完成！');
    }

    /**
     * 設定資料表備註（會先檢查是否已有備註）
     *
     * @param string $table
     * @param string $comment
     */
    private function setTableComment(string $table, string $comment): void
    {
        try {
            // 檢查資料表是否存在
            if (!DB::getSchemaBuilder()->hasTable($table)) {
                $this->warn("資料表 {$table} 不存在，跳過...");
                return;
            }

            // 取得目前的資料表備註
            $currentComment = DB::selectOne("
                SELECT TABLE_COMMENT
                FROM information_schema.TABLES
                WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = ?
            ", [$table]);

            // 如果已有備註且不是空的，則跳過
            if ($currentComment && !empty(trim($currentComment->TABLE_COMMENT))) {
                $this->info("資料表 {$table} 已有備註：{$currentComment->TABLE_COMMENT}，跳過...");
                return;
            }

            // 設定備註
            DB::statement("ALTER TABLE `{$table}` COMMENT = '{$comment}'");
            $this->info("✓ 設定資料表 {$table} 備註：{$comment}");

        } catch (\Exception $e) {
            $this->error("設定資料表 {$table} 備註失敗：" . $e->getMessage());
        }
    }

    /**
     * 輸出訊息（相容性方法）
     */
    private function info(string $message): void
    {
        echo "\033[32m{$message}\033[0m\n";
    }

    private function warn(string $message): void
    {
        echo "\033[33m{$message}\033[0m\n";
    }

    private function error(string $message): void
    {
        echo "\033[31m{$message}\033[0m\n";
    }
}
