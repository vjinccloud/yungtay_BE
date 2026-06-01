<!-- Modules/HistoryOrder/Vue/Detail.vue -->
<!-- 歷史訂單 - 檢視頁 -->
<template>
    <div class="content">
        <BreadcrumbItem />

        <div class="block block-rounded">
            <div class="block-content p-0 overflow-hidden">
                <div class="row g-0" style="min-height:600px;">
                    <!-- ====== 左側面板：規格 ====== -->
                    <div class="col-lg-6" style="background:#FFF; overflow:hidden;">
                        <!-- 左側 Header -->
                        <div class="px-3 py-2" style="background:#464C53;border-bottom:1px solid #EDEDED;">
                            <h6 class="mb-0 fw-bold" style="font-size:0.9rem;color:#ccc;">
                                規格 ({{ order.series_model }} 系列)
                            </h6>
                        </div>
                        <!-- 規格表格 -->
                        <div>
                            <table style="width:100%;border-collapse:collapse;">
                                <thead>
                                    <tr style="border-bottom:1px solid #EDEDED;">
                                        <th colspan="2" style="padding:8px 12px;font-size:0.8rem;color:#1E2939;font-weight:600;width:50%;border-right:1px solid #EDEDED;background:#F5F5F5;">車廂</th>
                                        <th colspan="2" style="padding:8px 12px;font-size:0.8rem;color:#1E2939;font-weight:600;background:#F5F5F5;">出入口</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(row, rowIdx) in specRows" :key="rowIdx" style="border-bottom:1px solid #EDEDED;">
                                        <!-- 車廂欄 -->
                                        <template v-if="row.cabin">
                                            <td style="width:70px;padding:8px 4px 8px 8px;border-right:1px solid #EDEDED;vertical-align:middle;text-align:center;">
                                                <i><img :src="row.cabin.icon" style="width:34px;"></i><br />
                                                <span style="font-size:0.7rem;color:#4A5565;">{{ row.cabin.label }}</span>
                                            </td>
                                            <td style="padding:8px 8px 8px 4px;vertical-align:middle;font-size:0.8rem;color:#101828;line-height:1.5;border-right:1px solid #EDEDED;">
                                                <template v-if="row.cabin.value">
                                                    <div v-for="(line, idx) in formatSpecLines(row.cabin.value)" :key="idx"
                                                         :style="line.subLabel ? 'display:flex;justify-content:space-between;' : ''">
                                                        <span>{{ line.value }}</span>
                                                        <span v-if="line.subLabel" style="color:#99A1AF;">{{ line.subLabel }}</span>
                                                    </div>
                                                </template>
                                                <span v-else style="color:#99A1AF;">—</span>
                                            </td>
                                        </template>
                                        <template v-else>
                                            <td colspan="2" style="border-right:1px solid #EDEDED;"></td>
                                        </template>
                                        <!-- 出入口欄 -->
                                        <template v-if="row.entrance">
                                            <td style="width:70px;padding:8px 4px 8px 8px;border-right:1px solid #EDEDED;vertical-align:middle;text-align:center;">
                                                <i><img :src="row.entrance.icon" style="width:34px;"></i><br />
                                                <span style="font-size:0.7rem;color:#4A5565;">{{ row.entrance.label }}</span>
                                            </td>
                                            <td style="padding:8px 8px 8px 4px;vertical-align:middle;font-size:0.8rem;color:#101828;line-height:1.5;">
                                                <template v-if="row.entrance.value">
                                                    <div v-for="(line, idx) in formatSpecLines(row.entrance.value)" :key="idx"
                                                         :style="line.subLabel ? 'display:flex;justify-content:space-between;' : ''">
                                                        <span>{{ line.value }}</span>
                                                        <span v-if="line.subLabel" style="color:#99A1AF;">{{ line.subLabel }}</span>
                                                    </div>
                                                </template>
                                                <span v-else style="color:#99A1AF;">—</span>
                                            </td>
                                        </template>
                                        <template v-else>
                                            <td colspan="2"></td>
                                        </template>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- ====== 右側面板：渲染圖 + 客戶資料 ====== -->
                    <div class="col-lg-6 d-flex flex-column" style="color:#333;">
                        <!-- ELEVATOR STYLE + 關閉按鈕 -->
                        <div class="d-flex justify-content-between align-items-center px-4 pt-3 pb-2" style="background:#FFF;">
                            <h6 class="fw-bold mb-0" style="color:#E3E3E3; letter-spacing:1px;">ELEVATOR STYLE</h6>
                            <Link :href="route('admin.history-order.index')" class="btn-close" style="font-size:0.7rem;"></Link>
                        </div>
                        <!-- 渲染圖 -->
                        <div class="text-center px-4 mb-3 flex-grow-1 d-flex align-items-center justify-content-center" style="background:#FFF; min-height:220px; max-height:320px; overflow:hidden;">
                            <img
                                v-if="order.elevator_image"
                                :src="elevatorImageSrc"
                                alt="電梯渲染圖"
                                class="img-fluid rounded shadow-sm"
                                style="max-height:320px; max-width:610px; object-fit:contain;"
                            />
                            <div v-else class="text-muted py-5">
                                <i class="fa fa-image fa-3x mb-2 d-block opacity-50"></i>
                                <span>電梯渲染圖</span>
                            </div>
                        </div>

                        <!-- 客戶聯絡資料 -->
                        <div class="px-4 pb-3" style="background:#FFF; padding-top:10px;">
                            <div class="fw-bold pb-1" style="font-size:0.95rem; color:#1E2939;">客戶聯絡資料</div>
                            <div class="row g-2 mb-2">
                                <div class="col-6">
                                    <label class="form-label text-muted mb-0" style="font-size:0.7rem; color:#6A7282;">客戶名稱</label>
                                    <div class="form-control form-control-sm" style="font-size:0.8rem; background-color:transparent; border:1px solid #E5E7EB;">{{ order.customer_name || '—' }}</div>
                                </div>
                                <div class="col-6">
                                    <label class="form-label text-muted mb-0" style="font-size:0.7rem; color:#6A7282;">聯絡電話</label>
                                    <div class="form-control form-control-sm" style="font-size:0.8rem; background-color:transparent; border:1px solid #E5E7EB;">{{ order.contact_phone || '—' }}</div>
                                </div>
                            </div>
                            <div class="mb-2">
                                <label class="form-label text-muted mb-0" style="font-size:0.7rem; color:#6A7282;">聯絡信箱</label>
                                <div class="form-control form-control-sm" style="font-size:0.8rem; background-color:transparent; border:1px solid #E5E7EB;">{{ order.contact_email || '—' }}</div>
                            </div>
                            <div class="row g-2 mb-2">
                                <div class="col-6">
                                    <label class="form-label text-muted mb-0" style="font-size:0.7rem; color:#6A7282;">案件地區</label>
                                    <div class="form-control form-control-sm" style="font-size:0.8rem; background-color:transparent; border:1px solid #E5E7EB;">{{ order.case_area || '—' }}</div>
                                </div>
                                <div class="col-6">
                                    <label class="form-label text-muted mb-0" style="font-size:0.7rem; color:#6A7282;">電梯台數</label>
                                    <div class="form-control form-control-sm" style="font-size:0.8rem; background-color:transparent; border:1px solid #E5E7EB;">{{ order.elevator_count ?? '—' }}</div>
                                </div>
                            </div>
                            <div class="mb-2">
                                <label class="form-label text-muted mb-0" style="font-size:0.7rem; color:#6A7282;">電梯規格</label>
                                <div class="form-control form-control-sm" style="font-size:0.8rem; background-color:transparent; border:1px solid #E5E7EB; min-height:60px; white-space:pre-wrap;">{{ order.elevator_spec || '—' }}</div>
                            </div>
                            <div class="d-flex justify-content-end align-items-end">
                                <a
                                    :href="route('admin.history-order.export-pdf', order.id)"
                                    class="btn btn-sm btn-danger"
                                    target="_blank"
                                >
                                    <i class="fa fa-file-pdf me-1"></i> 匯出 PDF
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { computed } from "vue";
import { Link } from "@inertiajs/vue3";
import Layout from "@/Shared/Admin/Layout.vue";
import BreadcrumbItem from "@/Shared/Admin/Partials/BreadcrumbItem.vue";

