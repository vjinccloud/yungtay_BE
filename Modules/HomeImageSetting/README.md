# HomeImageSetting 首頁圖片設定模組

## 模組說明

這是一個**單一設定頁面**模組，用於後台設定首頁圖片。
- 沒有列表頁面，只有編輯頁面
- 適合用於網站基本設定、單一資料編輯等場景

### 功能
- **標題**：中文/英文
- **圖片**：中文版/英文版（各一張）

## 模組結構

```
Modules/HomeImageSetting/
├── README.md
├── config.php
├── Backend/
│   ├── Controller/
│   │   └── HomeImageSettingController.php
│   ├── Request/
│   │   └── HomeImageSettingRequest.php
│   ├── Service/
│   │   └── HomeImageSettingService.php
│   └── Repository/
│       └── HomeImageSettingRepository.php
├── Database/
│   ├── migrations/
│   │   └── create_home_image_settings_table.php
│   └── MenuData.php                        # 選單資料（方便複製）
├── Model/
│   └── HomeImageSetting.php
├── Routes/
│   └── admin.php
└── Vue/
    └── Form.vue
```

## 快速安裝指令

```bash
# 1. 同步權限（選單已加入後）
php artisan module:install --sync-permissions

# 2. 互動式新增選單
php artisan module:install
```

## 完整安裝步驟（複製模組時使用）

### 1. 複製模組資料夾
```bash
cp -r Modules/HomeImageSetting Modules/YourSetting
```

### 2. 搜尋替換
- `HomeImageSetting` → `YourSetting`（PascalCase）
- `home_image_setting` → `your_setting`（snake_case）
- `home-image-setting` → `your-setting`（kebab-case）
- `首頁圖片設定` → `你的設定名稱`

### 3. 複製 Migration
```bash
cp Modules/YourSetting/Database/migrations/*.php database/migrations/YYYY_MM_DD_HHMMSS_create_your_settings_table.php
```

### 4. 複製 Vue 檔案
```bash
mkdir resources/js/InertiaPages/Admin/YourSetting
cp Modules/YourSetting/Vue/Form.vue resources/js/InertiaPages/Admin/YourSetting/
```

### 5. 註冊路由
在 `routes/admin.php` 加入：
```php
// YourSetting（模組）
require base_path('Modules/YourSetting/Routes/admin.php');
```

### 6. 新增選單
方法一：使用指令
```bash
php artisan module:install
# 選擇「新增單一設定頁面選單」
```

方法二：手動加入 `database/seeders/AdminMenuSeeder.php`
```php
// 你的設定名稱
['id' => 'XXX', 'title' => '你的設定名稱', 'parent_id' => '11', 'type' => '1', 'level' => '1', 'url' => 'admin/your-setting', 'url_name' => 'admin.your-setting', 'icon_image' => '', 'status' => '1', 'seq' => '99'],
```

### 7. 執行 Migration 和同步權限
```bash
php artisan migrate
php artisan db:seed --class=AdminMenuSeeder
php artisan module:install --sync-permissions
```

## 注意事項

1. 此模組只有一筆資料（id=1），透過 `firstOrCreate` 自動建立
2. `composer.json` 已設定 `"Modules\\": "Modules/"`，autoload 會自動載入
3. 沒有新增/刪除功能，只有編輯功能
