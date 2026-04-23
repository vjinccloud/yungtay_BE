<!-- resources/js/InertiaPages/Admin/Members/Index.vue -->
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
                <Link
                    class="btn btn-primary"
                    :href="route('admin.members.add')"
                    v-if="can('admin.members.add')"
                >
                    <i class="fa-solid fa-plus opacity-50 me-1"></i>新增會員
                </Link>
            </div>

            <div class="block-content block-content-full">
                <!-- 搜尋表單 -->
                <div v-show="filterExpanded" class="mb-4">
                    <form @submit.prevent="searchData">
                        <div class="row g-3">
                            <!-- 關鍵字搜尋 -->
                            <div class="col-md-4">
                                <label class="form-label">關鍵字</label>
                                <input
                                    type="text"
                                    class="form-control"
                                    v-model="searchParams.search"
                                    placeholder="姓名、Email、手機號碼">
                            </div>

                            <!-- 縣市搜尋 -->
                            <div class="col-md-4">
                                <label class="form-label">居住縣市</label>
                                <select class="form-select" v-model="searchParams.city_id">
                                    <option value="">請選擇縣市</option>
                                    <option v-for="city in props.cities" :key="city.id" :value="city.id">
                                        {{ city.name }}
                                    </option>
                                </select>
                            </div>

                            <!-- 會員狀態 -->
                            <div class="col-md-4">
                                <label class="form-label">會員狀態</label>
                                <select class="form-select" v-model="searchParams.is_active">
                                    <option value="">請選擇狀態</option>
                                    <option value="1">啟用</option>
                                    <option value="0">停用</option>
                                </select>
                            </div>

                            <!-- 年齡區間 -->
                            <div class="col-md-3">
                                <label class="form-label">最小年齡</label>
                                <input
                                    type="number"
                                    class="form-control"
                                    v-model="searchParams.age_min"
                                    placeholder="例：18"
                                    min="0"
                                    max="150">
                            </div>

                            <div class="col-md-3">
                                <label class="form-label">最大年齡</label>
                                <input
                                    type="number"
                                    class="form-control"
                                    v-model="searchParams.age_max"
                                    placeholder="例：65"
                                    min="0"
                                    max="150">
                            </div>

                            <!-- 註冊日期區間 - 使用 DatePicker 組件 -->
                            <div class="col-md-3">
                                <DatePicker
                                    v-model="searchParams.register_start_date"
                                    label="註冊開始日期"
                                    placeholder="選擇註冊開始日期"
                                />
                            </div>

                            <div class="col-md-3">
                                <DatePicker
                                    v-model="searchParams.register_end_date"
                                    label="註冊結束日期"
                                    placeholder="選擇註冊結束日期"
                                />
                            </div>

                            <!-- 驗證狀態 -->
                            <div class="col-md-4">
                                <label class="form-label">驗證狀態</label>
                                <select class="form-select" v-model="searchParams.verification_status">
                                    <option value="">請選擇驗證狀態</option>
                                    <option value="已完成">已完成</option>
                                    <option value="待驗證">待驗證</option>
                                    <option value="待補充資料">待補充資料</option>
                                </select>
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
import DataTablesCore from "datatables.net-bs5";
import DataTable from "datatables.net-vue3";
import { ref, reactive, onMounted, inject, nextTick } from "vue";
import DataTableHelper from "@/utils/datatableHelper";
import { router } from "@inertiajs/vue3";
import { Link } from "@inertiajs/vue3";
import { useDataTableSearch } from "@/composables";

// 定義 props
const props = defineProps({
    members: Object,
    cities: {
        type: Array,
        default: () => []
    }
});

const can = inject("can");
const sweetAlert = inject('$sweetAlert');

DataTable.use(DataTablesCore);

// 使用 DataTable 搜尋 composable
const {
    searchParams,
    filterExpanded,
    table,
    dt,
    rows,
    toggleFilter,
    searchData,
    resetSearch,
    reloadTable,
    initializeDataTable
} = useDataTableSearch({
    search: '',
    city_id: '',
    is_active: '',
    age_min: '',
    age_max: '',
    register_start_date: '',
    register_end_date: '',
    verification_status: ''
}, {
    route: route("admin.members"),
    dataKey: "members"
});

// 縣市資料 - 改用 props 接收
// const cities = ref([]); // 移除：改用 props.cities

