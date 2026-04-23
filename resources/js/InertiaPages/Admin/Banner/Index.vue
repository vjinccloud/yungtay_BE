<!-- resources/js/InertiaPages/Admin/Banner/Index.vue -->
<template>
    <div class="content">
      <BreadcrumbItem />

      <div class="block block-rounded">
        <div class="block-header block-header-default">
          <h3 class="block-title">首頁輪播管理</h3>
          <div class="block-options">
            <button
              class="btn btn-info me-2"
              @click="toggleSortMode"
              v-if="hasData && rows.length >= 2 && !isSortMode"
            >
              <i class="fa fa-sort"></i>
              更新排序
            </button>
            <Link
              class="btn btn-primary"
              :href="route('admin.banners.add')"
              v-if="can('admin.banners.add')"
            >
              <i class="fa-solid fa-plus opacity-50 me-1"></i>新增首頁輪播
            </Link>
          </div>
        </div>

        <div class="block-content block-content-full">
          <!-- 排序模式提示 -->
          <div class="alert alert-info d-flex align-items-center" v-if="isSortMode">
            <i class="fa fa-info-circle me-2"></i>
            <div>
              <strong>排序模式已啟用</strong> - 拖曳表格列來調整順序
              <button class="btn btn-sm btn-success ms-3" @click="confirmSaveSortOrder">
                <i class="fa fa-check"></i> 儲存排序
              </button>
              <button class="btn btn-sm btn-secondary ms-2" @click="cancelSortMode">
                <i class="fa fa-times"></i> 取消
              </button>
            </div>
          </div>

          <DataTable
            class="table table-bordered table-striped table-vcenter js-dataTable-full"
            :class="{ 'sortable-table': isSortMode }"
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
import { Link, router } from "@inertiajs/vue3";
import { ref, reactive, inject, onMounted, nextTick, onBeforeUnmount } from "vue";
import DataTableHelper from "@/utils/datatableHelper";
import Sortable from "sortablejs";

DataTable.use(DataTablesCore);

// Injects
const can = inject("can");
const sweetAlert = inject('$sweetAlert');
const isLoading = inject('isLoading');

// Refs
const table = ref(null);
const dt = ref(null);
const sortableInstance = ref(null);

// State
const isSortMode = ref(false);
const rows = ref([]);
const hasData = ref(false);

