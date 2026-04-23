<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\AdminMenu;
use App\Models\Permission;
use Illuminate\Support\Facades\DB;

/**
 * 模組安裝指令
 * 
 * 功能：
 * 1. 新增選單到 admin_menu 表
 * 2. 同步權限到 permissions 表
 * 3. 可選：執行 migration
 * 
 * 使用方式：
 * php artisan module:install                    # 互動式安裝
 * php artisan module:install --sync-permissions # 只同步權限（選單→權限）
 */
class ModuleInstallCommand extends Command
{
    protected $signature = 'module:install 
                            {--sync-permissions : 只同步權限（從選單同步到權限表）}
                            {--menu-only : 只新增選單}';

    protected $description = '安裝模組（新增選單、同步權限）';

    public function handle()
    {
        // 只同步權限
        if ($this->option('sync-permissions')) {
            $this->syncPermissions();
            return 0;
        }

        $this->info('=== 模組安裝工具 ===');
        $this->newLine();

        // 選擇操作
        $action = $this->choice(
            '請選擇操作',
            [
                '1' => '新增單一設定頁面選單（如：首頁圖片設定）',
                '2' => '新增列表頁面選單（含 CRUD）',
                '3' => '同步權限（選單 → 權限表）',
                '4' => '查看現有選單結構',
            ],
            '1'
        );

        switch ($action) {
            case '1':
                $this->addSinglePageMenu();
                break;
            case '2':
                $this->addListPageMenu();
                break;
            case '3':
                $this->syncPermissions();
                break;
            case '4':
                $this->showMenuStructure();
                break;
        }

        return 0;
    }

    /**
     * 新增單一設定頁面選單
     */
    protected function addSinglePageMenu()
    {
        $this->info('📄 新增單一設定頁面選單');
        $this->newLine();

        // 取得下一個可用 ID
        $nextId = AdminMenu::max('id') + 1;
        $this->line("下一個可用 ID: {$nextId}");

        // 顯示可用的父層選單
        $this->showParentMenuOptions();

        // 輸入資訊
        $parentId = $this->ask('父層選單 ID（0 = 最上層）', '11');
        $title = $this->ask('選單名稱', '首頁圖片設定');
        $urlName = $this->ask('路由名稱（如：admin.home-image-setting）', 'admin.home-image-setting');
        $url = $this->ask('URL 路徑（如：admin/home-image-setting）', 'admin/home-image-setting');
        $seq = $this->ask('排序', '99');

        // 取得父層資訊
        $parent = AdminMenu::find($parentId);
        $level = $parent ? $parent->level + 1 : 0;

        // 確認資訊
        $this->newLine();
        $this->info('即將新增以下選單：');
        $this->table(
            ['ID', '名稱', '父層ID', '層級', '路由名稱', 'URL', '排序'],
            [[$nextId, $title, $parentId, $level, $urlName, $url, $seq]]
        );

        if (!$this->confirm('確認新增？', true)) {
            $this->warn('已取消');
            return;
        }

        // 新增選單
        AdminMenu::create([
            'id' => $nextId,
            'title' => $title,
            'parent_id' => $parentId,
            'type' => 1,  // 1 = 顯示在選單
            'level' => $level,
            'url' => $url,
            'url_name' => $urlName,
            'icon_image' => '',
            'status' => 1,
            'seq' => $seq,
        ]);

        $this->info("✅ 選單已新增（ID: {$nextId}）");

        // 詢問是否同步權限
        if ($this->confirm('是否立即同步權限？', true)) {
            $this->syncPermissions();
        }

        $this->showSeederCode($nextId, $title, $parentId, $level, $url, $urlName, $seq);
    }

