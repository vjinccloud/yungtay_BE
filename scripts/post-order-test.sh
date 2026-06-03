#!/bin/bash
# 以 UTF-8 送出，避免 Windows shell 用 Big5 編碼導致 MySQL 收到非法字元
set -e
cd "$(dirname "$0")/.."

IMG=$(ls elevatorPNG/*155240*.png | head -n1)
echo "使用圖檔: $IMG"

curl -sS -X POST "http://yungtay.vjinc.biz/api/v1/history-orders" \
  -H "Accept: application/json" \
  -F "order_name=日立永大_李小名" \
  -F "customer_name=李小名" \
  -F "contact_phone=0972872972" \
  -F "contact_email=lee@vjinc.biz" \
  -F "case_area=106" \
  -F "series_model=EAS" \
  -F "cabin_specs[ceiling]=CH5 / J147" \
  -F "cabin_specs[door_panel]=髮紋不鏽鋼" \
  -F "cabin_specs[side_panel_left]=EAS / C114 / A111 / C114" \
  -F "cabin_specs[side_panel_right]=EAS / C114 / A111 / C114" \
  -F "cabin_specs[side_panel_back]=EAS / C114 / A111 / C114" \
  -F "cabin_specs[side_panel_front]=SNW-9" \
  -F "cabin_specs[floor]=單一材質 / 505" \
  -F "cabin_specs[control_panel]=KF-D2F" \
  -F "cabin_specs[handrail]=None" \
  -F "cabin_specs[trim]=None" \
  -F "entrance_specs[door_panel]=1072" \
  -F "entrance_specs[door_frame]=窄型門框 / 髮紋不鏽鋼" \
  -F "entrance_specs[lantern]=None" \
  -F "entrance_specs[control_panel]=HOT LED" \
  -F "elevator_image=@${IMG}" \
  -w "\n---HTTP_STATUS:%{http_code}---\n"
