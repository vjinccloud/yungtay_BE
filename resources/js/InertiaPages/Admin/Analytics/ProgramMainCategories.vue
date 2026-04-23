<template>
    <!-- Page Content -->
    <div class="content">
        <BreadcrumbItem />

        <!-- 頁面標題 -->
        <div class="block block-rounded">
            <div class="block-header block-header-default">
                <h3 class="block-title">節目主分類統計</h3>
                <div class="block-options">
                    <span class="text-muted">查看節目主分類的觀看數據與人口統計分析</span>
                </div>
            </div>
        </div>

        <!-- 日期篩選 -->
        <DateRangePicker :dateRange="dateRange" />

        <!-- Tab 介面 -->
        <AnalyticsTabs :tabs="tabList" @tab-changed="handleTabChange">
            <!-- Tab 1: 性別總觀看人次 -->
            <template #tab-0>
                <GenderViewsTab :categories="categories" :dateRange="dateRange" routeName="admin.analytics.programs.main-categories" />
            </template>

            <!-- Tab 2: 年齡層總觀看人次 -->
            <template #tab-1>
                <AgeGroupViewsTab :categories="categories" :dateRange="dateRange" routeName="admin.analytics.programs.main-categories" />
            </template>

            <!-- Tab 3: 男性年齡層總觀看人次 -->
            <template #tab-2>
                <MaleAgeGroupViewsTab :categories="categories" :dateRange="dateRange" routeName="admin.analytics.programs.main-categories" />
            </template>

            <!-- Tab 4: 女性年齡層總觀看人次 -->
            <template #tab-3>
                <FemaleAgeGroupViewsTab :categories="categories" :dateRange="dateRange" routeName="admin.analytics.programs.main-categories" />
            </template>
        </AnalyticsTabs>
    </div>
</template>

<script setup>
import { ref, provide } from 'vue';
import Layout from '@/Shared/Admin/Layout.vue';
import BreadcrumbItem from '@/Shared/Admin/Partials/BreadcrumbItem.vue';
import DateRangePicker from './Components/DateRangePicker.vue';
import AnalyticsTabs from './Components/AnalyticsTabs.vue';
import GenderViewsTab from './Components/GenderViewsTab.vue';
import AgeGroupViewsTab from './Components/AgeGroupViewsTab.vue';
import MaleAgeGroupViewsTab from './Components/MaleAgeGroupViewsTab.vue';
import FemaleAgeGroupViewsTab from './Components/FemaleAgeGroupViewsTab.vue';

// Layout 設定
defineOptions({
    layout: Layout
});

/**
 * 節目主分類統計報表頁面
 *
 * 功能：
 * - 顯示節目主分類的統計資料
 * - 4 個 Tab 切換不同維度的統計資料
 * - Tab 1: 性別總觀看人次（男性、女性、會員、訪客、總觀看）
 * - Tab 2: 年齡層總觀看人次（7個年齡區間）
 * - Tab 3: 男性年齡層總觀看人次（男性 × 7個年齡區間）
 * - Tab 4: 女性年齡層總觀看人次（女性 × 7個年齡區間）
 * - 支援日期區間篩選
 */

// Props 定義
const props = defineProps({
    categories: {
        type: Object,
        required: true,
        default: () => ({ data: [], total: 0 }),
    },
    dateRange: {
        type: Object,
        required: true,
        default: () => ({
            start: '',
            end: '',
        }),
    },
});

// Tab 清單設定
const tabList = ref([
    { title: '性別總觀看人次' },
    { title: '年齡層總觀看人次' },
    { title: '男性年齡層總觀看人次' },
    { title: '女性年齡層總觀看人次' },
]);

// 共享的分頁設定（所有 Tab 共用，切換時保持用戶選擇）
const sharedPageLength = ref(10);

// 提供給子組件使用
provide('sharedPageLength', sharedPageLength);

// Tab 切換處理
const handleTabChange = () => {
    // Tab 切換邏輯（分頁設定由 provide/inject 共享）
};
</script>

<style scoped>
/* 頁面樣式（已由各 Tab 組件處理） */
</style>
