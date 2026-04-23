<!-- resources/js/InertiaPages/Admin/News/Index.vue -->
<template>
    <div class="content">
      <BreadcrumbItem />

      <div class="block block-rounded">
        <div class="block-header block-header-default">
          <h3 class="block-title"></h3>
           <Link
                class="btn btn-primary"
                :href="route('admin.news.add')"
                v-if="can('admin.news.add')"
                >
                    <i class="fa-solid fa-plus opacity-50 me-1 "></i>新增最新消息
            </Link>
        </div>

        <div class="block-content block-content-full">
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
import { ref, reactive, onMounted, inject } from "vue";
import DataTableHelper from "@/utils/datatableHelper";
import { router, Link } from "@inertiajs/vue3";

const can = inject("can");
const sweetAlert = inject('$sweetAlert');
const isLoading = inject('isLoading');
const table = ref(null);
const dt = ref(null); // DataTable 實例

DataTable.use(DataTablesCore);

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
        width: "350px",
        render: (data) => data.title_zh || "",
    },
    {
        title: "上架日期",
        data: "published_date",
        defaultContent: "-",
        className: "text-center",
        width: "150px",
    },
    {
        title: "首頁曝光文章",
        data: "is_homepage_featured",
        width: "120px",
        className: "text-center",
        render: (val, type, row) => {
            if (can("admin.news.edit")) {
                return `
                <div class="form-check form-switch">
                    <input
                        class="form-check-input toggle-homepage-featured js-bs-tooltip-enabled"
                        type="checkbox"
                        data-id="${row.id}"
                        data-bs-toggle="tooltip"
                        aria-label="首頁曝光"
                        data-bs-title="首頁曝光（最多4則）"
                        ${val ? "checked" : ""}>
                </div>
                `;
            } else {
                return `
                <span class="badge ${val ? 'bg-warning' : 'bg-secondary'}">
                    ${val ? '是' : '否'}
                </span>
                `;
            }
        },
    },
    {
        title: "最新消息置頂文章",
        data: "is_pinned",
        width: "140px",
        className: "text-center",
        render: (val, type, row) => {
            if (can("admin.news.edit")) {
                return `
                <div class="form-check form-switch">
                    <input
                        class="form-check-input toggle-pinned js-bs-tooltip-enabled"
                        type="checkbox"
                        data-id="${row.id}"
                        data-bs-toggle="tooltip"
                        aria-label="置頂"
                        data-bs-title="置頂（最多3則）"
                        ${val ? "checked" : ""}>
                </div>
                `;
            } else {
                return `
                <span class="badge ${val ? 'bg-info' : 'bg-secondary'}">
                    ${val ? '是' : '否'}
                </span>
                `;
            }
        },
    },
    {
      title: "啟用狀態",
      data: "is_active",
      width: "100px",
      className: "text-center",
      render: (val, type, row) => {
          if (can("admin.news.edit")) {
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
        title: "修改日期",
        data: "updated_at",
        defaultContent: "-",
        className: "text-center",
        width: "150px",
    },
    {
        title: "功能",
        data: null,
        orderable: false,
        width: "120px",
        className: "text-center",
        render: (data) => {
        let btns = "";
        if (can("admin.news.edit")) {
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
        if (can("admin.news.delete")) {
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
const rows = ref([]);
const options = reactive({
    ...DataTableHelper.getBaseOptions(),
    ajax: (data, callback) => {
    DataTableHelper.fetchTableData(
        route("admin.news"),
        data,
        callback,
        rows,
        "news"
    );
    },
    drawCallback: () => {
        DataTableHelper.defaultDrawCallback();
        DataTableHelper.bindTableButtonEvents({
            edit: editNews,
            delete: destroy,
            check: toggleActive,
        });
        // 綁定首頁曝光切換事件
        document.querySelectorAll('.toggle-homepage-featured').forEach(el => {
            el.addEventListener('change', (e) => {
                const id = e.target.dataset.id;
                toggleHomepageFeatured(id);
            });
        });
        // 綁定置頂切換事件
        document.querySelectorAll('.toggle-pinned').forEach(el => {
            el.addEventListener('change', (e) => {
                const id = e.target.dataset.id;
                togglePinned(id);
            });
        });
    },
    order: [[6, "desc"]], // 依「修改日期」排序（欄位索引調整為6）
});

// 重載表格 - 改進版本
const reloadTable = () => {
    try {
        if (dt.value && typeof dt.value.ajax?.reload === 'function') {
            dt.value.ajax.reload(null, false);
        } else if (table.value?.dt && typeof table.value.dt.ajax?.reload === 'function') {
            // 備用方案：直接從 table ref 取得 DataTable 實例
            table.value.dt.ajax.reload(null, false);
        } else {
            console.warn('DataTable 實例不存在或沒有 reload 方法，重新初始化');
            // 最後備用方案：重新初始化 DataTable
            initializeDataTable();
        }
    } catch (error) {
        console.error('重載表格時發生錯誤:', error);
        sweetAlert?.error({ msg: '重載表格失敗，請刷新頁面' });
    }
};

// 初始化 DataTable
const initializeDataTable = () => {
    try {
        if (table.value) {
            dt.value = DataTableHelper.createDataTable(table.value);

        }
    } catch (error) {
        console.error('初始化 DataTable 時發生錯誤:', error);
    }
};

// 編輯
const editNews = (id) => {
    router.get(route("admin.news.edit", id));
}

// 刪除
const destroy = (id) => {
    sweetAlert.deleteConfirm('確認是否刪除', () => {
        isLoading.value = true;
        router.delete(route('admin.news.delete', id), {
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
    router.put(route('admin.news.toggle-active'), { id: id }, {
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

// 切換首頁曝光狀態
const toggleHomepageFeatured = (id) => {
    isLoading.value = true;
    router.put(route('admin.news.toggle-homepage-featured'), { id: id }, {
        onSuccess: (finalRes) => {
            try {
                const res = finalRes.props.flash?.result || finalRes.props.result;

                if (res && res.status) {
                    sweetAlert.resultData(res);
                    reloadTable();
                } else {
                    sweetAlert.error({ msg: res?.msg || '操作失敗，請重試！' });
                    reloadTable(); // 重新載入以還原 checkbox 狀態
                }
            } catch (error) {
                console.error('處理首頁曝光切換回應時發生錯誤:', error);
                sweetAlert.error({ msg: '處理回應時發生錯誤' });
            }
        },
        onError: (errors) => {
            console.error('首頁曝光切換請求失敗:', errors);
            sweetAlert.error({ msg: '操作失敗，請重試！' });
            reloadTable();
        },
        onFinish: () => {
            isLoading.value = false;
        }
    });
}

// 切換置頂狀態
const togglePinned = (id) => {
    isLoading.value = true;
    router.put(route('admin.news.toggle-pinned'), { id: id }, {
        onSuccess: (finalRes) => {
            try {
                const res = finalRes.props.flash?.result || finalRes.props.result;

                if (res && res.status) {
                    sweetAlert.resultData(res);
                    reloadTable();
                } else {
                    sweetAlert.error({ msg: res?.msg || '操作失敗，請重試！' });
                    reloadTable(); // 重新載入以還原 checkbox 狀態
                }
            } catch (error) {
                console.error('處理置頂切換回應時發生錯誤:', error);
                sweetAlert.error({ msg: '處理回應時發生錯誤' });
            }
        },
        onError: (errors) => {
            console.error('置頂切換請求失敗:', errors);
            sweetAlert.error({ msg: '操作失敗，請重試！' });
            reloadTable();
        },
        onFinish: () => {
            isLoading.value = false;
        }
    });
}

// 組件掛載
onMounted(() => {
    try {
        initializeDataTable();
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
