<template>
    <!-- Page Content -->
    <div class="content">

        <BreadcrumbItem />

        <!-- 頁面標題 -->
        <div class="block block-rounded">
            <div class="block-header block-header-default">
                <h3 class="block-title">{{ pageTitle }}</h3>
                <div class="block-options">
                    <span class="text-muted">{{ pageDescription }}</span>
                </div>
            </div>
        </div>

        <!-- 日期篩選 + 主分類篩選 -->
        <div class="block block-rounded">
            <div class="block-content">
                <div class="row g-3">
                    <!-- 起始日期 -->
                    <div class="col-md-3">
                        <label class="form-label">起始日期</label>
                        <input type="date" v-model="searchParams.start_date" class="form-control" />
                    </div>

                    <!-- 結束日期 -->
                    <div class="col-md-3">
                        <label class="form-label">結束日期</label>
                        <input type="date" v-model="searchParams.end_date" class="form-control" />
                    </div>

                    <!-- 主分類篩選 -->
                    <div class="col-md-3">
                        <label class="form-label">主分類篩選</label>
                        <select v-model="searchParams.parent_category_id" class="form-select">
                            <option :value="null">全部主分類</option>
                            <option v-for="category in mainCategories" :key="category.id" :value="category.id">
                                {{ category.name }}
                            </option>
                        </select>
                    </div>

                    <!-- 操作按鈕 -->
                    <div class="col-md-3 d-flex align-items-end gap-2">
                        <button type="button" @click="handleSearch" class="btn btn-primary flex-fill">
                            <i class="fa fa-search me-1"></i>查詢
                        </button>
                        <button type="button" @click="handleReset" class="btn btn-secondary flex-fill">
                            <i class="fa fa-undo me-1"></i>重置
                        </button>
                    </div>
                </div>

                <!-- 快速日期選擇 -->
                <div class="row mt-2">
                    <div class="col-12">
                        <div class="btn-group btn-group-sm" role="group">
                            <button type="button" @click="setDateRange(7)" class="btn btn-outline-secondary">最近 7 天</button>
                            <button type="button" @click="setDateRange(30)" class="btn btn-outline-secondary">最近 30 天</button>
                            <button type="button" @click="setDateRange(90)" class="btn btn-outline-secondary">最近 90 天</button>
                            <button type="button" @click="setDateRange(180)" class="btn btn-outline-secondary">最近 180 天</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tab 介面 -->
        <AnalyticsTabs :tabs="tabList" @tab-changed="handleTabChange">
            <!-- Tab 1: 性別總觀看人次 -->
            <template #tab-0>
                <GenderViewsTab :categories="categories" :dateRange="dateRange" :routeName="routeName" />
            </template>

            <!-- Tab 2: 年齡層總觀看人次 -->
            <template #tab-1>
                <AgeGroupViewsTab :categories="categories" :dateRange="dateRange" :routeName="routeName" />
            </template>

            <!-- Tab 3: 男性年齡層總觀看人次 -->
            <template #tab-2>
                <MaleAgeGroupViewsTab :categories="categories" :dateRange="dateRange" :routeName="routeName" />
            </template>

            <!-- Tab 4: 女性年齡層總觀看人次 -->
            <template #tab-3>
                <FemaleAgeGroupViewsTab :categories="categories" :dateRange="dateRange" :routeName="routeName" />
            </template>
        </AnalyticsTabs>
    </div>
</template>

<script setup>
import { router } from '@inertiajs/vue3';
import { ref, provide, computed } from 'vue';
import Layout from '@/Shared/Admin/Layout.vue';
import BreadcrumbItem from '@/Shared/Admin/Partials/BreadcrumbItem.vue';
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
 * 統一的子分類統計報表頁面
 *
 * 功能：
 * - 支援影音和節目兩種類型
 * - 顯示子分類（影集_台劇、影集_韓劇、電影_動作片等）的統計資料
 * - 支援主分類篩選（可篩選特定主分類下的子分類）
 * - 支援日期區間篩選與快速日期選擇
 * - 4 個 Tab 切換不同維度的統計資料
 */

// Props 定義
const props = defineProps({
    // 內容類型（drama 或 program）
    contentType: {
        type: String,
        required: true,
        validator: (value) => ['drama', 'program'].includes(value),
    },
    // 分類資料
    categories: {
        type: Object,
        required: true,
        default: () => ({ data: [], total: 0 }),
    },
    // 主分類清單
    mainCategories: {
        type: Array,
        required: true,
        default: () => [],
    },
    // 日期區間
    dateRange: {
        type: Object,
        required: true,
        default: () => ({
            start: '',
            end: '',
        }),
    },
    // 篩選條件
    filters: {
        type: Object,
        default: () => ({
            parent_category_id: null,
        }),
    },
});

// 計算屬性：根據 contentType 動態設定
const pageTitle = computed(() => {
    return props.contentType === 'drama' ? '影音子分類統計' : '節目子分類統計';
});

const pageDescription = computed(() => {
    return props.contentType === 'drama'
        ? '查看影音子分類的觀看數據與人口統計分析'
        : '查看節目子分類的觀看數據與人口統計分析';
});

const routeName = computed(() => {
    return props.contentType === 'drama'
        ? 'admin.analytics.dramas.sub-categories'
        : 'admin.analytics.programs.sub-categories';
});

// 搜尋參數（包含日期和主分類篩選）
const searchParams = ref({
    start_date: props.dateRange.start,
    end_date: props.dateRange.end,
    parent_category_id: props.filters.parent_category_id,
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

// 快速設定日期區間（設定完成後自動執行搜尋）
const setDateRange = (days) => {
    const today = new Date();
    const startDate = new Date(today);
    startDate.setDate(today.getDate() - days);

    searchParams.value.end_date = today.toISOString().split('T')[0];
    searchParams.value.start_date = startDate.toISOString().split('T')[0];

    // ✅ 自動執行搜尋
    handleSearch();
};

// 執行搜尋
const handleSearch = () => {
    const params = {
        start_date: searchParams.value.start_date,
        end_date: searchParams.value.end_date,
    };

    // 只有在選擇了主分類時才加入 search_params（轉換為整數）
    if (searchParams.value.parent_category_id) {
        params.search_params = {
            parent_category_id: parseInt(searchParams.value.parent_category_id),
        };
    }

    router.get(route(routeName.value), params, {
        preserveState: true,
        preserveScroll: true,
    });
};

// 重置搜尋
const handleReset = () => {
    // 重置日期為最近 30 天
    const today = new Date();
    const thirtyDaysAgo = new Date(today);
    thirtyDaysAgo.setDate(today.getDate() - 30);

    searchParams.value.start_date = thirtyDaysAgo.toISOString().split('T')[0];
    searchParams.value.end_date = today.toISOString().split('T')[0];
    searchParams.value.parent_category_id = null;

    handleSearch();
};

// Tab 切換處理
const handleTabChange = () => {
    // Tab 切換邏輯（分頁設定由 provide/inject 共享）
};
</script>

<style scoped>
/* 頁面樣式（已由各 Tab 組件處理） */
</style>
