# MenuSetting 選單管理模組

## 功能說明
後台選單 CRUD 管理模組，支援：
- 選單新增 / 編輯 / 刪除
- 樹狀結構（parent_id 父子層級）
- 排序（seq）
- 啟用 / 停用（status）
- 顯示類型（type: 0=不顯示, 1=顯示在選單）

## 資料表
使用現有的 `admin_menu` 資料表。

## 檔案結構
```
Modules/MenuSetting/
├── config.php                          # 模組配置
├── README.md                           # 說明文件
├── Backend/
│   ├── Controller/
│   │   └── MenuSettingController.php   # 控制器（CRUD + toggleActive + sort）
│   ├── Request/
│   │   └── MenuSettingRequest.php      # 表單驗證
│   └── Service/
│       └── MenuSettingService.php      # 商業邏輯
├── Database/
│   └── MenuData.php                    # 選單 Seeder 資料
├── Routes/
│   └── admin.php                       # 路由定義
└── Vue/
    ├── Index.vue                       # 列表頁
    └── Form.vue                        # 新增/編輯頁面
```

## 路由
| Method | URL | Name | 說明 |
|--------|-----|------|------|
| GET | /admin/menu-settings | admin.menu-settings.index | 列表頁 |
| GET | /admin/menu-settings/add | admin.menu-settings.add | 新增頁面 |
| POST | /admin/menu-settings | admin.menu-settings.store | 新增儲存 |
| GET | /admin/menu-settings/{id}/edit | admin.menu-settings.edit | 編輯頁面 |
| PUT | /admin/menu-settings/{id} | admin.menu-settings.update | 更新儲存 |
| DELETE | /admin/menu-settings/{id} | admin.menu-settings.destroy | 刪除 |
| PUT | /admin/menu-settings/toggle-active | admin.menu-settings.toggle-active | 切換啟用 |
| POST | /admin/menu-settings/sort | admin.menu-settings.sort | 更新排序 |

## 安裝步驟
1. 在 `routes/admin.php` 中加入路由引入
2. 將 Vue 檔案 symlink 或複製到前端頁面路徑
