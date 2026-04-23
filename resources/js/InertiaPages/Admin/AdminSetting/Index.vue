<template>
  <div class="content">
    <BreadcrumbItem />
    <div class="block block-rounded">
      <div class="block-header block-header-default">
        <h3 class="block-title"></h3>
        <Link
          class="btn btn-primary"
          :href="route('admin.admin-settings.add')"
          v-if="can('admin.admin-settings.add')"
        >
            <i class="fa-solid fa-plus opacity-50 me-1 "></i>新增員工
        </Link>
      </div>
      <div class="block-content block-content-full">
        <!-- 使用 datatables.net-vue3 -->
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
import { router, Link } from "@inertiajs/vue3";
import { ref, reactive, onMounted, inject } from "vue";
import DataTableHelper from "@/utils/datatableHelper.js";

DataTable.use(DataTablesCore);

// 注入服務
const can = inject('can');
const sweetAlert = inject('$sweetAlert');
const isLoading = inject('isLoading');

// 響應式數據
const table = ref(null);
const rows = ref([]);
const dt = ref(null);

// 表格欄位設定
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
    { title: "員工帳號", data: "username" },
    { title: "員工名稱", data: "name" },
    { title: "角色權限", data: "role", orderable: false },
    ...(can('admin.admin-settings.edit') ? [{
        title: "啟用狀態",
        data: "is_active",
        className: "text-center",
        render: (data, type, row) => `
            <div class="form-check form-switch">
                <input
                    class="form-check-input toggle-active checked-btn"
                    type="checkbox"
                    data-id="${row.id}"
                    ${data == 1 ? "checked" : ""}>
            </div>
        `,
    }] : []),
    { title: "建立時間", data: "created_at" },
    { title: "更新時間", data: "updated_at" },
    {
        title: "功能",
        data: null,
        orderable: false,
        width: "120px",
        className: "text-center",
        render: (data, type, row) => {
            let btnHtml = "";
            if (can('admin.admin-settings.add')) {
                btnHtml += `<button type="button" class="btn btn-sm btn-info js-bs-tooltip-enabled edit-btn me-2" data-bs-toggle="tooltip" aria-label="編輯" data-bs-title="編輯" data-id="${row.id}"><i class="fa fa-edit"></i></button>`;
            }
            if (can('admin.admin-settings.edit')) {
                btnHtml += `<button type="button" class="btn btn-sm btn-danger js-bs-tooltip-enabled delete-btn" data-bs-toggle="tooltip" aria-label="刪除" data-bs-title="刪除" data-id="${row.id}"><i class="fa-solid fa-trash"></i></button>`;
            }
            return btnHtml;
        },
    },
];

// DataTable 選項
const options = reactive({
    ...DataTableHelper.getBaseOptions(),
    ajax: (data, callback) => {
        DataTableHelper.fetchTableData(
            route("admin.admin-settings"),
            data,
            callback,
            rows
        );
    },
    drawCallback: function () {
        DataTableHelper.defaultDrawCallback();
        DataTableHelper.bindTableButtonEvents({
            edit: editModal,
            delete: destroy,
            check: toggleActive,
        });
    },
    order: [[6, "desc"]],
});

// 方法
const editModal = (id) => {
    router.get(route('admin.admin-settings.edit', id));
};

const destroy = (id) => {
    sweetAlert.deleteConfirm('確認是否刪除', () => {
        isLoading.value = true;
        router.delete(route('admin.admin-settings.delete', id), {
            onSuccess: (finalRes) => {
                const res = finalRes.props.flash?.result || finalRes.props.result;
                sweetAlert.resultData(res);
                if (res?.status) {
                    reloadTable();
                }
            },
            onError: () => sweetAlert.error({ msg: '刪除失敗，請重試！' }),
            onFinish: () => {
                isLoading.value = false;
            }
        });
    });
};

const toggleActive = (id) => {
    isLoading.value = true;
    router.put(route('admin.admin-settings.toggle-active'), { id }, {
        onSuccess: (finalRes) => {
            const res = finalRes.props.flash?.result || finalRes.props.result;
            if (res?.status) {
                sweetAlert.resultData(res);
                reloadTable();
            } else {
                sweetAlert.error({ msg: '狀態切換失敗，請重試！' });
            }
        },
        onError: () => sweetAlert.error({ msg: '狀態切換失敗，請重試！' }),
        onFinish: () => {
            isLoading.value = false;
        }
    });
};

const reloadTable = () => {
    if (dt.value && dt.value.ajax && typeof dt.value.ajax.reload === 'function') {
        dt.value.ajax.reload(null, false);
    }
};

// 生命週期
onMounted(async () => {
    try {
        if (!table.value) {
            console.error('Table element not found');
            return;
        }

        dt.value = await DataTableHelper.createDataTable(table.value);

        if (!dt.value) {
            console.error('DataTable 初始化失敗');
        }
    } catch (error) {
        console.error('DataTable 初始化錯誤:', error);
    }
});
</script>

<script>
export default {
    layout: Layout,
};
</script>
