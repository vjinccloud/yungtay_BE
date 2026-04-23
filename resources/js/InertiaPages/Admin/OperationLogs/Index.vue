<template>
    <div class="content">
        <BreadcrumbItem />

        <div class="block block-rounded">
            <div class="block-header block-header-default">
                <h3 class="block-title">
                    <button type="button"
                            class="btn btn-sm btn-outline-primary"
                            @click="toggleFilter">
                        <i class="fas fa-filter me-1"></i>
                        搜尋
                        <i class="fas fa-chevron-up ms-2"
                        :class="{ 'fa-rotate-180': filterExpanded }"
                        style="transition: transform 0.3s ease; font-size: 12px;"></i>
                    </button>
                </h3>
            </div>

            <div class="block-content block-content-full">
                <!-- 搜尋表單 -->
                <div v-show="filterExpanded" class="mb-4">
                    <form @submit.prevent="searchData">
                        <div class="row g-3">
                            <!-- 管理員名稱 - Autocomplete -->
                            <div class="col-md-4">
                                <label class="form-label">管理員名稱</label>
                                <!-- 直接使用 Select2Input 元件 -->
                                <Select2Input
                                  ref="select2Ref"
                                  v-model="searchParams.user_id"
                                  :options="adminOptions"
                                  placeholder="請選擇管理員"
                                />
                            </div>

                            <!-- 操作類型 -->
                            <div class="col-md-4">
                                <label class="form-label">操作類型</label>
                                <select class="form-select" v-model="searchParams.action_type">
                                    <option value="">請選擇操作類型</option>
                                    <option value="Login">登入</option>
                                    <option value="Add">新增</option>
                                    <option value="Edit">編輯</option>
                                    <option value="Delete">刪除</option>
                                    <option value="Reply">回覆信件</option>
                                </select>
                            </div>

                            <!-- IP位址 -->
                            <div class="col-md-4">
                                <label class="form-label">IP位址</label>
                                <input
                                    type="text"
                                    class="form-control"
                                    v-model="searchParams.ip_address"
                                    placeholder="請輸入IP位址">
                            </div>

                            <!-- 開始日期 - 使用 DatePicker 組件 -->
                            <div class="col-md-6">
                                <DatePicker
                                    v-model="searchParams.start_date"
                                    label="開始日期"
                                    placeholder="選擇開始日期"
                                />
                            </div>

                            <!-- 結束日期 - 使用 DatePicker 組件 -->
                            <div class="col-md-6">
                                <DatePicker
                                    v-model="searchParams.end_date"
                                    label="結束日期"
                                    placeholder="選擇結束日期"
                                />
                            </div>

                            <!-- 按鈕 -->
                            <div class="col-12">
                                <div class="d-flex gap-2 mt-3">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-search me-1"></i>搜尋
                                    </button>
                                    <button type="button" class="btn btn-secondary" @click="resetSearch">
                                        <i class="fas fa-refresh me-1"></i>重置
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- DataTable -->
                <DataTable
                    class="table table-bordered table-striped table-vcenter js-dataTable-full"
                    :columns="columns"
                    :options="options"
                    ref="table"
                />
            </div>
        </div>
    </div>
</template>

<script setup>
import Layout from "@/Shared/Admin/Layout.vue";
import BreadcrumbItem from "@/Shared/Admin/Partials/BreadcrumbItem.vue";
import DatePicker from "@/Plugin/DatePicker.vue"; // 引入 DatePicker 組件
import Select2Input from "@/Plugin/Select2Input.vue"; // 引入 Select2Input 組件
import DataTablesCore from "datatables.net-bs5";
import DataTable from "datatables.net-vue3";
import { ref, reactive, onMounted, computed, nextTick ,toRaw } from 'vue';
DataTable.use(DataTablesCore);
import DataTableHelper from "@/utils/datatableHelper";
import { usePage } from '@inertiajs/vue3';

const table = ref(null);
const rows = ref([]);
const select2Ref = ref(null);
const dt = ref(null); // 改為 ref

// 搜尋功能
const filterExpanded = ref(false);
const searchParams = reactive({
    user_id: '',
    action_type: '',
    ip_address: '',
    start_date: '',
    end_date: ''
});

const page = usePage();
const adminOptions = computed(() =>
  (page.props.adminUser || []).map(({ id, name }) => ({
    value: id,
    text: name,
  }))
)

// 切換搜尋區塊
const toggleFilter = () => {
    filterExpanded.value = !filterExpanded.value;
};

// 執行搜尋
const searchData = () => {
    if (dt.value && dt.value.ajax) {
        dt.value.ajax.reload();
    }
};

// 重置搜尋
const resetSearch = () => {
    Object.keys(searchParams).forEach(key => {
        searchParams[key] = '';
    });
    if (select2Ref.value) {
        select2Ref.value.clearSelection();
    }
    if (dt.value) {
        if (typeof dt.value.search === 'function') {
            dt.value.search('').draw();
        }
        if (dt.value.ajax) {
            dt.value.ajax.reload();
        }
    }
};

const columns = [
    {
        title: "#",
        data: null,
        className: "text-center",
        orderable: false,
        render: (data, type, row, meta) => {
            const currentPageStart = meta.settings._iDisplayStart;
            return currentPageStart + meta.row + 1;
        },
    },
    { title: "管理員名稱", data: "name" },
    { title: "管理員帳號", data: "email" },
    { title: "操作紀錄", data: "message", orderable: false },
    { title: "登入IP", data: "ip_address" },
    { title: "建立時間", data: "created_at" },
].filter(col => col !== null);

const options = reactive({
    ...DataTableHelper.getBaseOptions(),
    ajax: (data, callback) => {
        const searchData = {
            ...data,
            search_params: { ...toRaw(searchParams) },
        };

        DataTableHelper.fetchTableData(
            route("admin.operation-logs"),
            searchData,
            callback,
            rows,
            'operationLogs',
            searchData.search_params
        )
    },
    drawCallback: function () {
        DataTableHelper.defaultDrawCallback();
        DataTableHelper.bindTableButtonEvents({});
    },
    order: [[5, "desc"]],
});

onMounted(async () => {
    try {
        // 等待 DOM 完全載入
        await nextTick();

        if (!table.value) {
            console.error('Table element not found');
            return;
        }

        // 正確地等待 DataTable 初始化
        dt.value = await DataTableHelper.createDataTable(table.value);

        // 檢查並清除預設搜尋內容
        if (dt.value && typeof dt.value.search === 'function') {
            dt.value.search('').draw();
        } else {
            console.warn('DataTable search method not available');
        }
    } catch (error) {
        console.error('DataTable 初始化失敗:', error);
    }
});
</script>

<script>
export default {
    layout: Layout,
};
</script>

<style scoped>
.fa-rotate-180 {
    transform: rotate(180deg);
}

.form-label {
    font-weight: 500;
    color: #495057;
    margin-bottom: 4px;
}

.gap-2 {
    gap: 0.5rem !important;
}
</style>
