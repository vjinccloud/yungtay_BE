<template>
    <BaseAnalyticsTable
        :categories="categories"
        :dateRange="dateRange"
        :columns="columns"
        :routeName="routeName"
        :defaultOrder="[[2, 'desc']]"
    />
</template>

<script setup>
import { computed } from 'vue';
import BaseAnalyticsTable from './BaseAnalyticsTable.vue';
import { useAnalyticsTable } from '@/composables/admin/useAnalyticsTable';

/**
 * Tab 4: 女性年齡層總觀看人次統計
 *
 * 顯示欄位：
 * - 分類名稱
 * - 女性0-10歲總觀看人次
 * - 女性11-20歲總觀看人次
 * - 女性21-30歲總觀看人次
 * - 女性31-40歲總觀看人次
 * - 女性41-50歲總觀看人次
 * - 女性51-60歲總觀看人次
 * - 女性61+歲總觀看人次
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
    createNumberColumn("女性0-10歲總觀看人次", "female_age_0_10", "140px"),
    createNumberColumn("女性11-20歲總觀看人次", "female_age_11_20", "150px"),
    createNumberColumn("女性21-30歲總觀看人次", "female_age_21_30", "150px"),
    createNumberColumn("女性31-40歲總觀看人次", "female_age_31_40", "150px"),
    createNumberColumn("女性41-50歲總觀看人次", "female_age_41_50", "150px"),
    createNumberColumn("女性51-60歲總觀看人次", "female_age_51_60", "150px"),
    createNumberColumn("女性61+歲總觀看人次", "female_age_61_plus", "150px"),
]);
</script>
