<template>
    <BaseAnalyticsTable
        :categories="categories"
        :dateRange="dateRange"
        :columns="columns"
        :defaultOrder="[[6, 'desc']]"
        :routeName="routeName"
    />
</template>

<script setup>
import { computed } from 'vue';
import BaseAnalyticsTable from './BaseAnalyticsTable.vue';
import { useAnalyticsTable } from '@/composables/admin/useAnalyticsTable';

/**
 * Tab 1: 性別總觀看人次統計
 *
 * 顯示欄位：
 * - 分類名稱
 * - 男性總觀看人次
 * - 女性總觀看人次
 * - 會員總觀看人次
 * - 非會員總觀看人次
 * - 總觀看人次
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
    createNumberColumn("男性總觀看人次", "male_views"),
    createNumberColumn("女性總觀看人次", "female_views"),
    createNumberColumn("會員總觀看人次", "member_views"),
    createNumberColumn("非會員總觀看人次", "guest_views", "130px"),
    createNumberColumn("總觀看人次", "total_views", "110px"),
]);
</script>
