<!-- resources/js/InertiaPages/Admin/CustomerService/Index.vue -->
<template>
    <div class="content">
      <BreadcrumbItem />

      <div class="block block-rounded">
        <div class="block-header block-header-default">
          <h3 class="block-title"></h3>
          <div class="block-options">
          </div>
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
import { ref, reactive, inject, onMounted } from "vue";
import datatableHelper from "@/utils/datatableHelper";
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
        title: "聯絡人",
        data: null,
        width: "150px",
        render: (data) => {
          let html = `<div class="fw-bold">${data.name || '-'}</div>`;
          if (data.email) {
            html += `<small class="text-muted">${data.email}</small>`;
          }
          return html;
        },
    },
    {
        title: "主旨",
        data: "subject",
        width: "250px",
        render: (data) => {
          if (!data) return "-";
          // 如果主旨太長，截取並顯示省略號
          return data.length > 50 ? data.substring(0, 50) + '...' : data;
        },
    },
    {
        title: "狀態",
        data: "is_replied",
        width: "80px",
        className: "text-center",
        render: (val) => {
          if (val) {
            return `<span class="badge bg-success"><i class="fa fa-check me-1"></i>已處理</span>`;
          } else {
            return `<span class="badge bg-warning"><i class="fa fa-clock me-1"></i>待處理</span>`;
          }
        },
    },
    {
        title: "提交時間",
        data: "created_at",
        defaultContent: "-",
        className: "text-center",
        width: "150px",
    },
    {
        title: "回覆時間",
        data: "replied_at",
        defaultContent: "-",
        className: "text-center",
        width: "150px",
        render: (data) => data || '<span class="text-muted">-</span>',
    },
    {
        title: "操作",
        data: null,
        orderable: false,
        width: "180px",
        className: "text-center",
        render: (data) => {
        let btns = "";

        // 查看詳情按鈕
        btns += `
        <button
            type="button"
            class="btn btn-sm btn-info js-bs-tooltip-enabled view-btn me-1"
            data-bs-toggle="tooltip"
            aria-label="查看詳情"
            data-bs-title="查看詳情"
            data-id="${data.id}"
        >
            <i class="fa fa-eye"></i>
        </button>`;


        // 標記為已回覆/未回覆按鈕
        if (can("admin.customer-services.update")) {
          const isReplied = data.is_replied;
          const toggleTitle = isReplied ? '標記為待處理' : '標記為已處理';
          const toggleIcon = isReplied ? 'fa-undo' : 'fa-check';
          const toggleClass = isReplied ? 'btn-warning' : 'btn-primary';

          btns += `
          <button
              type="button"
              class="btn btn-sm ${toggleClass} js-bs-tooltip-enabled toggle-reply-btn me-1"
              data-bs-toggle="tooltip"
              aria-label="${toggleTitle}"
              data-bs-title="${toggleTitle}"
              data-id="${data.id}"
              data-status="${isReplied ? 0 : 1}"
          >
              <i class="fa ${toggleIcon}"></i>
          </button>`;
        }

        // 刪除按鈕
        if (can("admin.customer-services.delete")) {
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
    ...datatableHelper.getBaseOptions(),
    ajax: (data, callback) => {
      datatableHelper.fetchTableData(
          route("admin.customer-services"),
          data,
          callback,
          rows,
          "customerServices", // 對應後端回傳的 key
      );
    },
    drawCallback: () => {
        // 使用 datatableHelper 的 tooltip 初始化
        datatableHelper.defaultDrawCallback();

        // 使用 datatableHelper 綁定標準按鈕事件
        datatableHelper.bindTableButtonEvents({
            edit: (id) => viewMessage(id), // edit 按鈕對應到查看功能
            delete: destroy,
        });

        // 自訂按鈕事件（datatableHelper 沒有的）
        // 綁定查看詳情按鈕（因為用 view-btn 而不是 edit-btn）
        document.querySelectorAll('.view-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const id = this.dataset.id;
                viewMessage(id);
            });
        });


        // 綁定切換狀態按鈕事件
        document.querySelectorAll('.toggle-reply-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const id = this.dataset.id;
                toggleReplyStatus(id);
            });
        });
    },
    order: [[4, "desc"]], // 依「提交時間」排序（後端會自動加上狀態優先邏輯）
});

// 重載表格
const reloadTable = () => {
    try {
        if (dt.value && typeof dt.value.ajax?.reload === 'function') {
            dt.value.ajax.reload(null, false);
        } else if (table.value?.dt && typeof table.value.dt.ajax?.reload === 'function') {
            table.value.dt.ajax.reload(null, false);
        } else {
            console.warn('DataTable 實例不存在或沒有 reload 方法，重新初始化');
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
            dt.value = datatableHelper.createDataTable(table.value);
        }
    } catch (error) {
        console.error('初始化 DataTable 時發生錯誤:', error);
    }
};

const isLoading = inject('isLoading')

// 查看詳情
const viewMessage = (id) => {
    router.get(route("admin.customer-services.show", id));
}


// 切換回覆狀態
const toggleReplyStatus = (id) => {
    const button = document.querySelector(`[data-id="${id}"].toggle-reply-btn`);
    const targetStatus = parseInt(button.dataset.status); // 0=要改成待處理, 1=要改成已處理
    const actionText = targetStatus === 0 ? '標記為待處理' : '標記為已處理';
    const newIsReplied = targetStatus === 1; // 轉換為布林值

    sweetAlert.confirm(`確認要將此信件${actionText}嗎？`, () => {
        isLoading.value = true;
        router.patch(route('admin.customer-services.toggle-status', id), {
            is_replied: newIsReplied
        }, {
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
    });
}

// 刪除
const destroy = (id) => {
    sweetAlert.deleteConfirm('確認是否刪除此信件？', () => {
        isLoading.value = true;
        router.delete(route('admin.customer-services.destroy', id), {
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