<!-- resources/js/InertiaPages/Admin/Radios/Index.vue -->
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
                :href="route('admin.radios.add')"
                v-if="can('admin.radios.add')"
                >
                    <i class="fa-solid fa-plus opacity-50 me-1"></i>新增廣播
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
                            placeholder="廣播標題、媒體名稱">
                    </div>

                    <!-- 分類 -->
                    <div class="col-md-4">
                        <label class="form-label">分類</label>
                        <select class="form-select" v-model="searchParams.category_id">
                            <option value="">請選擇分類</option>
                            <option v-for="category in categories" :key="category.id" :value="category.id">
                                {{ category.name }}
                            </option>
                        </select>
                    </div>

                    <!-- 啟用狀態 -->
                    <div class="col-md-4">
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
import DataTablesCore from "datatables.net-bs5";
import DataTable from "datatables.net-vue3";
import { ref, reactive, onMounted, inject, nextTick } from "vue";
import DataTableHelper from "@/utils/datatableHelper";
import { router, Link } from "@inertiajs/vue3";
import { useDataTableSearch } from "@/composables";

const can = inject("can");
const sweetAlert = inject('$sweetAlert');
const isLoading = inject('isLoading');

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
    category_id: '',
    is_active: ''
}, {
    route: route("admin.radios"),
    dataKey: "radios"
});

// Props
const props = defineProps({
    radios: Object,
    categories: {
        type: Array,
        default: () => []
    }
});

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
        title: "標題（中文）",
        data: null,
        width: "200px",
        render: (data) => data.title_zh || "",
    },
    {
        title: "標題（英文）",
        data: null,
        width: "200px",
        render: (data) => data.title_en || "",
    },
    {
        title: "分類",
        data: "category_name",
        width: "120px",
        className: "text-center",
        defaultContent: "-",
    },
    {
        title: "上架日期",
        data: "publish_date",
        defaultContent: "-",
        className: "text-center",
        width: "120px",
    },
    {
      title: "啟用狀態",
      data: "is_active",
      width: "100px",
      className: "text-center",
      render: (val, type, row) => {
          if (can("admin.radios.edit")) {
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
            const frontendUrl = route('radio.show', { id: data.id });
            return `<a href="${frontendUrl}" target="_blank" class="btn btn-sm btn-outline-primary js-bs-tooltip-enabled" title="查看前台" data-bs-toggle="tooltip" data-bs-title="查看前台">
                <i class="fa fa-eye"></i>
            </a>`;
        },
    },
    {
        title: "修改日期",
        data: "updated_at",
        defaultContent: "-",
        className: "text-center",
        width: "150px",
    },
    {
        title: "觀看統計",
        data: "total_views",
        width: "100px",
        className: "text-center",
        defaultContent: "0",
        render: (data, type, row) => {
            if (can("admin.radios.view-stats")) {
                return `<button type="button"
                    class="btn btn-sm btn-outline-info js-bs-tooltip-enabled view-stats-btn"
                    data-bs-toggle="tooltip"
                    data-bs-title="查看觀看統計"
                    data-id="${row.id}"
                    data-title="${row.title_zh || ''}">
                    <i class="fa fa-chart-bar me-1"></i>${Number(data ?? 0).toLocaleString()}
                </button>`;
            }
            return Number(data ?? 0).toLocaleString();
        },
    },
    {
        title: "操作",
        data: null,
        orderable: false,
        width: "120px",
        className: "text-center",
        render: (data) => {
        let btns = "";
        if (can("admin.radios.edit")) {
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
        if (can("admin.radios.delete")) {
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
            route("admin.radios"),
            searchData,
            callback,
            rows,
            "radios",
            searchParams
        );
    },
    drawCallback: () => {
        DataTableHelper.defaultDrawCallback();
        DataTableHelper.bindTableButtonEvents({
            edit: editRadio,
            delete: destroy,
            check: toggleActive,
        });
        // 綁定觀看統計按鈕事件
        document.querySelectorAll('.view-stats-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const id = btn.dataset.id;
                if (id) showViewStats(id);
            });
        });
    },
});

// 方法
const editRadio = (id) => {
    router.get(route('admin.radios.edit', id));
}

const showViewStats = (id) => {
    router.get(route('admin.radios.view-stats', id));
}

const destroy = (id) => {
    sweetAlert.deleteConfirm('確定要刪除嗎？', () => {
        isLoading.value = true;
        router.delete(route('admin.radios.delete', id), {
            onSuccess: (finalRes) => {
                const res = finalRes.props.flash?.result;
                if (res && res.status) {
                    sweetAlert.resultData(res);
                    reloadTable();
                }
            },
            onError: () => {
                sweetAlert.error('刪除失敗，請重試！');
            },
            onFinish: () => {
                isLoading.value = false;
            },
        });
    });
};

const toggleActive = (id) => {
    isLoading.value = true;
    router.put(route('admin.radios.toggle-active'), { id: id }, {
        onSuccess: (finalRes) => {
            const res = finalRes.props.flash?.result;
            if (res && res.status) {
                sweetAlert.resultData(res);
                reloadTable();
            }
        },
        onError: () => {
            sweetAlert.error('狀態切換失敗，請重試！');
        },
        onFinish: () => {
            isLoading.value = false;
        },
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
        dt.value = await DataTableHelper.createDataTable(table.value, options);

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
