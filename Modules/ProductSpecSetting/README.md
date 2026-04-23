# ProductSpecSetting 商品規格設定模組

## 概述

商品多組規格搭配模組，支援笛卡爾積自動產生規格組合。

## 功能特色

- **規格群組管理**：可建立多個規格群組（如：顏色、大小、材質）
- **規格值管理**：每個群組下可新增多個規格值（如：紅色、白色、M、L）
- **自動組合產生**：根據啟用的群組與值，自動做笛卡爾積產生所有組合
- **組合資料保留**：重新產生組合時，會自動保留已設定的 SKU / 價格 / 庫存
- **多語言支援**：規格名稱支援中文/英文
- **排序 / 啟停用**：群組、值、組合皆可排序與啟停用

## 範例

```
群組「顏色」→ 紅色、白色、黃色
群組「大小」→ M、L

自動產生 6 種組合：
  紅色 / M  →  SKU: CLR-RED-M,  價格: 100, 庫存: 50
  紅色 / L  →  SKU: CLR-RED-L,  價格: 110, 庫存: 30
  白色 / M  →  SKU: CLR-WHT-M,  價格: 100, 庫存: 45
  白色 / L  →  SKU: CLR-WHT-L,  價格: 110, 庫存: 25
  黃色 / M  →  SKU: CLR-YLW-M,  價格: 100, 庫存: 40
  黃色 / L  →  SKU: CLR-YLW-L,  價格: 110, 庫存: 20
```

## 目錄結構

```
Modules/ProductSpecSetting/
├── config.php                                      # 模組配置
├── README.md                                       # 說明文件
├── Backend/
│   ├── Controller/
│   │   └── ProductSpecController.php              # 控制器
│   ├── Repository/
│   │   └── ProductSpecRepository.php              # 資料存取層
│   ├── Request/
│   │   ├── SpecGroupRequest.php                   # 群組驗證
│   │   ├── SpecValueRequest.php                   # 值驗證
│   │   ├── SpecCombinationRequest.php             # 組合驗證
│   │   └── SpecCombinationBatchRequest.php        # 批次組合驗證
│   └── Service/
│       └── ProductSpecService.php                 # 商業邏輯
├── Database/
│   └── migrations/
│       ├── 2026_02_28_000001_create_spec_groups_table.php
│       ├── 2026_02_28_000002_create_spec_values_table.php
│       ├── 2026_02_28_000003_create_spec_combinations_table.php
│       └── 2026_02_28_000004_create_spec_combination_values_table.php
├── Model/
│   ├── SpecGroup.php                              # 規格群組
│   ├── SpecValue.php                              # 規格值
│   ├── SpecCombination.php                        # 規格組合
│   └── SpecCombinationValue.php                   # 組合值對應
└── Routes/
    └── admin.php                                   # 路由定義
```

## 架構

Controller → Service → Repository → Model（四層架構）

## 資料表關聯

```
spec_groups (1) ──→ (N) spec_values
spec_combinations (1) ──→ (N) spec_combination_values
spec_combination_values → spec_groups (FK)
spec_combination_values → spec_values (FK)
```

## API 路由

### Inertia Pages
| Method | URI | 說明 |
|--------|-----|------|
| GET | /product-spec-settings | 主頁 |
| GET | /product-spec-settings/groups/add | 新增群組頁 |
| POST | /product-spec-settings/groups | 儲存群組 |
| GET | /product-spec-settings/groups/{id}/edit | 編輯群組頁 |
| PUT | /product-spec-settings/groups/{id} | 更新群組 |
| DELETE | /product-spec-settings/groups/{id} | 刪除群組 |

### JSON API
| Method | URI | 說明 |
|--------|-----|------|
| GET | /api/product-spec-settings/groups | 群組列表 |
| POST | /api/product-spec-settings/groups | 新增群組 |
| PUT | /api/product-spec-settings/groups/{id} | 更新群組 |
| DELETE | /api/product-spec-settings/groups/{id} | 刪除群組 |
| POST | /api/product-spec-settings/groups/{groupId}/values | 新增規格值 |
| PUT | /api/product-spec-settings/values/{id} | 更新規格值 |
| DELETE | /api/product-spec-settings/values/{id} | 刪除規格值 |
| GET | /api/product-spec-settings/combinations | 組合列表 |
| POST | /product-spec-settings/combinations/generate | 產生組合 |
| PUT | /product-spec-settings/combinations/batch | 批次更新組合 |
| GET | /api/product-spec-settings/structure | 前台完整結構 |
