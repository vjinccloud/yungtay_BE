<!-- resources/js/InertiaPages/Admin/ModuleDescription/Index.vue -->
<template>
    <div class="content">
      <BreadcrumbItem />

      <div class="block block-rounded">
        <div class="block-header block-header-default">
          <h3 class="block-title">模組描述列表</h3>
          <Link
              class="btn btn-primary"
              :href="route('admin.module-descriptions.add')"
              v-if="can('admin.module-descriptions.add')"
          >
              <i class="fa-solid fa-plus opacity-50 me-1"></i>新增模組描述
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

// 注入服務
const can = inject("can");
const sweetAlert = inject('$sweetAlert');
const isLoading = inject('isLoading');

// 響應式資料
const table = ref(null);
const rows = ref([]);
const dt = ref(null);

DataTable.use(DataTablesCore);

// 表格欄位設定
const columns = [
  {
    title: "#",
    data: null,
    className: "text-center",
    orderable: false,
    width: "50px",
    render: (data, type, row, meta) => {
      return meta.settings._iDisplayStart + meta.row + 1;
    },
  },
  {
    title: "模組類型",
    data: "title", // 使用 Service 傳回的 title（來自 module_name）
    className: "text-center",
    width: "120px",
    render: (data) => {
      return `<div class="fw-semibold">${data || ''}</div>`;
    },
  },
  {
    title: "SEO描述（中文）",
    data: "meta_description_zh",
    orderable: false, // 不可排序
    render: (data) => {
      // 限制顯示長度
      const maxLength = 60;
      const text = data || '';
      if (text.length > maxLength) {
        return `<span title="${text}">${text.substring(0, maxLength)}...</span>`;
      }
      return text;
    },
  },
  {
    title: "SEO描述（英文）",
    data: "meta_description_en",
    orderable: false, // 不可排序
    render: (data) => {
      // 限制顯示長度
      const maxLength = 60;
      const text = data || '';
      if (text.length > maxLength) {
        return `<span title="${text}">${text.substring(0, maxLength)}...</span>`;
      }
      return text;
    },
  },
  {
    title: "更新時間",
    data: "updated_at",
    className: "text-center",
    width: "180px",
    defaultContent: "-",
  },
  {
    title: "操作",
    data: null,
    orderable: false,
    className: "text-center",
    width: "120px",
    render: (data) => {
      let btns = "";
      
      if (can('admin.module-descriptions.edit')) {
        btns += `
          <button
            type="button"
            class="btn btn-sm btn-primary js-bs-tooltip-enabled edit-btn me-2"
            data-bs-toggle="tooltip"
            aria-label="編輯"
            data-bs-title="編輯"
            data-id="${data.id}"
          >
            <i class="fa fa-edit"></i>
          </button>`;
      }
      
      if (can('admin.module-descriptions.delete')) {
        btns += `
          <button
            type="button"
            class="btn btn-sm btn-danger js-bs-tooltip-enabled delete-btn"
            data-bs-toggle="tooltip"
            aria-label="刪除"
            data-id="${data.id}"
            data-name="${data.title || ''}"
          >
            <i class="fa-solid fa-trash"></i>
          </button>`;
      }
      
      return btns || '---';
    },
  },
];

// DataTable 選項
const options = reactive({
  ...DataTableHelper.getBaseOptions(),
  ajax: (data, callback) => {
    DataTableHelper.fetchTableData(
      route('admin.module-descriptions'),
      data,
      callback,
      rows,
      "moduleDescriptions"
    );
  },
  drawCallback: () => {
    DataTableHelper.defaultDrawCallback();
    DataTableHelper.bindTableButtonEvents({
      edit: editModuleDescription,
      delete: destroy,
    });
  },
  order: [[4, "desc"]], // 依「更新時間」欄位排序（第5欄，索引為4）
});

// 方法
const editModuleDescription = (id) => {
  router.get(route('admin.module-descriptions.edit', id));
}

// 刪除
const destroy = (id) => {
  sweetAlert.deleteConfirm(`確定要刪除嗎？`, () => {
    isLoading.value = true;
    router.delete(route('admin.module-descriptions.delete', id), {
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
      dt.value = DataTableHelper.createDataTable(table.value, (dtInstance) => {
        // 強制設定正確的排序（更新時間欄位降序）
        dtInstance.order([4, 'desc']).draw(false);
      });
    }
  } catch (error) {
    console.error('初始化 DataTable 時發生錯誤:', error);
  }
};

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
