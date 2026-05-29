# 新增歷史訂單 API 文件

## API 基本資訊
- Method: `POST`
- URL: `/api/v1/history-orders`
- Content-Type: `multipart/form-data`
- 路由名稱: `api.history-orders.store`

> 備註：此 API 支援檔案上傳（`elevator_image`），請使用 `multipart/form-data`。

---

## 必填欄位
- `order_name` (string, max:100)
- `customer_name` (string, max:50)

---

## 欄位規格

| 欄位 | 型別 | 必填 | 規則 / 說明 |
|---|---|---|---|
| order_name | string | 是 | max:100 |
| customer_name | string | 是 | max:50 |
| contact_phone | string | 否 | max:30，聯絡電話 |
| contact_email | string(email) | 否 | email, max:100，聯絡信箱 |
| case_area | string | 否 | max:100，案件地區 |
| elevator_count | integer | 否 | min:0，電梯台數 |
| elevator_spec | string | 否 | max:100，電梯規格 |
| series_model | string | 否 | max:50 |
| note | string | 否 | max:1000 |
| elevator_image | file | 否 | image, mimes:jpeg/png/jpg/webp, max:5MB |
| cabin_specs | object/array | 否 | 車廂規格（巢狀） |
| cabin_specs.ceiling | any | 否 | 天井 |
| cabin_specs.door_panel | any | 否 | 門板 |
| cabin_specs.side_panel_left | any | 否 | 左側板 |
| cabin_specs.side_panel_right | any | 否 | 右側板 |
| cabin_specs.side_panel_back | any | 否 | 後側板 |
| cabin_specs.side_panel_front | any | 否 | 前側板 |
| cabin_specs.floor | any | 否 | 地板 |
| cabin_specs.control_panel | any | 否 | 操作盤 |
| cabin_specs.handrail | any | 否 | 扶手 |
| cabin_specs.trim | any | 否 | 飾條 |
| entrance_specs | object/array | 否 | 乘場規格（巢狀） |
| entrance_specs.door_panel | any | 否 | 門板 |
| entrance_specs.door_frame | any | 否 | 門框 |
| entrance_specs.lantern | any | 否 | 廳燈 |
| entrance_specs.control_panel | any | 否 | 操作盤 |

---

## cURL 範例（完整）

```bash
curl -X POST "https://你的網域/api/v1/history-orders" \
  -H "Accept: application/json" \
  -F "order_name=日立永大_某某建設" \
  -F "customer_name=某某建設" \
  -F "contact_phone=0912-345-678" \
  -F "contact_email=contact@example.com" \
  -F "case_area=台北市信義區" \
  -F "elevator_count=2" \
  -F "elevator_spec=客梯 800kg / 11 人乘" \
  -F "series_model=EAS" \
  -F "note=請優先處理" \
  -F "cabin_specs[ceiling]=CH5" \
  -F "cabin_specs[door_panel]=髮紋不銹鋼" \
  -F "cabin_specs[side_panel_left]=鏡面不銹鋼" \
  -F "cabin_specs[side_panel_right]=鏡面不銹鋼" \
  -F "cabin_specs[side_panel_back]=鏡面不銹鋼" \
  -F "cabin_specs[side_panel_front]=鏡面不銹鋼" \
  -F "cabin_specs[floor]=8TB" \
  -F "cabin_specs[control_panel]=BL-C2" \
  -F "cabin_specs[handrail]=NR-108" \
  -F "cabin_specs[trim]=鏡面不銹鋼" \
  -F "entrance_specs[door_panel]=NR-108" \
  -F "entrance_specs[door_frame]=窄型門框" \
  -F "entrance_specs[lantern]=無" \
  -F "entrance_specs[control_panel]=HF-LM5" \
  -F "elevator_image=@/path/to/image.jpg"
```

---

## cURL 範例（最小可用）

```bash
curl -X POST "https://你的網域/api/v1/history-orders" \
  -H "Accept: application/json" \
  -F "order_name=日立永大_某某建設" \
  -F "customer_name=某某建設"
```

---

## 成功回應（201）

```json
{
  "success": true,
  "data": {
    "id": 123,
    "order_name": "日立永大_某某建設",
    "customer_name": "某某建設",
    "contact_phone": "0912-345-678",
    "contact_email": "contact@example.com",
    "case_area": "台北市信義區",
    "elevator_count": 2,
    "elevator_spec": "客梯 800kg / 11 人乘",
    "elevator_image": "/uploads/history-orders/xxx.jpg"
  },
  "message": "歷史訂單已建立"
}
```

---

## 失敗回應（422）

```json
{
  "success": false,
  "errors": {
    "order_name": ["The order name field is required."],
    "customer_name": ["The customer name field is required."]
  }
}
```

---

## 前端串接提醒
- 有檔案上傳時，請使用 `FormData` 與 `multipart/form-data`。
- 巢狀欄位請用 `cabin_specs[ceiling]`、`entrance_specs[door_panel]` 這種 key。
- 若收到 `422`，請優先檢查必填欄位與 email 格式。

---

# 後台帳密檢查 API 文件（純驗證，不登入）

## API 基本資訊
- Method: `POST`
- URL: `/api/v1/admin-auth/check-credentials`
- Content-Type: `application/json`
- 路由名稱: `api.admin-auth.check-credentials`
- Rate Limit: `10 requests / minute`

> 備註：此 API 只檢查帳號密碼是否匹配，不建立登入狀態、不發 token。

---

## Request 欄位

| 欄位 | 型別 | 必填 | 規則 / 說明 |
|---|---|---|---|
| username | string(email) | 是 | 必須是 email 格式 |
| password | string | 是 | 最少 6 碼 |

---

## cURL 範例

```bash
curl -X POST "https://你的網域/api/v1/admin-auth/check-credentials" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "username": "admin@example.com",
    "password": "your_password"
  }'
```

---

## 成功回應（200）

### 帳密正確

```json
{
  "success": true,
  "exists": true
}
```

### 帳密錯誤 / 帳號停用 / 帳號不存在

```json
{
  "success": true,
  "exists": false
}
```

---

## 驗證錯誤回應（422）

```json
{
  "message": "The given data was invalid.",
  "errors": {
    "username": ["The username field is required."],
    "password": ["The password field is required."]
  }
}
```

---

## 使用規則
- 這支 API 僅供「前置檢查」用途。
- 回傳 `exists=true` 只代表帳密可用，不代表使用者已登入。
- 真正登入流程仍需走後台登入機制（Session / Guard）。