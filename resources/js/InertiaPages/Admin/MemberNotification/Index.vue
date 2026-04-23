<!-- resources/js/InertiaPages/Admin/MemberNotification/Index.vue -->
<template>
  <div class="content">
    <BreadcrumbItem />

    <div class="block block-rounded">
      <div class="block-header block-header-default">
        <h3 class="block-title"></h3>
        <Link
          class="btn btn-primary"
          :href="route('admin.member-notifications.add')"
          v-if="can('admin.member-notifications.add')"
        >
          <i class="fa-solid fa-plus me-1"></i>新增通知
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

// 狀態配置
const statusConfig = {
  draft: { class: 'bg-secondary', text: '草稿' },
  scheduled: { class: 'bg-warning', text: '已排程' },
  sent: { class: 'bg-success', text: '已發送' }
};

// 發送對象顯示邏輯
const formatTargetType = (data) => {
  const userCount = data.target_count || 0;

  if (data.target_type === 'all') {
    return `全體會員 (${userCount})`;
  } else if (data.target_type === 'specific') {
    return `指定會員 (${userCount})`;
  }
  return '-';
};

// 格式化發送時間
const formatScheduledTime = (data) => {
  if (data.scheduled_at === null) {
    return '立即發送';
  }
  return data.scheduled_at || '-';
};

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
    title: "通知主旨",
    data: "title",
    width: "200px",
    render: (data) => data || "-",
  },
  {
    title: "通知訊息",
    data: "message",
    width: "250px",
    render: (data) => {
      if (!data) return "-";
      // 限制顯示長度，超過則截斷，並加入 title 屬性顯示完整內容
      const maxLength = 50;
      if (data.length > maxLength) {
        const truncated = data.substring(0, maxLength) + "...";
        return `<span title="${data.replace(/"/g, '&quot;')}" style="cursor: help;">${truncated}</span>`;
      }
      return `<span title="${data.replace(/"/g, '&quot;')}">${data}</span>`;
    },
  },
  {
    title: "發送對象",
    data: null,
    width: "120px",
    className: "text-center",
    orderable: false,
    render: (data) => formatTargetType(data),
  },
  {
    title: "發送時間",
    data: null,
    width: "140px",
    className: "text-center",
    orderable: false,
    render: (data) => formatScheduledTime(data),
  },
  {
    title: "狀態",
    data: "status",
    width: "80px",
    className: "text-center",
    render: (data) => {
      const config = statusConfig[data] || statusConfig.draft;
      return `<span class="badge ${config.class}">${config.text}</span>`;
    },
  },
  {
    title: "建立時間",
    data: "created_at",
    defaultContent: "-",
    className: "text-center",
    width: "130px",
  },
  {
    title: "操作",
    data: null,
    orderable: false,
    width: "80px",
    className: "text-center",
    render: (data) => {
      let btns = "";

      // 檢視按鈕
      if (can("admin.member-notifications.show")) {
        btns += `
          <button
            type="button"
            class="btn btn-sm btn-info js-bs-tooltip-enabled view-btn me-2"
            data-bs-toggle="tooltip"
            aria-label="檢視"
            data-bs-title="檢視"
            data-id="${data.id}"
          >
            <i class="fa fa-eye"></i>
          </button>`;
      }

      // 刪除按鈕
      if (can("admin.member-notifications.delete")) {
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
      route("admin.member-notifications"),
      data,
      callback,
      rows,
      "notifications"
    );
  },
  drawCallback: () => {
    DataTableHelper.defaultDrawCallback();
    DataTableHelper.bindTableButtonEvents({
      delete: destroy,
    });

    // 手動綁定檢視按鈕
    document.querySelectorAll('.view-btn').forEach(btn => {
      btn.addEventListener('click', function() {
        const id = this.getAttribute('data-id');
        router.get(route('admin.member-notifications.show', id));
      });
    });
  },
  order: [[6, "desc"]], // 依「建立時間」排序
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
      dt.value = DataTableHelper.createDataTable(table.value);
    }
  } catch (error) {
    console.error('初始化 DataTable 時發生錯誤:', error);
  }
};

// 檢視通知
const viewNotification = (id) => {
  router.get(route("admin.member-notifications.show", id));
};

// 刪除通知
const destroy = (id) => {
  sweetAlert.deleteConfirm('確認是否刪除此通知？', () => {
    isLoading.value = true;
    router.delete(route('admin.member-notifications.delete', id), {
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
