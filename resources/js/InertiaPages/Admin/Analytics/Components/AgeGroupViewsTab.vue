<template>
    <BaseAnalyticsTable
        :categories="categories"
        :dateRange="dateRange"
        :columns="columns"
        :defaultOrder="[[2, 'desc']]"
        :routeName="routeName"
    />
</template>

<script setup>
import { computed } from 'vue';
import BaseAnalyticsTable from './BaseAnalyticsTable.vue';
import { useAnalyticsTable } from '@/composables/admin/useAnalyticsTable';

/**
 * Tab 2: 年齡層總觀看人次統計
 *
 * 顯示欄位：
 * - 分類名稱
 * - 0-10歲總觀看人次
 * - 11-20歲總觀看人次
 * - 21-30歲總觀看人次
 * - 31-40歲總觀看人次
 * - 41-50歲總觀看人次
 * - 51-60歲總觀看人次
 * - 61+歲總觀看人次
 */

// Props 定義
const props = defineProps({
    categories: {
        type: Object,
        required: true,
        default: () => ({ data: [] }),
    },
    dateRange: {
        type: Object,
        required: true,
        default: () => ({ start: '', end: '' }),
    },
    routeName: {
        type: String,
        required: true,
        default: 'admin.analytics.articles',
    },
});

// 使用 composable 的工具方法
const { createBaseColumns, createNumberColumn } = useAnalyticsTable({ props });

// 定義表格欄位
const columns = computed(() => [
    ...createBaseColumns(),
    createNumberColumn("0-10歲總觀看人次", "age_0_10", "130px"),
    createNumberColumn("11-20歲總觀看人次", "age_11_20", "130px"),
    createNumberColumn("21-30歲總觀看人次", "age_21_30", "130px"),
    createNumberColumn("31-40歲總觀看人次", "age_31_40", "130px"),
    createNumberColumn("41-50歲總觀看人次", "age_41_50", "130px"),
    createNumberColumn("51-60歲總觀看人次", "age_51_60", "130px"),
    createNumberColumn("61+歲總觀看人次", "age_61_plus", "130px"),
]);
</script>
