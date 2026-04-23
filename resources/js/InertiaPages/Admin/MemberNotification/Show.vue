<template>
  <div class="content">
    <BreadcrumbItem />

    <!-- 頁面標題與返回按鈕 -->
    <div class="d-flex justify-content-between align-items-center mb-4">
      <Link :href="route('admin.member-notifications')" class="btn btn-secondary">
        <i class="fa fa-arrow-left me-1"></i> 返回列表
      </Link>
    </div>

    <!-- 通知基本資訊 -->
    <div class="block block-rounded">
      <div class="block-header block-header-default">
        <h3 class="block-title">通知資訊</h3>
      </div>
      <div class="block-content">
        <div class="row">
          <div class="col-md-6">
            <div class="mb-3">
              <label class="form-label">通知主旨（繁體中文）</label>
              <p class="form-control-static">{{ notification.title?.zh_TW || '-' }}</p>
            </div>
            <div class="mb-3">
              <label class="form-label">通知主旨（英文）</label>
              <p class="form-control-static">{{ notification.title?.en || '-' }}</p>
            </div>
          </div>
          <div class="col-md-6">
            <div class="mb-3">
              <label class="form-label">發送狀態</label>
              <p class="form-control-static">
                <span :class="getStatusClass(notification.status)">
                  {{ getStatusLabel(notification.status) }}
                </span>
              </p>
            </div>
            <div class="mb-3">
              <label class="form-label">{{ notification.status === 'sent' ? '發送時間' : '排程時間' }}</label>
              <p class="form-control-static">{{ notification.sent_at || notification.scheduled_at || '-' }}</p>
            </div>
            <div class="mb-3">
              <label class="form-label">建立時間</label>
              <p class="form-control-static">{{ notification.created_at || '-' }}</p>
            </div>
          </div>
        </div>
        <div class="row">
          <div class="col-md-6">
            <div class="mb-3">
              <label class="form-label">通知內容（繁體中文）</label>
              <div class="border rounded p-3" style="min-height: 100px; white-space: pre-wrap;">
                {{ notification.message?.zh_TW || '-' }}
              </div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="mb-3">
              <label class="form-label">通知內容（英文）</label>
              <div class="border rounded p-3" style="min-height: 100px; white-space: pre-wrap;">
                {{ notification.message?.en || '-' }}
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- 接收會員列表 -->
    <div class="block block-rounded">
      <div class="block-header block-header-default">
        <h3 class="block-title">接收會員列表</h3>
      </div>
      <div class="block-content">
        <!-- DataTable -->
        <DataTable
          class="table table-bordered table-striped table-vcenter js-dataTable-full"
          :columns="recipientsColumns"
          :options="recipientsOptions"
          ref="recipientsTable"
        />
      </div>
    </div>
  </div>
</template>

<script setup>
import { Link } from "@inertiajs/vue3";
import Layout from "@/Shared/Admin/Layout.vue";
import BreadcrumbItem from "@/Shared/Admin/Partials/BreadcrumbItem.vue";
import DataTablesCore from "datatables.net-bs5";
import DataTable from "datatables.net-vue3";
import { ref, reactive, onMounted, inject } from "vue";
import DataTableHelper from "@/utils/datatableHelper";

const can = inject("can");
const sweetAlert = inject('$sweetAlert');
const isLoading = inject('isLoading');

DataTable.use(DataTablesCore);

// Props
const props = defineProps({
  notification: {
    type: Object,
    required: true,
  },
});

// DataTable refs
const recipientsTable = ref(null);
const recipientsDt = ref(null);
const rows = ref([]);

// 接收者表格欄位設定（動態標題）
const getTimeColumnTitle = () => {
  return props.notification.status === 'sent' ? '發送時間' : '排程時間';
};

const recipientsColumns = [
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
    orderable: false,
    render: (data) => {
      let html = `<div class="fw-bold">${data.user_name || '-'}</div>`;
      if (data.user_email) {
        html += `<small class="text-muted">${data.user_email}</small>`;
      }
      return html;
    },
  },
  {
    title: "註冊時間",
    data: "user_created_at",
    width: "150px",
    className: "text-center",
    orderable: false,
    render: (data) => data || '-',
  },
  {
    title: "發送狀態",
    data: "sent_status_label",
    className: "text-center",
    width: "120px",
    orderable: false,
    render: (data, type, row) => {
      const statusClass = getSentStatusClass(row.sent_status);
      return `<span class="${statusClass}">${data}</span>`;
    },
  },
  {
    title: "已讀狀態",
    data: "read_status_label",
    className: "text-center",
    width: "120px",
    orderable: false,
    render: (data, type, row) => {
      const statusClass = getReadStatusClass(row.read_status);
      return `<span class="${statusClass}">${data}</span>`;
    },
  },
  {
    title: getTimeColumnTitle(),
    data: "sent_at",
    width: "150px",
    className: "text-center",
    orderable: false,
    render: (data, type, row) => data || row.notification_scheduled_at || '-',
  },
  {
    title: "已讀時間",
    data: "read_at",
    width: "150px",
    className: "text-center",
    orderable: false,
    render: (data) => data || '-',
  },
];

// DataTable 選項
const recipientsOptions = reactive({
  ...DataTableHelper.getBaseOptions(),
  searching: false,
  ordering: false,
  ajax: (data, callback) => {
    const extraParams = {
      notification_id: props.notification.id
    };

    DataTableHelper.fetchTableData(
      route("admin.member-notifications.recipients", props.notification.id),
      data,
      callback,
      rows,
      "",              // responseKey 不需要（AJAX 模式）
      extraParams,
      (error) => {
        console.error('DataTable 載入失敗:', error);
      },
      true              // useAjax = true（純 AJAX）
    );
  },
  drawCallback: () => {
    DataTableHelper.defaultDrawCallback();
  },
  order: [[5, "desc"]], // 依「發送時間」排序
});

// 重載表格
const reloadTable = () => {
  try {
    if (recipientsDt.value && typeof recipientsDt.value.ajax?.reload === 'function') {
      recipientsDt.value.ajax.reload(null, false);
    } else if (recipientsTable.value?.dt && typeof recipientsTable.value.dt.ajax?.reload === 'function') {
      recipientsTable.value.dt.ajax.reload(null, false);
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
    if (recipientsTable.value) {
      recipientsDt.value = DataTableHelper.createDataTable(recipientsTable.value);
    }
  } catch (error) {
    console.error('初始化 DataTable 時發生錯誤:', error);
  }
};

// 取得通知狀態標籤
const getStatusLabel = (status) => {
  const labels = {
    draft: '草稿',
    scheduled: '待發送',
    sent: '已發送',
  };
  return labels[status] || status;
};

// 取得通知狀態樣式
const getStatusClass = (status) => {
  const classes = {
    draft: 'badge bg-secondary',
    scheduled: 'badge bg-warning',
    sent: 'badge bg-success',
  };
  return classes[status] || 'badge bg-secondary';
};

// 取得發送狀態樣式
const getSentStatusClass = (status) => {
  const classes = {
    pending: 'badge bg-warning',
    sent: 'badge bg-success',
    failed: 'badge bg-danger',
  };
  return classes[status] || 'badge bg-secondary';
};

// 取得已讀狀態樣式
const getReadStatusClass = (status) => {
  const classes = {
    unread: 'badge bg-warning',
    read: 'badge bg-info',
  };
  return classes[status] || 'badge bg-secondary';
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
