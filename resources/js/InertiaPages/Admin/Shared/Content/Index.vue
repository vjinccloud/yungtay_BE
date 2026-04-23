<!-- resources/js/InertiaPages/Admin/Shared/Content/Index.vue -->
<!-- 共用的內容列表頁面組件（影音/節目） -->
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
                :href="addRoute"
                v-if="can(addPermission)"
                >
                    <i class="fa-solid fa-plus opacity-50 me-1"></i>{{ addButtonText }}
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
                            :placeholder="`${contentTypeLabel}標題、描述、演員、標籤`">
                    </div>

                    <!-- 主分類 -->
                    <div class="col-md-4">
                        <label class="form-label">主分類</label>
                        <select class="form-select" v-model="searchParams.category_id" @change="onCategoryChange">
                            <option value="">請選擇主分類</option>
                            <option v-for="category in props.categories" :key="category.id" :value="category.id">
                                {{ category.name }}
                            </option>
                        </select>
                    </div>

                    <!-- 子分類 -->
                    <div class="col-md-4">
                        <label class="form-label">子分類</label>
                        <select class="form-select" v-model="searchParams.subcategory_id">
                            <option value="">請選擇子分類</option>
                            <option v-for="subcategory in filteredSubcategories" :key="subcategory.id" :value="subcategory.id">
                                {{ subcategory.name }}
                            </option>
                        </select>
                    </div>

                    <!-- 上架日期區間 -->
                    <div class="col-md-3">
                        <DatePicker
                            v-model="searchParams.published_start_date"
                            label="上架開始日期"
                            placeholder="選擇上架開始日期"
                        />
                    </div>

                    <div class="col-md-3">
                        <DatePicker
                            v-model="searchParams.published_end_date"
                            label="上架結束日期"
                            placeholder="選擇上架結束日期"
                        />
                    </div>

                    <!-- 年份 -->
                    <div class="col-md-3">
                        <label class="form-label">年份</label>
                        <input
                            type="number"
                            class="form-control"
                            v-model="searchParams.release_year"
                            placeholder="請輸入年份"
                            min="1990"
                            :max="new Date().getFullYear() + 5">
                    </div>

                    <!-- 啟用狀態 -->
                    <div class="col-md-3">
                        <label class="form-label">啟用狀態</label>
                        <select class="form-select" v-model="searchParams.is_active">
                            <option value="">請選擇狀態</option>
                            <option value="1">啟用</option>
                            <option value="0">停用</option>
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
            :columns="mergedColumns"
            :options="mergedOptions"
            ref="table"
          />
        </div>
      </div>
    </div>
</template>

<script setup>
import BreadcrumbItem from "@/Shared/Admin/Partials/BreadcrumbItem.vue";
import DatePicker from "@/Plugin/DatePicker.vue";
import DataTablesCore from "datatables.net-bs5";
import DataTable from "datatables.net-vue3";
import { reactive, onMounted, inject, computed, nextTick } from "vue";
import DataTableHelper from "@/utils/datatableHelper";
import { router, Link } from "@inertiajs/vue3";
import { useDataTableSearch } from "@/composables";

DataTable.use(DataTablesCore);

// Props
const props = defineProps({
    contentType: {
        type: String,
        required: true,
        validator: (value) => ['drama', 'program'].includes(value)
    },
    customColumns: {
        type: Array,
        default: () => []
    },
    customOptions: {
        type: Object,
        default: () => ({})
    },
    categories: {
        type: Array,
        default: () => []
    },
    subcategories: {
        type: Array,
        default: () => []
    }
})

// Injects
const can = inject("can");
const sweetAlert = inject('$sweetAlert');
const isLoading = inject('isLoading')

