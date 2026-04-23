<template>
    <div class="block-content block-content-full">
        <!-- DataTable -->
        <DataTable
            class="table table-bordered table-striped table-vcenter js-dataTable-full"
            :columns="columns"
            :options="tableOptions"
            ref="table"
        />
    </div>
</template>

<script setup>
import { onMounted, nextTick } from 'vue';
import DataTablesCore from "datatables.net-bs5";
import DataTable from "datatables.net-vue3";
import { useAnalyticsTable } from '@/composables/admin/useAnalyticsTable';

DataTable.use(DataTablesCore);

/**
 * Analytics 基礎表格組件
 *
 * 功能：
 * - 通用的 DataTable 表格顯示
 * - 支援自訂欄位配置
 * - 自動格式化數字
 * - 監聽日期區間變化，自動重新載入資料
 */

// Props 定義
const props = defineProps({
    // 表格資料
    categories: {
        type: Object,
        required: true,
        default: () => ({ data: [] }),
    },
    // 日期區間
    dateRange: {
        type: Object,
        required: true,
        default: () => ({ start: '', end: '' }),
    },
    // 欄位配置
    columns: {
        type: Array,
        required: true,
        default: () => [],
    },
    // 路由名稱（用於 DataTable AJAX 請求）
    routeName: {
        type: String,
        required: true,
        default: 'admin.analytics.articles',
    },
    // 額外的表格配置
    tableConfig: {
        type: Object,
        default: () => ({}),
    },
    // 預設排序
    defaultOrder: {
        type: Array,
        default: () => [[2, 'desc']],  // 預設按第3欄（通常是總觀看數）降序
    },
});

// 使用 composable
const {
    table,
    dt,
    tableOptions,
    initializeDataTable,
} = useAnalyticsTable({
    columns: props.columns,
    props,
    routeName: props.routeName,
    tableConfig: props.tableConfig,
    defaultOrder: props.defaultOrder,  // ✅ 傳入 defaultOrder
});

// 元件掛載後初始化
// ✅ 使用 nextTick 確保 Inertia props 完全注入後再初始化 DataTable
// 避免初始化時機過早導致多次 AJAX 請求
onMounted(async () => {
    await nextTick();
    initializeDataTable();
});

// ❌ 移除 watch dateRange：
// DataTable 使用 Inertia 模式，每次請求都會帶上最新的 dateRange
// 不需要額外監聽 dateRange 變化來 reload
//
// 原本的 watch 會導致：
// 1. 點擊排序 → DataTable 自動發送請求（帶 sortColumn）
// 2. Inertia 更新 props → watch 觸發 reload（不帶 sortColumn）
// 3. 造成排序參數丟失
</script>
