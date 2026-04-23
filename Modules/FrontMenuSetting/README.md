# FrontMenuSetting 前台選單管理模組

## 功能說明
- 前台網站選單的層級式管理（支援無限層級）
- 多語言選單名稱（中文/英文）
- 支援多種連結類型（外部連結、內部路由、頁面、無連結）
- 樹狀結構檢視 / 表格檢視 切換
- 排序、啟用/停用（停用時遞迴停用子選單）
- 刪除時自動刪除所有子孫選單

## 目錄結構

```
Modules/FrontMenuSetting/
├── Backend/
│   ├── Controller/
│   │   └── FrontMenuController.php     # 控制器
│   ├── Repository/
│   │   └── FrontMenuRepository.php     # 資料存取層
│   ├── Request/
│   │   └── FrontMenuRequest.php        # 表單驗證
│   └── Service/
│       └── FrontMenuService.php        # 商業邏輯層
├── Database/
│   ├── MenuData.php                    # 後台選單 Seeder 資料
│   └── migrations/
│       └── create_front_menus_table.php
├── Model/
│   └── FrontMenu.php                   # Eloquent Model
├── Routes/
│   └── admin.php                       # 路由定義
├── Vue/
│   ├── Index.vue                       # 列表頁（樹狀 + 表格）
│   └── Form.vue                        # 新增/編輯頁
├── config.php                          # 模組配置
└── README.md
```

## 安裝步驟

### 1. 執行 Migration
```bash
php artisan migrate --path=Modules/FrontMenuSetting/Database/migrations
```

### 2. 新增後台選單
在 AdminMenuSeeder 中加入選單資料，或手動執行：
```php
use Modules\FrontMenuSetting\Database\MenuData;
$data = MenuData::getData($startId, $parentId);
```

### 3. 路由已自動載入
路由已在 `routes/admin.php` 中以 `require` 方式載入。

## 資料表欄位說明

| 欄位 | 類型 | 說明 |
|------|------|------|
| id | bigint | 主鍵 |
| parent_id | bigint | 父層選單 ID（0 = 頂層）|
| title | json | 選單名稱（支援多語言）|
| level | tinyint | 層級深度 |
| link_type | string | 連結類型：url/route/page/none |
| link_url | string | 連結網址 |
| link_target | string | 開啟方式：_self/_blank |
| icon | string | FontAwesome 圖標 class |
| seq | smallint | 排序（數字越小越前面）|
| status | boolean | 啟用狀態 |
| created_by | bigint | 建立者 |
| updated_by | bigint | 更新者 |
| created_at | timestamp | 建立時間 |
| updated_at | timestamp | 更新時間 |

## API 端點

### 後台 Inertia 頁面
- `GET  /admin/front-menu-settings` - 列表頁
- `GET  /admin/front-menu-settings/add` - 新增頁
- `POST /admin/front-menu-settings` - 新增儲存
- `GET  /admin/front-menu-settings/{id}/edit` - 編輯頁
- `PUT  /admin/front-menu-settings/{id}` - 更新
- `DELETE /admin/front-menu-settings/{id}` - 刪除

### JSON API
- `GET  /admin/api/front-menu-settings` - 樹狀選單
- `GET  /admin/api/front-menu-settings/parent-options` - 父層選項
- `GET  /admin/api/front-menu-settings/frontend-tree` - 前台用樹狀選單
- `GET  /admin/api/front-menu-settings/{id}` - 單筆資料
- `POST /admin/api/front-menu-settings` - API 新增
- `PUT  /admin/api/front-menu-settings/{id}` - API 更新
- `DELETE /admin/api/front-menu-settings/{id}` - API 刪除