// 表格欄位設定
const columns = [
    {
        title: "#",
        data: null,
        className: "text-center",
        orderable: false,
        width: "50px",
        render: (data, type, row, meta) =>
            meta.settings._iDisplayStart + meta.row + 1,
    },
    {
        title: "會員資訊",
        data: null,
        width: "200px",
        render: (data) => {
            let html = `<div class="fw-bold">${data.name || '-'}</div>`;
            if (data.email) {
                html += `<small class="text-muted">${data.email}</small>`;
            }
            return html;
        },
    },
    {
        title: "手機",
        data: "phone",
        width: "120px",
        className: "text-center",
        defaultContent: "-",
    },
    {
        title: "註冊方式",
        data: "registration_type",
        width: "100px",
        className: "text-center",
    },
    {
        title: "驗證狀態",
        data: "verification_status",
        width: "100px",
        className: "text-center",
        render: (val) => {
            const statusMap = {
                '已完成': 'bg-success',
                '待驗證': 'bg-warning',
                '待補充資料': 'bg-info'
            };
            const badgeClass = statusMap[val] || 'bg-secondary';
            return `<span class="badge ${badgeClass}">${val}</span>`;
        },
    },
    {
        title: "年齡",
        data: "age",
        width: "80px",
        className: "text-center",
        defaultContent: "-",
    },
    {
        title: "居住地區",
        data: "full_address",
        width: "150px",
        defaultContent: "-",
    },
    {
        title: "啟用狀態",
        data: "is_active",
        width: "100px",
        className: "text-center",
        render: (val, type, row) => {
            if (can("admin.members.toggle-status")) {
                return `
                <div class="form-check form-switch">
                    <input
                        class="form-check-input toggle-active checked-btn js-bs-tooltip-enabled"
                        type="checkbox"
                        data-id="${row.id}"
                        data-bs-toggle="tooltip"
                        aria-label="啟用/停用"
                        data-bs-title="啟用/停用"
                        ${val ? "checked" : ""}
                    >
                </div>
                `;
            } else {
                return `
                <span class="badge ${val ? 'bg-success' : 'bg-secondary'}">
                    ${val ? '啟用' : '停用'}
                </span>
                `;
            }
        },
    },
    {
        title: "註冊日期",
        data: "created_at",
        defaultContent: "-",
        className: "text-center",
        width: "150px",
    },
    {
        title: "功能",
        data: null,
        orderable: false,
        width: "80px",
        className: "text-center",
        render: (data) => {
            let btns = "";
            if (can("admin.members.show")) {
                btns += `
                <button
                    type="button"
                    class="btn btn-sm btn-info js-bs-tooltip-enabled show-btn me-2"
                    data-bs-toggle="tooltip"
                    aria-label="檢視"
                    data-bs-title="檢視"
                    data-id="${data.id}"
                >
                    <i class="fa fa-eye"></i>
                </button>`;
            }
            if (can("admin.members.delete")) {
                btns += `
                <button
                    type="button"
                    class="btn btn-sm btn-danger js-bs-tooltip-enabled delete-btn"
                    data-bs-toggle="tooltip"
                    aria-label="刪除"
                    data-bs-title="刪除"
                    data-id="${data.id}"
                >
                    <i class="fa-solid fa-trash"></i>
                </button>`;
            }
            return btns;
        },
    },
];

// 搜尋功能已由 composable 提供


// DataTable 選項
const options = reactive({
    ...DataTableHelper.getBaseOptions(),
    searching: false, // 關閉 DataTable 原生搜尋
    ajax: (data, callback) => {
        const searchData = {
            ...data,
            search_params: searchParams
        };
        DataTableHelper.fetchTableData(
            route("admin.members"),
            searchData,
            callback,
            rows,
            "members",
            searchParams
        );
    },
    drawCallback: () => {
        DataTableHelper.defaultDrawCallback();
        DataTableHelper.bindTableButtonEvents({
            show: showMember,
            delete: destroy,
            check: toggleActive,
        });
    },
    order: [[8, "desc"]], // 依「註冊日期」排序
});

// 檢視會員詳情
const showMember = (id) => {
    router.get(route("admin.members.show", id));
};

const isLoading = inject('isLoading');

// 刪除會員
const destroy = (id) => {
    sweetAlert.deleteConfirm('確認是否刪除此會員', () => {
        isLoading.value = true;
        router.delete(route('admin.members.delete', id), {
            onSuccess: (finalRes) => {
                try {
                    const res = finalRes.props.flash?.result || finalRes.props.result;
                    if (res && res.status) {
                        sweetAlert.resultData(res);
                        reloadTable();
                    } else {
                        sweetAlert.error({ msg: '刪除失敗，請重試！' });
                    }
                } catch (error) {
                    console.error('處理刪除回應時發生錯誤:', error);
                    sweetAlert.error({ msg: '處理回應時發生錯誤' });
                }
            },
            onError: (errors) => {
                console.error('刪除請求失敗:', errors);
                sweetAlert.error({ msg: '刪除失敗，請重試！' });
            },
            onFinish: () => {
                isLoading.value = false;
            }
        });
    });
};

// 切換會員狀態
const toggleActive = (id) => {
    isLoading.value = true;
    router.post(route('admin.members.toggle-status', id), {}, {
        onSuccess: (finalRes) => {
            try {
                const res = finalRes.props.flash?.result || finalRes.props.result;
                if (res && res.status) {
                    sweetAlert.resultData(res);
                    reloadTable();
                } else {
                    sweetAlert.error({ msg: '狀態切換失敗，請重試！' });
                }
            } catch (error) {
                console.error('處理狀態切換回應時發生錯誤:', error);
                sweetAlert.error({ msg: '處理回應時發生錯誤' });
            }
        },
        onError: (errors) => {
            console.error('狀態切換請求失敗:', errors);
            sweetAlert.error({ msg: '狀態切換失敗，請重試！' });
        },
        onFinish: () => {
            isLoading.value = false;
        }
    });
};

// 組件掛載
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
        console.error('組件掛載時發生錯誤:', error);
    }
});
</script>

<script>
export default {
    layout: Layout,
};
</script>
