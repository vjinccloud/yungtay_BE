<!-- Modules/HistoryOrder/Vue/Index.vue -->
<!-- 歷史訂單 - 列表頁 -->
<template>
    <div class="content">
        <BreadcrumbItem />

        <div class="block block-rounded">
            <div class="block-header block-header-default">
                <h3 class="block-title">歷史訂單</h3>
            </div>

            <!-- 篩選列 -->
            <div class="block-content block-content-full border-bottom bg-body-light">
                <div class="row g-2 align-items-end">
                    <div class="col-md-2">
                        <label class="form-label small mb-1">更新日期</label>
                        <input
                            type="date"
                            class="form-control form-control-sm"
                            v-model="filterForm.date"
                            placeholder="請輸入"
                        />
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small mb-1">訂單名稱 <span class="text-danger">*</span></label>
                        <input
                            type="text"
                            class="form-control form-control-sm"
                            v-model="filterForm.order_name"
                            placeholder="請輸入"
                            @keydown.enter="applyFilter"
                        />
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small mb-1">系列型號</label>
                        <input
                            type="text"
                            class="form-control form-control-sm"
                            v-model="filterForm.series_model"
                            placeholder="請輸入"
                            @keydown.enter="applyFilter"
                        />
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small mb-1">業務姓名 <span class="text-danger">*</span></label>
                        <input
                            type="text"
                            class="form-control form-control-sm"
                            v-model="filterForm.sales_name"
                            placeholder="請輸入"
                            @keydown.enter="applyFilter"
                        />
                    </div>
                    <div class="col-md-2 d-flex gap-2">
                        <button class="btn btn-sm btn-alt-primary" @click="applyFilter">
                            <i class="fa fa-search me-1"></i> 搜尋
                        </button>
                        <button class="btn btn-sm btn-alt-secondary" @click="resetFilter">
                            <i class="fa fa-undo me-1"></i> 重置
                        </button>
                    </div>
                </div>
            </div>

            <div class="block-content block-content-full">
                <div v-if="items.data.length === 0" class="text-center text-muted py-5">
                    <i class="fa fa-file-alt fa-3x mb-3 d-block opacity-50"></i>
                    <p>尚無歷史訂單資料</p>
                </div>

                <template v-else>
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-vcenter">
                            <thead>
                                <tr>
                                    <th style="width: 40px;" class="text-center">
                                        <input
                                            type="checkbox"
                                            class="form-check-input"
                                            :checked="isAllSelected"
                                            @change="toggleSelectAll"
                                        />
                                    </th>
                                    <th style="width: 130px;" class="text-center">
                                        更新日期
                                        <i class="fa fa-sort ms-1 text-muted" style="cursor: pointer;" @click="toggleSort('updated_at')"></i>
                                    </th>
                                    <th>
                                        訂單
                                        <i class="fa fa-sort ms-1 text-muted" style="cursor: pointer;" @click="toggleSort('order_name')"></i>
                                    </th>
                                    <th style="width: 100px;" class="text-center">
                                        型號
                                        <i class="fa fa-sort ms-1 text-muted" style="cursor: pointer;" @click="toggleSort('series_model')"></i>
                                    </th>
                                    <th style="width: 120px;" class="text-center">
                                        業務姓名
                                        <i class="fa fa-sort ms-1 text-muted" style="cursor: pointer;" @click="toggleSort('sales_name')"></i>
                                    </th>
                                    <th style="width: 80px;" class="text-center">功能</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="item in items.data" :key="item.id">
                                    <td class="text-center">
                                        <input
                                            type="checkbox"
                                            class="form-check-input"
                                            :value="item.id"
                                            v-model="selectedIds"
                                        />
                                    </td>
                                    <td class="text-center small">
                                        {{ formatDate(item.updated_at) }}
                                    </td>
                                    <td>
                                        {{ item.order_name }}
                                    </td>
                                    <td class="text-center">
                                        <span v-if="item.series_model" class="badge bg-info rounded-pill">
                                            {{ item.series_model }}
                                        </span>
                                        <span v-else class="text-muted">-</span>
                                    </td>
                                    <td class="text-center">
                                        {{ item.sales_name }}
                                    </td>
                                    <td class="text-center">
                                        <button
                                            class="btn btn-sm btn-primary"
                                            @click="openDetail(item)"
                                        >
                                            檢視
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- 分頁 -->
                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <div class="text-muted small">
                            顯示第 {{ items.from }}–{{ items.to }} 筆，共 {{ items.total }} 筆
                        </div>
                        <nav v-if="items.last_page > 1">
                            <ul class="pagination pagination-sm mb-0">
                                <li class="page-item" :class="{ disabled: !items.prev_page_url }">
                                    <a class="page-link" href="#" @click.prevent="goToPage(items.current_page - 1)">
                                        <i class="fa fa-chevron-left"></i>
                                    </a>
                                </li>
                                <li
                                    v-for="p in pageRange"
                                    :key="p"
                                    class="page-item"
                                    :class="{ active: p === items.current_page, disabled: p === '...' }"
                                >
                                    <a v-if="p !== '...'" class="page-link" href="#" @click.prevent="goToPage(p)">{{ p }}</a>
                                    <span v-else class="page-link">…</span>
                                </li>
                                <li class="page-item" :class="{ disabled: !items.next_page_url }">
                                    <a class="page-link" href="#" @click.prevent="goToPage(items.current_page + 1)">
                                        <i class="fa fa-chevron-right"></i>
                                    </a>
                                </li>
                            </ul>
                        </nav>
                    </div>
                </template>

                <!-- 匯出 EXCEL -->
                <div class="d-flex justify-content-end mt-3">
                    <a
                        :href="exportUrl"
                        class="btn btn-sm btn-alt-secondary"
                        target="_blank"
                    >
                        <i class="fa fa-download me-1"></i> 匯出 EXCEL
                    </a>
                </div>
            </div>
        </div>

    </div>

    <!-- 檢視彈窗 (Teleport 到 body 避免 z-index 問題) -->
    <Teleport to="body">
        <div class="modal fade" id="detailModal" tabindex="-1" ref="detailModalRef">
            <div class="modal-dialog modal-xl modal-dialog-scrollable" style="max-width:1200px; display:flex; align-items:center;">
                <div class="modal-content border-0 overflow-hidden" style="border-radius:8px;" v-if="detailOrder">
                    <div class="row g-0" style="min-height:600px;">
                        <!-- ====== 左側深色面板：規格 ====== -->
                        <div class="col-6" style="background:#FFF; border-radius:0.5rem; overflow:hidden;">
                            <!-- 左側 Header -->
                            <div class="px-3 py-2" style="background:#464C53;border-bottom:1px solid #EDEDED;">
                                <h6 class="mb-0 fw-bold" style="font-size:0.9rem;color:#ccc;">
                                    規格 ({{ detailOrder.series_model }} 系列)
                                </h6>
                            </div>
                            <!-- 規格表格 -->
                            <div style="max-height:calc(100vh - 160px);overflow-y:auto;">
                                <table style="width:100%;border-collapse:collapse;">
                                    <!-- 表頭 -->
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

                        <!-- ====== 右側白色面板：渲染圖 + 客戶資料 ====== -->
                        <div class="col-6 d-flex flex-column" style="color:#333; margin-left:15px; width:calc(50% - 15px);">
                            <!-- ELEVATOR STYLE + 關閉按鈕 -->
                            <div class="d-flex justify-content-between align-items-center px-4 pt-3 pb-2" style="background:#FFF; border-top-left-radius:0.5rem;">
                                <h6 class="fw-bold mb-0" style="color:#E3E3E3; letter-spacing:1px;">ELEVATOR STYLE</h6>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" style="font-size:0.7rem;"></button>
                            </div>
                            <!-- 渲染圖 -->
                            <div class="text-center px-4 mb-3 flex-grow-1 d-flex align-items-center justify-content-center" style="background:#FFF; max-height:320px; overflow:hidden; border-bottom-left-radius:0.5rem; border-bottom-right-radius:0.5rem;">
                                <img
                                    v-if="detailOrder.elevator_image"
                                    :src="detailOrder.elevator_image"
                                    alt="電梯渲染圖"
                                    class="img-fluid rounded shadow-sm"
                                    style="max-height:320px; max-width:610px; object-fit:contain;"
                                />
                                <div v-else class="text-muted py-5">
                                    <i class="fa fa-image fa-3x mb-2 d-block opacity-50"></i>
                                    <span>電梯渲染圖</span>
                                </div>
                            </div>

                            <!-- 客戶訂單資料 -->
                            <div class="px-4 pb-3" style="background:#FFF; padding-top:10px; border-radius:0.5rem;">
                                <div class="fw-bold pb-1" style="font-size:0.95rem; color:#1E2939;">客戶訂單資料</div>
                                <div class="row g-2 mb-2">
                                    <div class="col-6">
                                        <label class="form-label text-muted mb-0" style="font-size:0.7rem; color:#6A7282;">客戶名稱</label>
                                        <div class="form-control form-control-sm bg-light" style="font-size:0.8rem; background-color:transparent !important; border:1px solid #E5E7EB;">{{ detailOrder.customer_name || '—' }}</div>
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label text-muted mb-0" style="font-size:0.7rem;">專案名稱</label>
                                        <div class="form-control form-control-sm bg-light" style="font-size:0.8rem; background-color:transparent !important; border:1px solid #E5E7EB;">{{ detailOrder.project_name || '—' }}</div>
                                    </div>
                                </div>
                                <div class="mb-2">
                                    <label class="form-label text-muted mb-0" style="font-size:0.7rem; color:#6A7282;">施工地點</label>
                                    <div class="form-control form-control-sm bg-light" style="font-size:0.8rem; background-color:transparent !important; border:1px solid #E5E7EB;">{{ detailOrder.construction_location || '—' }}</div>
                                </div>
                                <div class="row g-2 mb-2">
                                    <div class="col-6">
                                        <label class="form-label text-muted mb-0" style="font-size:0.7rem; color:#6A7282;">客戶窗口姓名</label>
                                        <div class="form-control form-control-sm bg-light" style="font-size:0.8rem; background-color:transparent !important; border:1px solid #E5E7EB;">{{ detailOrder.customer_contact_name || '—' }}</div>
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label text-muted mb-0" style="font-size:0.7rem; color:#6A7282;">客戶窗口信箱</label>
                                        <div class="form-control form-control-sm bg-light" style="font-size:0.8rem; background-color:transparent !important; border:1px solid #E5E7EB;">{{ detailOrder.customer_contact_email || '—' }}</div>
                                    </div>
                                </div>
                                <div class="row g-2 mb-2">
                                    <div class="col-6">
                                        <label class="form-label text-muted mb-0" style="font-size:0.7rem; color:#6A7282;">業務人員姓名</label>
                                        <div class="form-control form-control-sm bg-light" style="font-size:0.8rem; background-color:transparent !important; border:1px solid #E5E7EB;">{{ detailOrder.sales_name || '—' }}</div>
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label text-muted mb-0" style="font-size:0.7rem; color:#6A7282;">業務人員信箱</label>
                                        <div class="form-control form-control-sm bg-light" style="font-size:0.8rem; background-color:transparent !important; border:1px solid #E5E7EB;">{{ detailOrder.sales_email || '—' }}</div>
                                    </div>
                                </div>
                                <div class="d-flex justify-content-between align-items-end">
                                    <div>
                                        <span class="text-muted" style="font-size:0.75rem;">業務連絡電話：</span>
                                        <span style="font-size:0.85rem;">{{ detailOrder.sales_phone || '—' }}</span>
                                    </div>
                                    <a
                                        :href="route('admin.history-order.export-pdf', detailOrder.id)"
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
    </Teleport>
