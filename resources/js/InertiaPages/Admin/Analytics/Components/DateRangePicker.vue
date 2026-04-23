<template>
    <div class="block block-rounded">
        <div class="block-content">
            <form @submit.prevent="handleSubmit" class="row g-3 align-items-end">
                <!-- 起始日期 -->
                <div class="col-md-4">
                    <label class="form-label" for="start-date">起始日期</label>
                    <input
                        type="date"
                        class="form-control"
                        id="start-date"
                        v-model="localDateRange.start"
                        :max="maxStartDate"
                        required
                    />
                </div>

                <!-- 結束日期 -->
                <div class="col-md-4">
                    <label class="form-label" for="end-date">結束日期</label>
                    <input
                        type="date"
                        class="form-control"
                        id="end-date"
                        v-model="localDateRange.end"
                        :min="localDateRange.start"
                        :max="today"
                        required
                    />
                </div>

                <!-- 查詢按鈕 -->
                <div class="col-md-4">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fa fa-search me-1"></i>
                        查詢
                    </button>
                </div>

                <!-- 快速選擇按鈕 -->
                <div class="col-12">
                    <div class="btn-group" role="group">
                        <button
                            type="button"
                            class="btn btn-sm btn-outline-secondary"
                            @click="setQuickRange(7)"
                        >
                            最近 7 天
                        </button>
                        <button
                            type="button"
                            class="btn btn-sm btn-outline-secondary"
                            @click="setQuickRange(30)"
                        >
                            最近 30 天
                        </button>
                        <button
                            type="button"
                            class="btn btn-sm btn-outline-secondary"
                            @click="setQuickRange(90)"
                        >
                            最近 90 天
                        </button>
                        <button
                            type="button"
                            class="btn btn-sm btn-outline-secondary"
                            @click="setQuickRange(180)"
                        >
                            最近 180 天
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { router } from '@inertiajs/vue3';

/**
 * 日期區間選擇器組件
 *
 * 功能：
 * - 起始/結束日期選擇
 * - 日期驗證（起始日期 <= 結束日期 <= 今天）
 * - 快速選擇（7/30/90/180 天）
 * - 透過 Inertia 更新 URL 參數並重新載入必要資料（不刷新整個頁面）
 * - BaseAnalyticsTable 的 watch 監聽 props 變化後自動重新載入 DataTable
 */

// Props 定義
const props = defineProps({
    // 日期區間 { start, end }
    dateRange: {
        type: Object,
        required: true,
        default: () => ({
            start: '',
            end: '',
        }),
    },
});

// Emits 定義
const emit = defineEmits(['update:dateRange']);

// 本地日期狀態
const localDateRange = ref({
    start: props.dateRange.start,
    end: props.dateRange.end,
});

// 今天日期（YYYY-MM-DD 格式）
const today = computed(() => {
    const date = new Date();
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
});

// 起始日期最大值（不能超過結束日期）
const maxStartDate = computed(() => {
    return localDateRange.value.end || today.value;
});

/**
 * 監聽 props 變化，同步本地狀態
 */
watch(
    () => props.dateRange,
    (newRange) => {
        localDateRange.value = {
            start: newRange.start,
            end: newRange.end,
        };
    },
    { deep: true }
);

/**
 * 處理表單提交
 * ✅ 使用 Inertia 更新 URL 參數，觸發 DataTable 重新載入
 */
const handleSubmit = () => {
    router.get(
        route(route().current()),
        {
            start_date: localDateRange.value.start,
            end_date: localDateRange.value.end,
        },
        {
            preserveState: true,
            preserveScroll: true,
            only: ['dateRange', 'categories'],  // ✅ 只重新載入必要資料
        }
    );
};

/**
 * 設定快速日期範圍
 * @param {number} days - 往前推算的天數
 */
const setQuickRange = (days) => {
    // 使用 computed today 確保結束日期是今天
    const endDate = today.value;

    // 計算起始日期
    const start = new Date();
    start.setDate(start.getDate() - days + 1); // +1 因為包含今天
    const startDate = start.toISOString().split('T')[0];

    localDateRange.value = {
        start: startDate,
        end: endDate,
    };

    // 自動提交查詢
    handleSubmit();
};
</script>

<style scoped>
/* 表單樣式優化 */
.form-label {
    font-weight: 600;
    margin-bottom: 0.5rem;
}

.btn-group {
    flex-wrap: wrap;
}

.btn-group .btn {
    margin-bottom: 0.5rem;
}
</style>
