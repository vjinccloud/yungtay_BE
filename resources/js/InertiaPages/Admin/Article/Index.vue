<!-- resources/js/InertiaPages/Admin/Article/Index.vue -->
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
                :href="route('admin.articles.add')"
                v-if="can('admin.articles.add')"
                >
                    <i class="fa-solid fa-plus opacity-50 me-1 "></i>新增新聞
            </Link>
        </div>

        <div class="block-content block-content-full">
          <!-- 搜尋表單 -->
          <div v-show="filterExpanded" class="mb-4">
            <form @submit.prevent="searchData">
                <div class="row g-3">
                    <!-- 關鍵字搜尋 -->
                    <div class="col-md-6">
                        <label class="form-label">關鍵字</label>
                        <input
                            type="text"
                            class="form-control"
                            v-model="searchParams.search"
                            placeholder="新聞標題、內容、作者、地點、標籤">
                    </div>

                    <!-- 分類（使用 Select2Input）-->
                    <div class="col-md-4">
                        <label class="form-label">分類</label>
                        <Select2Input
                            v-model="searchParams.category_id"
                            :options="categoryOptions"
                            placeholder="請選擇分類"
                            :clearable="true"
                        />
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
            :columns="columns"
            :options="mergedOptions"
            ref="table"
          />
        </div>
      </div>
    </div>
</template>

<script setup>
import Layout from "@/Shared/Admin/Layout.vue";
import BreadcrumbItem from "@/Shared/Admin/Partials/BreadcrumbItem.vue";
import DatePicker from "@/Plugin/DatePicker.vue";
import Select2Input from "@/Plugin/Select2Input.vue";
import DataTablesCore from "datatables.net-bs5";
import DataTable from "datatables.net-vue3";
import { ref, reactive, onMounted, inject, computed, nextTick } from "vue";
import DataTableHelper from "@/utils/datatableHelper";
import { router, Link } from "@inertiajs/vue3";
import { useDataTableSearch } from "@/composables";

DataTable.use(DataTablesCore);

// Props
const props = defineProps({
    categories: {
        type: Array,
        default: () => []
    }
})

// Injects
const can = inject("can");
const sweetAlert = inject('$sweetAlert');
const isLoading = inject('isLoading')

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
    published_start_date: '',
    published_end_date: '',
    is_active: ''
}, {
    route: computed(() => route('admin.articles')),
    dataKey: 'articles'
});

// 分類選項格式化為 Select2 格式
const categoryOptions = computed(() => {
    return props.categories.map(category => ({
        value: category.id,
        text: category.name
    }));
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
        title: "分類",
        data: null,
        width: "120px",
        className: "text-center",
        render: (data) => data.category_name || "-",
    },
    {
        title: "RSS 來源",
        data: "source_link",
        width: "150px",
        orderable: false,
        render: (data) => {
            if (!data) return "-";
            return `<a href="${data}" target="_blank" class="text-primary" title="${data}" style="word-break: break-all; display: block; line-height: 1.2;">${data}</a>`;
        },
    },
    {
        title: "前台",
        data: null,
        width: "60px",
        className: "text-center",
        orderable: false,
        render: (data) => {
            const frontendUrl = `/articles/${data.id}`;
            return `<a href="${frontendUrl}" target="_blank" class="btn btn-sm btn-outline-primary js-bs-tooltip-enabled" title="查看前台" data-bs-toggle="tooltip" data-bs-title="查看前台">
                <i class="fa fa-eye"></i>
            </a>`;
        },
    },
    {
        title: "上架日期",
        data: "publish_date",
        defaultContent: "-",
        className: "text-center",
        width: "120px",
    },
    {
      title: "狀態",
      data: "is_active",
      width: "80px",
      className: "text-center",
      render: (val, type, row) => {
          if (can("admin.articles.edit")) {
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
              // 沒有權限時顯示純文字狀態
              return `
              <span class="badge ${val ? 'bg-success' : 'bg-secondary'}">
                  ${val ? '啟用' : '停用'}
              </span>
              `;
          }
      },
    },
    {
        title: "更新時間",
        data: "updated_at",
        defaultContent: "-",
        className: "text-center",
        width: "130px",
    },
    {
        title: "操作",
        data: null,
        orderable: false,
        width: "120px",
        className: "text-center",
        render: (data) => {
        let btns = "";
        if (can("admin.articles.edit")) {
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
        if (can("admin.articles.delete")) {
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
const defaultOptions = computed(() => ({
    ...DataTableHelper.getBaseOptions(),
    searching: false, // 關閉 DataTable 原生搜尋
    ajax: (data, callback) => {
        const searchData = {
            ...data,
            search_params: searchParams
        };
        DataTableHelper.fetchTableData(
            route("admin.articles"),
            searchData,
            callback,
            rows,
            "articles",
            searchParams
        );
    },
    drawCallback: () => {
        DataTableHelper.defaultDrawCallback();
        DataTableHelper.bindTableButtonEvents({
            edit: editArticle,
            delete: destroy,
            check: toggleActive,
        });
    },
    order: [[7, "desc"]], // 依「更新時間」排序
}))

// 合併選項
const mergedOptions = computed(() => {
    return reactive({
        ...defaultOptions.value
    })
})


// 編輯
const editArticle = (id) => {
    router.get(route("admin.articles.edit", id));
}

// 刪除
const destroy = (id) => {
    sweetAlert.deleteConfirm('確認是否刪除', () => {
        isLoading.value = true;
        router.delete(route('admin.articles.delete', id), {
            onSuccess: (finalRes) => {
                try {
                    // 修正：確保正確取得結果
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
                isLoading.value = false; // 結束 loading
            }
        });
    });
};

// 切換狀態
const toggleActive = (id) => {
    isLoading.value = true;
    router.put(route('admin.articles.toggle-active'), { id: id }, {
        onSuccess: (finalRes) => {
            try {
                // 修正：確保正確取得結果
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

<script>
export default {
    layout: Layout,
};
</script>
