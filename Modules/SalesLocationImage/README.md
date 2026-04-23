# SalesLocationImage 銷售據點圖片管理模組

## 功能說明
- 銷售據點圖片列表管理
- 支援中英文圖片上傳
- 拖曳排序功能
- 啟用/停用狀態控制

## 檔案結構
```
Modules/SalesLocationImage/
├── Backend/
│   ├── Controller/
│   │   └── SalesLocationImageController.php
│   ├── Repository/
│   │   └── SalesLocationImageRepository.php
│   ├── Request/
│   │   └── SalesLocationImageRequest.php
│   └── Service/
│       └── SalesLocationImageService.php
├── Database/
│   ├── MenuData.php
│   └── migrations/
│       └── create_sales_location_images_table.php
├── Model/
│   └── SalesLocationImage.php
├── Routes/
│   └── admin.php
├── Vue/
│   ├── Index.vue
│   └── Form.vue
├── config.php
└── README.md
```

## 安裝步驟

1. 執行資料庫遷移：
```bash
php artisan migrate --path=Modules/SalesLocationImage/Database/migrations
```

2. 在 `AdminMenuSeeder.php` 中新增選單項目（ID 可自行調整）：
```php
// 銷售據點圖片管理
['id' => '151', 'title' => '銷售據點圖片管理', 'parent_id' => '11', 'type' => '1', 'level' => '1', 'url' => 'admin/sales-location-images', 'url_name' => 'admin.sales-location-images.index', 'icon_image' => '', 'status' => '1', 'seq' => '5'],
['id' => '152', 'title' => '新增銷售據點圖片', 'parent_id' => '151', 'type' => '0', 'level' => '2', 'url' => 'admin/sales-location-images/add', 'url_name' => 'admin.sales-location-images.add', 'icon_image' => '', 'status' => '1', 'seq' => '1'],
['id' => '153', 'title' => '編輯銷售據點圖片', 'parent_id' => '151', 'type' => '0', 'level' => '2', 'url' => 'admin/sales-location-images/edit', 'url_name' => 'admin.sales-location-images.edit', 'icon_image' => '', 'status' => '1', 'seq' => '2'],
['id' => '154', 'title' => '刪除銷售據點圖片', 'parent_id' => '151', 'type' => '0', 'level' => '2', 'url' => '', 'url_name' => 'admin.sales-location-images.destroy', 'icon_image' => '', 'status' => '1', 'seq' => '3'],
```

3. 在 `routes/admin.php` 中載入模組路由：
```php
// 銷售據點圖片管理（模組）
require base_path('Modules/SalesLocationImage/Routes/admin.php');
```

4. 重新執行選單 Seeder：
```bash
php artisan db:seed --class=AdminMenuSeeder
php artisan db:seed --class=PermissionSeeder
```

5. 清除快取：
```bash
php artisan cache:clear
php artisan ziggy:generate
```

## 路由
- GET `/admin/sales-location-images` - 列表頁面
- GET `/admin/sales-location-images/add` - 新增頁面
- POST `/admin/sales-location-images` - 新增儲存
- GET `/admin/sales-location-images/{id}/edit` - 編輯頁面
- PUT `/admin/sales-location-images/{id}` - 更新儲存
- DELETE `/admin/sales-location-images/{id}` - 刪除
- POST `/admin/sales-location-images/sort` - 更新排序
