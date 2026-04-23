<!-- resources/js/InertiaPages/Admin/MailRecipient/Index.vue -->
<template>
    <div class="content">
      <BreadcrumbItem />

      <div class="block block-rounded">
        <div class="block-header block-header-default">
          <h3 class="block-title"></h3>
           <Link
                class="btn btn-primary"
                :href="route('admin.mail-recipients.add')"
                v-if="can('admin.mail-recipients.add')"
                >
                    <i class="fa-solid fa-plus opacity-50 me-1"></i>新增收件信箱
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
import { Link } from "@inertiajs/vue3";
import { ref, reactive, inject, onMounted } from "vue";
import DataTableHelper from "@/utils/datatableHelper";
import { router } from "@inertiajs/vue3";

const can = inject("can");
const sweetAlert = inject('$sweetAlert');
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
        title: "收件類型",
        data: null,
        width: "120px",
        className: "text-center",
        render: (data) => {
            if (data.mail_type) {
                return `<span class="badge bg-primary">${data.mail_type.name || '-'}</span>`;
            }
            return '-';
        },
    },
    {
        title: "收件人名稱",
        data: "name",
        width: "150px",
        render: (data) => data || "-",
    },
    {
        title: "收件信箱",
        data: "email",
        width: "250px",
        render: (data) => {
            if (!data) return "-";
            return `<a href="mailto:${data}" class="text-primary">${data}</a>`;
        },
    },
    {
        title: "修改時間",
        data: "updated_at",
        defaultContent: "-",
        className: "text-center",
        width: "150px",
    },
    {
      title: "狀態",
      data: "status",
      width: "80px",
      className: "text-center",
      render: (val, type, row) => {
          if (can("admin.mail-recipients.edit")) {
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
        title: "操作",
        data: null,
        orderable: false,
        width: "120px",
        className: "text-center",
        render: (data) => {
        let btns = "";
        if (can("admin.mail-recipients.edit")) {
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
        if (can("admin.mail-recipients.delete")) {
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
        route("admin.mail-recipients"),
        data,
        callback,
        rows,
        "mailRecipients"
    );
    },
    drawCallback: () => {
        DataTableHelper.defaultDrawCallback();
        DataTableHelper.bindTableButtonEvents({
            edit: editMailRecipient,
            delete: destroy,
            check: toggleActive,
        });
    },
    order: [[4, "desc"]], // 依「修改時間」排序
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
const editMailRecipient = (id) => {
    router.get(route("admin.mail-recipients.edit", id));
}

const isLoading = inject('isLoading')

// 刪除
const destroy = (id) => {
    sweetAlert.deleteConfirm('確認是否刪除', () => {
        isLoading.value = true;
        router.delete(route('admin.mail-recipients.delete', id), {
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
    router.put(route('admin.mail-recipients.toggle-active'), { id: id }, {
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
