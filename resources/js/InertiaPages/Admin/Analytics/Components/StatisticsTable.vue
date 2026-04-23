<template>
    <div class="block block-rounded">
        <div class="block-header block-header-default">
            <h3 class="block-title">{{ headerLabel }}</h3>
        </div>
        <div class="block-content">
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-vcenter">
                    <thead>
                        <tr>
                            <th>項目</th>
                            <th class="text-end">觀看數</th>
                            <th class="text-end">百分比</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(item, index) in tableData" :key="index">
                            <td>{{ item.label }}</td>
                            <td class="text-end">{{ formatNumber(item.value) }}</td>
                            <td class="text-end">{{ item.percentage }}%</td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr class="table-active">
                            <th>總計</th>
                            <th class="text-end">{{ formatNumber(total.value) }}</th>
                            <th class="text-end">{{ total.percentage }}%</th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</template>

<script setup>
/**
 * 統計表格共用組件（Phase 1 基本版）
 *
 * 用於顯示各種統計數據的簡單表格
 * - 固定三欄：項目、觀看數、百分比
 * - 支援千分位格式化
 * - 顯示總計行
 */

// Props 定義
const props = defineProps({
    // 表格標題
    headerLabel: {
        type: String,
        required: true,
    },
    // 表格資料 [{ label, value, percentage }, ...]
    tableData: {
        type: Array,
        required: true,
        default: () => [],
    },
    // 總計資料 { value, percentage }
    total: {
        type: Object,
        required: true,
        default: () => ({ value: 0, percentage: 100.0 }),
    },
});

/**
 * 格式化數字：加入千分位逗號
 * @param {number} num - 要格式化的數字
 * @return {string} - 格式化後的字串 (例: 1,234,567)
 */
const formatNumber = (num) => {
    if (!num && num !== 0) return '0';
    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',');
};
</script>

<style scoped>
/* 表格樣式優化 */
.table th,
.table td {
    vertical-align: middle;
}

.table-active {
    font-weight: 600;
}

/* 數字右對齊的欄位加粗 */
.text-end {
    font-weight: 500;
}
</style>