// 🔥 計算屬性 - 必須在 useDataTableSearch 之前定義
const config = computed(() => {
    const configs = {
        drama: {
            title: '影音管理',
            addButtonText: '新增影音',
            addRoute: 'admin.dramas.add',
            addPermission: 'admin.dramas.add',
            editPermission: 'admin.dramas.edit',
            deletePermission: 'admin.dramas.delete',
            viewLogsPermission: 'admin.dramas.view-logs',
            indexRoute: 'admin.dramas',
            editRoute: 'admin.dramas.edit',
            deleteRoute: 'admin.dramas.delete',
            toggleRoute: 'admin.dramas.toggle-active',
            responseKey: 'dramas',
            frontendRoute: 'drama.videos.index'
        },
        program: {
            title: '節目管理',
            addButtonText: '新增節目',
            addRoute: 'admin.programs.add',
            addPermission: 'admin.programs.add',
            editPermission: 'admin.programs.edit',
            deletePermission: 'admin.programs.delete',
            viewLogsPermission: 'admin.programs.view-logs',
            indexRoute: 'admin.programs',
            editRoute: 'admin.programs.edit',
            deleteRoute: 'admin.programs.delete',
            toggleRoute: 'admin.programs.toggle-active',
            responseKey: 'programs',
            frontendRoute: 'program.videos.index'
        }
    }
    return configs[props.contentType]
})

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
    category_id: '',
    subcategory_id: '',
    published_start_date: '',
    published_end_date: '',
    release_year: '',
    is_active: ''
}, {
    route: computed(() => route(config.value.indexRoute)),
    dataKey: computed(() => config.value.responseKey)
})

const title = computed(() => config.value.title)
const addButtonText = computed(() => config.value.addButtonText)
const addRoute = computed(() => route(config.value.addRoute))
const addPermission = computed(() => config.value.addPermission)

// 內容類型標籤
const contentTypeLabel = computed(() => {
    return props.contentType === 'drama' ? '影音' : '節目'
})

// 子分類篩選（根據主分類）
const filteredSubcategories = computed(() => {
    if (!searchParams.category_id) {
        return props.subcategories
    }
    return props.subcategories.filter(sub => sub.parent_id == searchParams.category_id)
})

// 主分類變更時重置子分類
const onCategoryChange = () => {
    searchParams.subcategory_id = ''
}