// DataTable 欄位設定
const columns = reactive([
  {
    title: "#",
    data: null,
    className: "text-center",
    orderable: !isSortMode.value,
    width: "60px",
    render: (data, type, row, meta) => {
      if (isSortMode.value) {
        return `<div class="sort-handle" style="cursor: move;">
                  <i class="fa fa-grip-vertical"></i>
                </div>`;
      }

      const start = meta.settings._iDisplayStart;
      const idx = meta.row + 1;
      setTimeout(() => {
        const tr = document.querySelector(`tbody tr:nth-child(${idx})`);
        if (tr) {
          tr.setAttribute('data-id', row.id);
        }
      }, 0);

      return start + idx;
    },
  },
  // 標題、連結、描述欄位已隱藏
  {
    title: "排序",
    data: "sort_order",
    className: "text-center",
    width: "80px",
    visible: !isSortMode.value,
  },
  {
    title: "狀態",
    data: "is_active",
    className: "text-center",
    width: "100px",
    render: (val, type, row) => {
      if (can('admin.banners.edit')) {
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
    title: "更新時間",
    data: "updated_at",
    width: "140px",
    defaultContent: "-",
  },
  {
    title: "操作",
    data: null,
    orderable: false,
    width: "120px",
    className: "text-center",
    render: (data) => {
      let btns = "";
      if (can('admin.banners.edit')) {
        btns += `
        <a
          href="${route('admin.banners.edit', data.id)}"
          class="btn btn-sm btn-info js-bs-tooltip-enabled me-2"
          data-bs-toggle="tooltip"
          aria-label="編輯"
          data-bs-title="編輯"
        >
          <i class="fa fa-edit"></i>
        </a>`;
      }
      if (can('admin.banners.delete')) {
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
]);

// DataTable 選項
const options = reactive({
  ...DataTableHelper.getBaseOptions(),
  ajax: (data, callback) => {
    DataTableHelper.fetchTableData(
      route('admin.banners'),
      data,
      callback,
      rows,
      'banners'
    );
  },
  rowCallback: (row, data) => {
    try {
      if (row && data && typeof data.id !== 'undefined') {
        row.setAttribute('data-id', data.id);
      }
    } catch (_) {}
  },
  drawCallback: () => {
    DataTableHelper.defaultDrawCallback();
    DataTableHelper.bindTableButtonEvents({
      edit: editBanner,
      delete: destroy,
      check: toggleActive,
    });

    // 初始化排序
    if (isSortMode.value) {
      nextTick(() => initSortable());
    }

    // 更新資料狀態
    hasData.value = rows.value.length > 0;
  },
  order: [[5, "asc"]], // 預設依「排序」欄位排序
});

// 排序相關方法
const toggleSortMode = () => {
  try {
    if (!isSortMode.value) {
      isSortMode.value = true;

      nextTick(() => {
        try {
          const api = getDataTableInstance();
          if (api && typeof api.page === 'function') {
            // 顯示全部資料並依排序欄位排序
            api.page.len(9999).order([5, 'asc']).draw(false);
          } else {
            // 若尚未就緒，延遲再試一次
            setTimeout(() => {
              const api2 = getDataTableInstance();
              try {
                api2?.page?.len(9999).order([5, 'asc']).draw(false);
              } catch (_) {}
            }, 200);
          }
        } catch (_) {}
      });
    }
  } catch (error) {
    console.error('切換排序模式失敗:', error);
    sweetAlert?.error?.({ msg: '無法進入排序模式，請重試' });
  }
};

const cancelSortMode = () => {
  isSortMode.value = false;

  if (sortableInstance.value) {
    try {
      sortableInstance.value.destroy();
      sortableInstance.value = null;
    } catch (error) {
      console.warn('銷毀 Sortable 實例時發生錯誤:', error);
    }
  }

  nextTick(() => {
    try {
      const api = getDataTableInstance();
      if (api && typeof api.page === 'function') {
        // 恢復分頁並重新排序
        api.page.len(10).order([5, 'asc']).draw(false);
      }
    } catch (_) {}
  });
};

const saveSortOrder = () => {
  const tbody = table.value?.$el?.querySelector('tbody');
  if (!tbody) {
    sweetAlert.error({ msg: '找不到表格內容' });
    return;
  }

  const trs = tbody.querySelectorAll('tr');
  const sorted = [];

  trs.forEach((tr, index) => {
    const id = tr.getAttribute('data-id');
    if (id) {
      sorted.push({
        id: parseInt(id),
        sort_order: index + 1
      });
    }
  });

  if (sorted.length === 0) {
    sweetAlert.error({ msg: '無法取得排序資料' });
    return;
  }

  isLoading.value = true;

  router.post(route('admin.banners.sort'), { sorted }, {
    onSuccess: (finalRes) => {
      try {
        const res = finalRes.props.flash?.result || finalRes.props.result;
        if (res && res.status) {
          sweetAlert.resultData(res);
          cancelSortMode();
          reloadTable();
        } else {
          sweetAlert.error({ msg: '排序更新失敗，請重試！' });
        }
      } catch (error) {
        console.error('處理排序回應時發生錯誤:', error);
        sweetAlert.error({ msg: '處理回應時發生錯誤' });
      }
    },
    onError: (errors) => {
      console.error('排序請求失敗:', errors);
      sweetAlert.error({ msg: '排序更新失敗，請重試！' });
    },
    onFinish: () => {
      isLoading.value = false;
    }
  });
};

// 二次確認：送出儲存排序
const confirmSaveSortOrder = () => {
  sweetAlert.confirm('確定更新輪播圖排序嗎？', () => {
    saveSortOrder();
  }, '此操作將更新輪播圖的顯示順序');
};

const initSortable = () => {
  const tbody = table.value?.$el?.querySelector('tbody');
  if (!tbody) return;

  if (sortableInstance.value) {
    try {
      sortableInstance.value.destroy();
    } catch (error) {
      console.warn('銷毀舊 Sortable 實例時發生錯誤:', error);
    }
    sortableInstance.value = null;
  }

  try {
    sortableInstance.value = Sortable.create(tbody, {
      animation: 150,
      handle: '.sort-handle',
      ghostClass: 'sortable-ghost',
      chosenClass: 'sortable-chosen',
      dragClass: 'sortable-drag'
    });
  } catch (error) {
    console.error('初始化 Sortable 時發生錯誤:', error);
  }
};

// 取得 DataTable 實例
const getDataTableInstance = () => {
  if (dt.value && typeof dt.value.page !== 'undefined') return dt.value;
  if (table.value && table.value.dt) return table.value.dt;

  if (table.value && table.value.$el) {
    const node = table.value.$el.querySelector('table');
    if (node && window.$ && window.$(node).DataTable) {
      try {
        return window.$(node).DataTable();
      } catch (e) {
        console.warn('無法取得 DataTable 實例:', e);
      }
    }
  }
  return null;
};

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

// 編輯
const editBanner = (id) => {
  router.get(route('admin.banners.edit', id));
};

// 刪除
const destroy = (id) => {
  sweetAlert.deleteConfirm('確認是否刪除', () => {
    isLoading.value = true;
    router.delete(route('admin.banners.delete', id), {
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

// 切換狀態
const toggleActive = (id) => {
  isLoading.value = true;
  router.put(route('admin.banners.toggle-active'), { id: id }, {
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
};

// 組件掛載
onMounted(() => {
  try {
    initializeDataTable();
  } catch (error) {
    console.error('組件掛載時發生錯誤:', error);
  }
});

// 組件卸載
onBeforeUnmount(() => {
  if (sortableInstance.value) {
    try {
      sortableInstance.value.destroy();
    } catch (error) {
      console.warn('清理 Sortable 實例時發生錯誤:', error);
    }
  }
});

// 暴露方法
defineExpose({
  reloadTable,
  initializeDataTable
});
</script>

<script>
export default {
    layout: Layout,
};
</script>

<style scoped>
.sortable-table tbody tr {
  cursor: move;
}

.sortable-ghost {
  opacity: 0.5;
  background: #f0f0f0;
}

.sortable-chosen {
  background: #f9f9f9;
}

.sortable-drag {
  background: #fff;
  box-shadow: 0 5px 15px rgba(0,0,0,0.3);
}
</style>