export default {
    components: { BreadcrumbItem, Link },
    props: {
        order:              { type: Object, required: true },
        cabinSpecFields:    { type: Object, default: () => ({}) },
        entranceSpecFields: { type: Object, default: () => ({}) },
    },
    setup(props) {
        // 渲染圖加上 cache-busting 版本戳，避免換圖後瀏覽器沿用舊快取
        const elevatorImageSrc = computed(() => {
            const url = props.order?.elevator_image;
            if (!url) return '';
            const ver = props.order?.updated_at
                ? new Date(props.order.updated_at).getTime()
                : Date.now();
            return url + (url.includes('?') ? '&' : '?') + 't=' + ver;
        });

        const specRows = computed(() => {
            const cabinKeys = Object.keys(props.cabinSpecFields);
            const entranceKeys = Object.keys(props.entranceSpecFields);
            const maxLen = Math.max(cabinKeys.length, entranceKeys.length);
            const rows = [];
            for (let i = 0; i < maxLen; i++) {
                const cKey = cabinKeys[i];
                const eKey = entranceKeys[i];
                rows.push({
                    cabin: cKey ? {
                        icon: props.cabinSpecFields[cKey].icon,
                        label: props.cabinSpecFields[cKey].label,
                        value: props.order?.cabin_specs?.[cKey] || null,
                    } : null,
                    entrance: eKey ? {
                        icon: props.entranceSpecFields[eKey].icon,
                        label: props.entranceSpecFields[eKey].label,
                        value: props.order?.entrance_specs?.[eKey] || null,
                    } : null,
                });
            }
            return rows;
        });

        const formatSpecLines = (value) => {
            const lines = Array.isArray(value)
                ? value
                : typeof value === 'string'
                    // 相容字面 \n（單引號 PHP 字串存入的舊資料）與真實換行
                    ? value.replace(/\\n/g, '\n').split('\n')
                    : [String(value)];
            return lines.map(line => {
                const parts = line.split('　'); // 全形空格分隔
                if (parts.length >= 2) {
                    return { value: parts[0].trim(), subLabel: parts.slice(1).join('　').trim() };
                }
                return { value: line.trim(), subLabel: null };
            });
        };

        return {
            elevatorImageSrc,
            specRows,
            formatSpecLines,
        };
    },
    layout: Layout,
};
</script>