// 預設欄位設定
const defaultColumns = computed(() => [
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
        title: "標題（中文）",
        data: null,
        width: "200px",
        render: (data) => data.title_zh || "",
    },
    {
        title: "主分類 - 子分類",
        data: null,
        width: "150px",
        render: (data) => {
            const categoryName = data.category_name || '';
            const subcategoryName = data.subcategory_name || '';

            if (subcategoryName) {
                return `${categoryName} - ${subcategoryName}`;
            }
            return categoryName || '-';
        }
    },
    {
        title: "年份",
        data: "release_year",
        className: "text-center",
        width: "80px",
        defaultContent: "-",
    },
    {
        title: "季數",
        data: "season_number",
        className: "text-center",
        width: "80px",
        render: (data) => {
            return data ? `共 ${data} 季` : '-';
        }
    },
    {
        title: "總集數",
        data: "episodes_count",
        className: "text-center",
        width: "80px",
        defaultContent: "0",
    },
    {
        title: "收藏人數",
        data: "collection_count",
        className: "text-center",
        width: "90px",
        defaultContent: "0",
    },
    {
        title: "總觀看次數",
        data: "total_views",
        className: "text-center",
        width: "110px",
        defaultContent: "0",
        render: (data, type, row) => {
            if (can(config.value.viewLogsPermission)) {
                return `<button type="button"
                    class="btn btn-sm btn-outline-info js-bs-tooltip-enabled view-logs-btn"
                    data-bs-toggle="tooltip"
                    data-bs-title="查看觀看記錄"
                    data-id="${row.id}"
                    data-title="${row.title_zh || ''}">
                    <i class="fa fa-eye me-1"></i>${Number(data ?? 0).toLocaleString()}
                </button>`;
            } else {
                return `<span class="badge bg-info">${Number(data ?? 0).toLocaleString()}</span>`;
            }
        },
    },
    {
        title: "上架日期",
        data: "published_date",
        className: "text-center",
        width: "120px",
        defaultContent: "-",
    },
    {
        title: "啟用狀態",
        data: "is_active",
        className: "text-center",
        width: "100px",
        render: (val, type, row) => {
            if (can(config.value.editPermission)) {
                return `
                <div class="form-check form-switch">
                    <input
                    class="form-check-input toggle-active checked-btn js-bs-tooltip-enabled"
                    type="checkbox"
                    data-id="${row.id}"
                    data-bs-toggle="tooltip"
                    aria-label="啟用/停用"
                    data-bs-title="啟用/停用"
                    ${val ? "checked" : ""}>
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
        title: "前台",
        data: null,
        width: "60px",
        className: "text-center",
        orderable: false,
        render: (data) => {
            const routeParams = props.contentType === 'drama'
                ? { dramaId: data.id }
                : { programId: data.id };
            const frontendUrl = route(config.value.frontendRoute, routeParams);
            return `<a href="${frontendUrl}" target="_blank" class="btn btn-sm btn-outline-primary js-bs-tooltip-enabled" title="查看前台" data-bs-toggle="tooltip" data-bs-title="查看前台">
                <i class="fa fa-eye"></i>
            </a>`;
        },
    },
    {
        title: "修改日期",
        data: "updated_at",
        width: "140px",
        defaultContent: "-",
    },
    {
        title: "功能",
        data: null,
        orderable: false,
        width: "120px",
        className: "text-center",
        render: (data) => {
            let btns = "";
            if (can(config.value.editPermission)) {
                btns += `
                <button
                    type="button"
                    class="btn btn-sm btn-info js-bs-tooltip-enabled edit-btn me-2"
                    data-bs-toggle="tooltip"
                    aria-label="編輯"
                    data-bs-title="編輯"
                    data-id="${data.id}"
                >
                    <i class="fa fa-edit"></i>
                </button>`;
            }
            if (can(config.value.deletePermission)) {
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
])

// 合併欄位（使用自定義欄位覆蓋預設）
const mergedColumns = computed(() => {
    if (props.customColumns.length > 0) {
        return props.customColumns
    }
    return defaultColumns.value
})

// DataTable 選項
const defaultOptions = computed(() => ({
    ...DataTableHelper.getBaseOptions(),
    searching: false, // 關閉 DataTable 原生搜尋
    ajax: (data, callback) => {
        const searchData = {
            ...data,
            search_params: searchParams
        };
        DataTableHelper.fetchTableData(
            route(config.value.indexRoute),
            searchData,
            callback,
            rows,
            config.value.responseKey,
            searchParams
        );
    },
    drawCallback: () => {
        DataTableHelper.defaultDrawCallback();
        DataTableHelper.bindTableButtonEvents({
            edit: editContent,
            delete: destroy,
            check: toggleActive,
            viewLogs: showViewLogs,  // 新增觀看記錄按鈕事件
        });
    },
    order: [[8, "desc"]], // 依「修改日期」排序
}))

// 合併選項
const mergedOptions = computed(() => {
    return reactive({
        ...defaultOptions.value,
        ...props.customOptions
    })
})


// 編輯
const editContent = (id) => {
    router.get(route(config.value.editRoute, id));
}

// 刪除
const destroy = (id) => {
    sweetAlert.deleteConfirm('確認是否刪除', () => {
        isLoading.value = true;
        router.delete(route(config.value.deleteRoute, id), {
            onSuccess: (finalRes) => {
                try {
                    const res = finalRes.props.flash?.result || finalRes.props.result;
                    console.log('刪除回應:', res);

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
                isLoading.value = false; // 結束 loading
            }
        });
    });
};

// 顯示觀看記錄頁面（使用 Inertia 跳轉）
const showViewLogs = (id, title) => {
    const viewLogsRoute = `admin.${props.contentType}s.view-logs`;
    console.log(viewLogsRoute);
    router.get(route(viewLogsRoute, id));
}

// 切換狀態
const toggleActive = (id) => {
    isLoading.value = true;
    router.put(route(config.value.toggleRoute), { id: id }, {
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
            isLoading.value = false; // 結束 loading
        }
    });
}

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
        dt.value = await DataTableHelper.createDataTable(table.value, mergedOptions.value);

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

// 暴露方法給父組件
defineExpose({
    reloadTable,
    initializeDataTable
})
</script>

<style scoped>
/* 共用樣式 */
</style>