# IntroVideo 片頭動畫模組

## 功能說明
- 上傳單一 MP4 影片作為網站片頭動畫
- 支援啟用/停用狀態控制
- 影片大小限制 100MB

## 檔案結構
```
Modules/IntroVideo/
├── Backend/
│   ├── Controller/
│   │   └── IntroVideoController.php
│   ├── Repository/
│   │   └── IntroVideoRepository.php
│   ├── Request/
│   │   └── IntroVideoRequest.php
│   └── Service/
│       └── IntroVideoService.php
├── Database/
│   ├── MenuData.php
│   └── migrations/
│       └── create_intro_videos_table.php
├── Model/
│   └── IntroVideo.php
├── Routes/
│   └── admin.php
├── Vue/
│   └── Form.vue
├── config.php
└── README.md
```

## 安裝步驟

1. 執行資料庫遷移：
```bash
php artisan migrate --path=Modules/IntroVideo/Database/migrations
```

2. 在 `AdminMenuSeeder.php` 中新增選單項目（ID 可自行調整）：
```php
// 片頭動畫
['id' => '150', 'title' => '片頭動畫', 'parent_id' => '11', 'type' => '1', 'level' => '1', 'url' => 'admin/intro-video', 'url_name' => 'admin.intro-video', 'icon_image' => '', 'status' => '1', 'seq' => '4'],
```

3. 重新執行選單 Seeder：
```bash
php artisan db:seed --class=AdminMenuSeeder
```

4. 清除快取：
```bash
php artisan cache:clear
```

## 路由
- GET `/admin/intro-video` - 編輯頁面
- PUT `/admin/intro-video` - 更新設定
- DELETE `/admin/intro-video/video` - 刪除影片

## API 前台取得片頭動畫
可在 API Controller 中使用：
```php
use Modules\IntroVideo\Model\IntroVideo;

$introVideo = IntroVideo::where('is_active', true)->first();
if ($introVideo && $introVideo->video_url) {
    return response()->json([
        'video_url' => $introVideo->video_url,
    ]);
}
```
