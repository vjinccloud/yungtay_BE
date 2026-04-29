<!-- Modules/HistoryOrder/Vue/Detail.vue -->
<!-- 歷史訂單 - 檢視頁 -->
<template>
    <div class="content">
        <BreadcrumbItem />

        <div class="block block-rounded">
            <div class="block-header block-header-default d-flex align-items-center">
                <h3 class="block-title">
                    規格 <span class="text-primary">({{ order.series_model }} 系列)</span>
                </h3>
                <div class="block-options">
                    <Link :href="route('admin.history-order.index')" class="btn btn-sm btn-alt-secondary">
                        <i class="fa fa-times"></i>
                    </Link>
                </div>
            </div>

            <div class="block-content block-content-full">
                <div class="row">
                    <!-- 左欄：電梯規格 -->
                    <div class="col-lg-6">
                        <div class="row">
                            <!-- 車廂 -->
                            <div class="col-6">
                                <h6 class="fw-bold border-bottom pb-2 mb-3">
                                    <i class="fa fa-cube me-1 text-primary"></i> 車廂
                                </h6>
                                <div v-for="(field, key) in cabinSpecFields" :key="'cabin-' + key" class="spec-row mb-3">
                                    <div class="d-flex align-items-start">
                                        <div class="spec-icon me-2">
                                            <i :class="'fa ' + field.icon" class="text-muted"></i>
                                        </div>
                                        <div>
                                            <div class="spec-label text-muted small">{{ field.label }}</div>
                                            <div class="spec-value fw-semibold">
                                                <template v-if="getCabinSpec(key)">
                                                    <div v-for="(line, idx) in formatSpecLines(getCabinSpec(key))" :key="idx" class="spec-line">
                                                        {{ line }}
                                                    </div>
                                                </template>
                                                <span v-else class="text-muted">—</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- 出入口 -->
                            <div class="col-6">
                                <h6 class="fw-bold border-bottom pb-2 mb-3">
                                    <i class="fa fa-door-open me-1 text-primary"></i> 出入口
                                </h6>
                                <div v-for="(field, key) in entranceSpecFields" :key="'entrance-' + key" class="spec-row mb-3">
                                    <div class="d-flex align-items-start">
                                        <div class="spec-icon me-2">
                                            <i :class="'fa ' + field.icon" class="text-muted"></i>
                                        </div>
                                        <div>
                                            <div class="spec-label text-muted small">{{ field.label }}</div>
                                            <div class="spec-value fw-semibold">
                                                <template v-if="getEntranceSpec(key)">
                                                    <div v-for="(line, idx) in formatSpecLines(getEntranceSpec(key))" :key="idx" class="spec-line">
                                                        {{ line }}
                                                    </div>
                                                </template>
                                                <span v-else class="text-muted">—</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 右欄：渲染圖 + 客戶資料 -->
                    <div class="col-lg-6">
                        <!-- ELEVATOR STYLE -->
                        <div class="text-center mb-4">
                            <h6 class="fw-bold text-muted mb-3">ELEVATOR STYLE</h6>
                            <div class="elevator-image-wrapper border rounded p-3 bg-body-light">
                                <img
                                    v-if="order.elevator_image"
                                    :src="order.elevator_image"
                                    alt="電梯渲染圖"
                                    class="img-fluid rounded"
                                    style="max-height: 300px;"
                                />
                                <div v-else class="text-muted py-5">
                                    <i class="fa fa-image fa-3x mb-2 d-block opacity-50"></i>
                                    <span>電梯渲染圖</span>
                                </div>
                            </div>
                        </div>

                        <!-- 客戶訂單資料 -->
                        <div class="customer-info">
                            <h6 class="fw-bold text-white bg-primary px-3 py-2 rounded-top mb-0">
                                客戶訂單資料
                            </h6>
                            <table class="table table-bordered table-sm mb-0">
                                <tbody>
                                    <tr>
                                        <th class="bg-body-light" style="width: 25%;">客戶名稱</th>
                                        <td style="width: 25%;">{{ order.customer_name || '—' }}</td>
                                        <th class="bg-body-light" style="width: 25%;">專案名稱</th>
                                        <td style="width: 25%;">{{ order.project_name || '—' }}</td>
                                    </tr>
                                    <tr>
                                        <th class="bg-body-light" colspan="1">施工地點</th>
                                        <td colspan="3">{{ order.construction_location || '—' }}</td>
                                    </tr>
                                    <tr>
                                        <th class="bg-body-light">客戶窗口姓名</th>
                                        <td>{{ order.customer_contact_name || '—' }}</td>
                                        <th class="bg-body-light">客戶窗口信箱</th>
                                        <td>{{ order.customer_contact_email || '—' }}</td>
                                    </tr>
                                    <tr>
                                        <th class="bg-body-light">業務人員姓名</th>
                                        <td>{{ order.sales_name || '—' }}</td>
                                        <th class="bg-body-light">業務人員信箱</th>
                                        <td>{{ order.sales_email || '—' }}</td>
                                    </tr>
                                    <tr>
                                        <th class="bg-body-light" colspan="1">業務連絡電話</th>
                                        <td colspan="3">{{ order.sales_phone || '—' }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- 匯出 PDF -->
                <div class="d-flex justify-content-end mt-4 mb-3">
                    <a
                        :href="route('admin.history-order.export-pdf', order.id)"
                        class="btn btn-danger"
                        target="_blank"
                    >
                        <i class="fa fa-file-pdf me-1"></i> 匯出 PDF
                    </a>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
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
        const getCabinSpec = (key) => {
            return props.order.cabin_specs?.[key] || null;
        };

        const getEntranceSpec = (key) => {
            return props.order.entrance_specs?.[key] || null;
        };

        const formatSpecLines = (value) => {
            if (Array.isArray(value)) return value;
            if (typeof value === 'string') return value.split('\n');
            return [String(value)];
        };

        return {
            getCabinSpec,
            getEntranceSpec,
            formatSpecLines,
        };
    },
    layout: Layout,
};
</script>

<style scoped>
.spec-icon {
    width: 24px;
    text-align: center;
    padding-top: 2px;
}

.spec-line {
    line-height: 1.5;
    font-size: 0.875rem;
}

.elevator-image-wrapper {
    min-height: 200px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.customer-info .table th {
    font-size: 0.8125rem;
    font-weight: 600;
    white-space: nowrap;
}

.customer-info .table td {
    font-size: 0.8125rem;
}
</style>