    /**
     * 新增列表頁面選單（含 CRUD）
     */
    protected function addListPageMenu()
    {
        $this->info('📋 新增列表頁面選單（含 CRUD）');
        $this->newLine();

        // 取得下一個可用 ID
        $nextId = AdminMenu::max('id') + 1;
        $this->line("下一個可用 ID: {$nextId}");

        // 顯示可用的父層選單
        $this->showParentMenuOptions();

        // 輸入資訊
        $parentId = $this->ask('父層選單 ID（0 = 最上層）', '11');
        $title = $this->ask('選單名稱（如：範例管理）', '範例管理');
        $urlPrefix = $this->ask('URL 前綴（如：admin/samples）', 'admin/samples');
        $routePrefix = $this->ask('路由前綴（如：admin.samples）', 'admin.samples');
        $seq = $this->ask('排序', '99');

        // 取得父層資訊
        $parent = AdminMenu::find($parentId);
        $level = $parent ? $parent->level + 1 : 0;

        // 準備 CRUD 選單
        $menus = [
            ['id' => $nextId, 'title' => $title, 'parent_id' => $parentId, 'type' => 1, 'level' => $level, 'url' => $urlPrefix, 'url_name' => $routePrefix, 'seq' => $seq],
            ['id' => $nextId + 1, 'title' => "新增{$title}", 'parent_id' => $nextId, 'type' => 0, 'level' => $level + 1, 'url' => "{$urlPrefix}/add", 'url_name' => "{$routePrefix}.add", 'seq' => 1],
            ['id' => $nextId + 2, 'title' => "編輯{$title}", 'parent_id' => $nextId, 'type' => 0, 'level' => $level + 1, 'url' => "{$urlPrefix}/edit", 'url_name' => "{$routePrefix}.edit", 'seq' => 2],
            ['id' => $nextId + 3, 'title' => "刪除{$title}", 'parent_id' => $nextId, 'type' => 0, 'level' => $level + 1, 'url' => '', 'url_name' => "{$routePrefix}.delete", 'seq' => 3],
        ];

        // 確認資訊
        $this->newLine();
        $this->info('即將新增以下選單：');
        $this->table(
            ['ID', '名稱', '父層ID', '類型', '層級', '路由名稱'],
            collect($menus)->map(fn($m) => [$m['id'], $m['title'], $m['parent_id'], $m['type'] ? '顯示' : '隱藏', $m['level'], $m['url_name']])->toArray()
        );

        if (!$this->confirm('確認新增？', true)) {
            $this->warn('已取消');
            return;
        }

        // 新增選單
        foreach ($menus as $menu) {
            AdminMenu::create([
                'id' => $menu['id'],
                'title' => $menu['title'],
                'parent_id' => $menu['parent_id'],
                'type' => $menu['type'],
                'level' => $menu['level'],
                'url' => $menu['url'],
                'url_name' => $menu['url_name'],
                'icon_image' => '',
                'status' => 1,
                'seq' => $menu['seq'],
            ]);
        }

        $this->info("✅ 選單已新增（ID: {$nextId} ~ " . ($nextId + 3) . "）");

        // 詢問是否同步權限
        if ($this->confirm('是否立即同步權限？', true)) {
            $this->syncPermissions();
        }

        $this->showSeederCodeForList($menus);
    }

    /**
     * 同步權限（從選單同步到權限表）
     */
    protected function syncPermissions()
    {
        $this->info('🔄 同步權限中...');

        $adminMenu = AdminMenu::whereNotNull('url_name')
            ->where('url_name', '!=', '')
            ->get();
        $arr = [];

        foreach ($adminMenu as $menu) {
            $arr[] = [
                'id' => $menu->id,
                'name' => $menu->url_name,
                'guard_name' => 'admin',
            ];
        }

        // 禁用外键约束
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('permissions')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        Permission::upsert($arr, [], ['name', 'guard_name']);

        $this->info("✅ 已同步 " . count($arr) . " 個權限");
    }

    /**
     * 顯示父層選單選項
     */
    protected function showParentMenuOptions()
    {
        $this->line('可用的父層選單：');
        
        $menus = AdminMenu::where('type', 1)
            ->where('level', '<=', 1)
            ->orderBy('seq')
            ->get();

        $this->table(
            ['ID', '名稱', '層級'],
            $menus->map(fn($m) => [$m->id, str_repeat('  ', $m->level) . $m->title, $m->level])->toArray()
        );
        
        $this->newLine();
    }

    /**
     * 顯示現有選單結構
     */
    protected function showMenuStructure()
    {
        $this->info('📂 現有選單結構：');
        
        $menus = AdminMenu::orderBy('seq')->orderBy('id')->get();

        $this->table(
            ['ID', '名稱', '父層ID', '類型', '層級', '路由名稱'],
            $menus->map(fn($m) => [
                $m->id,
                str_repeat('  ', $m->level) . $m->title,
                $m->parent_id,
                $m->type ? '顯示' : '隱藏',
                $m->level,
                $m->url_name
            ])->toArray()
        );
    }

    /**
     * 顯示 Seeder 程式碼（單一頁面）
     */
    protected function showSeederCode($id, $title, $parentId, $level, $url, $urlName, $seq)
    {
        $this->newLine();
        $this->info('📝 請將以下程式碼加入 AdminMenuSeeder.php：');
        $this->newLine();
        $this->line("// {$title}");
        $this->line("['id' => '{$id}', 'title' => '{$title}', 'parent_id' => '{$parentId}', 'type' => '1', 'level' => '{$level}', 'url' => '{$url}', 'url_name' => '{$urlName}', 'icon_image' => '', 'status' => '1', 'seq' => '{$seq}'],");
    }

    /**
     * 顯示 Seeder 程式碼（列表頁面）
     */
    protected function showSeederCodeForList($menus)
    {
        $this->newLine();
        $this->info('📝 請將以下程式碼加入 AdminMenuSeeder.php：');
        $this->newLine();
        $this->line("// {$menus[0]['title']}");
        foreach ($menus as $menu) {
            $this->line("['id' => '{$menu['id']}', 'title' => '{$menu['title']}', 'parent_id' => '{$menu['parent_id']}', 'type' => '{$menu['type']}', 'level' => '{$menu['level']}', 'url' => '{$menu['url']}', 'url_name' => '{$menu['url_name']}', 'icon_image' => '', 'status' => '1', 'seq' => '{$menu['seq']}'],");
        }
    }
}