</template>

<script>
import { reactive, ref, computed, nextTick, onMounted, onBeforeUnmount } from "vue";
import { Link, router } from "@inertiajs/vue3";
import Layout from "@/Shared/Admin/Layout.vue";
import BreadcrumbItem from "@/Shared/Admin/Partials/BreadcrumbItem.vue";
import Modal from "bootstrap/js/dist/modal";

export default {
    components: { BreadcrumbItem, Link },
    props: {
        items:              { type: Object, default: () => ({ data: [], total: 0, current_page: 1, last_page: 1 }) },
        seriesModelOptions: { type: Array, default: () => [] },
        cabinSpecFields:    { type: Object, default: () => ({}) },
        entranceSpecFields: { type: Object, default: () => ({}) },
        filters:            { type: Object, default: () => ({}) },
    },
    setup(props) {
        const selectedIds = ref([]);
        const detailModalRef = ref(null);
        const detailOrder = ref(null);
        let modalInstance = null;

        onMounted(() => {
            if (detailModalRef.value) {
                modalInstance = new Modal(detailModalRef.value, {
                    backdrop: 'static',
                    keyboard: false,
                });
                detailModalRef.value.addEventListener('hidden.bs.modal', () => {
                    detailOrder.value = null;
                });
            }
        });

        onBeforeUnmount(() => {
            if (modalInstance) {
                modalInstance.dispose();
                modalInstance = null;
            }
        });

        const openDetail = (item) => {
            detailOrder.value = item;
            nextTick(() => {
                if (modalInstance) {
                    modalInstance.show();
                }
            });
        };

        const closeDetail = () => {
            if (modalInstance) {
                modalInstance.hide();
            }
        };

        const getCabinSpec = (key) => {
            return detailOrder.value?.cabin_specs?.[key] || null;
        };

        const getEntranceSpec = (key) => {
            return detailOrder.value?.entrance_specs?.[key] || null;
        };

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
                        value: detailOrder.value?.cabin_specs?.[cKey] || null,
                    } : null,
                    entrance: eKey ? {
                        icon: props.entranceSpecFields[eKey].icon,
                        label: props.entranceSpecFields[eKey].label,
                        value: detailOrder.value?.entrance_specs?.[eKey] || null,
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
                const parts = line.split('\u3000'); // 全形空格分隔
                if (parts.length >= 2) {
                    return { value: parts[0].trim(), subLabel: parts.slice(1).join('\u3000').trim() };
                }
                return { value: line.trim(), subLabel: null };
            });
        };

        const filterForm = reactive({
            date:          props.filters?.date || '',
            order_name:    props.filters?.order_name || '',
            series_model:  props.filters?.series_model || '',
            sales_name:    props.filters?.sales_name || '',
        });

        const buildQuery = (extra = {}) => {
            const params = {};
            if (filterForm.date)          params.date          = filterForm.date;
            if (filterForm.order_name)    params.order_name    = filterForm.order_name;
            if (filterForm.series_model)  params.series_model  = filterForm.series_model;
            if (filterForm.sales_name)    params.sales_name    = filterForm.sales_name;
            return { ...params, ...extra };
        };

        const applyFilter = () => {
            router.get(route('admin.history-order.index'), buildQuery(), {
                preserveState: true,
                preserveScroll: true,
            });
        };

        const resetFilter = () => {
            filterForm.date = '';
            filterForm.order_name = '';
            filterForm.series_model = '';
            filterForm.sales_name = '';
            router.get(route('admin.history-order.index'), {}, {
                preserveState: true,
                preserveScroll: true,
            });
        };

        const goToPage = (page) => {
            if (page < 1 || page > props.items.last_page) return;
            router.get(route('admin.history-order.index'), buildQuery({ page }), {
                preserveState: true,
                preserveScroll: true,
            });
        };

        const pageRange = computed(() => {
            const total = props.items.last_page;
            const current = props.items.current_page;
            if (total <= 7) {
                return Array.from({ length: total }, (_, i) => i + 1);
            }
            const pages = [];
            pages.push(1);
            if (current > 3) pages.push('...');
            for (let i = Math.max(2, current - 1); i <= Math.min(total - 1, current + 1); i++) {
                pages.push(i);
            }
            if (current < total - 2) pages.push('...');
            pages.push(total);
            return pages;
        });

        const isAllSelected = computed(() => {
            if (props.items.data.length === 0) return false;
            return props.items.data.every(item => selectedIds.value.includes(item.id));
        });

        const toggleSelectAll = () => {
            if (isAllSelected.value) {
                selectedIds.value = [];
            } else {
                selectedIds.value = props.items.data.map(item => item.id);
            }
        };

        const toggleSort = (column) => {
            router.get(route('admin.history-order.index'), buildQuery({
                sort: column,
                direction: 'desc',
            }), {
                preserveState: true,
                preserveScroll: true,
            });
        };

        const formatDate = (dateStr) => {
            if (!dateStr) return '';
            const d = new Date(dateStr);
            const y = d.getFullYear();
            const m = String(d.getMonth() + 1).padStart(2, '0');
            const day = String(d.getDate()).padStart(2, '0');
            return `${y}/${m}/${day}`;
        };

        const exportUrl = computed(() => {
            const base = route('admin.history-order.export');
            if (selectedIds.value.length > 0) {
                const params = new URLSearchParams();
                selectedIds.value.forEach(id => params.append('ids[]', id));
                return base + '?' + params.toString();
            }
            const params = new URLSearchParams(buildQuery());
            return base + '?' + params.toString();
        });

        return {
            filterForm,
            selectedIds,
            detailModalRef,
            detailOrder,
            specRows,
            openDetail,
            closeDetail,
            getCabinSpec,
            getEntranceSpec,
            formatSpecLines,
            applyFilter,
            resetFilter,
            goToPage,
            pageRange,
            isAllSelected,
            toggleSelectAll,
            toggleSort,
            formatDate,
            exportUrl,
        };
    },
    layout: Layout,
};
</script>
