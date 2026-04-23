<!-- resources/js/InertiaPages/Admin/Live/Index.vue -->
<template>
  <div class="content">
    <BreadcrumbItem />

    <div class="block block-rounded">
      <div class="block-header block-header-default">
        <h3 class="block-title"></h3>
        <div>
          <!-- 排序按鈕 -->
          <button
            class="btn btn-info me-2"
            @click="toggleSortMode"
            v-if="!isSortMode && can('admin.lives.edit')"
            :disabled="rows.length <= 1"
          >
            <i class="fa fa-sort"></i>
            更新排序
          </button>
          <Link
            class="btn btn-primary"
            :href="route('admin.lives.add')"
            v-if="can('admin.lives.add')"
          >
            <i class="fa-solid fa-plus opacity-50 me-1"></i>新增直播
          </Link>
        </div>
      </div>

      <div class="block-content block-content-full">
        <!-- 排序模式提示 -->
        <div class="alert alert-info d-flex align-items-center" v-if="isSortMode">
          <i class="fa fa-info-circle me-2"></i>
          <div>
            <strong>排序模式已啟用</strong> - 拖曳表格列來調整直播排序
            <button class="btn btn-sm btn-success ms-3" @click="saveSort">
              <i class="fa fa-check"></i> 儲存排序
            </button>
            <button class="btn btn-sm btn-secondary ms-2" @click="cancelSort">
              <i class="fa fa-times"></i> 取消
            </button>
          </div>
        </div>

        <!-- DataTable -->
        <DataTable
          class="table table-bordered table-striped table-vcenter js-dataTable-full"
          :class="{ 'sortable-table': isSortMode }"
          :columns="columns"
          :options="options"
          ref="table"
        />

        <!-- 影片播放器 -->
        <BaseVideoPlayer
          ref="videoPlayerRef"
          :video="currentVideo"
          :autoplay="true"
          size="md"
          @close="handlePlayerClosed"
        />
      </div>
    </div>
  </div>
</template>

<script setup>
import Layout from "@/Shared/Admin/Layout.vue";
import BreadcrumbItem from "@/Shared/Admin/Partials/BreadcrumbItem.vue";
import BaseVideoPlayer from "@/InertiaPages/Admin/Shared/Content/Components/BaseVideoPlayer.vue";
import DataTablesCore from "datatables.net-bs5";
import DataTable from "datatables.net-vue3";
import { ref, reactive, onMounted, inject, nextTick } from "vue";
import DataTableHelper from "@/utils/datatableHelper";
import { router, Link } from "@inertiajs/vue3";
import Sortable from "sortablejs";

const can = inject("can");
const sweetAlert = inject("$sweetAlert");
const isLoading = inject("isLoading");

const table = ref(null);
const dt = ref(null); // DataTable instance
const rows = ref([]);
const isSortMode = ref(false);
const sortableInstance = ref(null);
const previousState = ref({ len: 10, order: [] });

const videoPlayerRef = ref(null);
const currentVideo = ref(null);

DataTable.use(DataTablesCore);

/* 欄位設定 */
const columns = [
  {
    title: "#",
    data: null,
    className: "text-center",
    orderable: false,
    width: "50px",
    render: (data, type, row, meta) => {
      if (isSortMode.value) {
        return `<i class="fa fa-grip-vertical text-muted sort-handle" style="cursor: move;"></i>`;
      }
      return meta.settings._iDisplayStart + meta.row + 1;
    },
  },
  {
    title: "標題（中文）",
    data: null,
    width: "250px",
    render: (data) => data.title_zh || "",
  },
  {
    title: "標題（EN）",
    data: null,
    width: "200px",
    render: (data) => data.title_en || "",
  },
  {
    title: "影片播放",
    data: null,
    width: "120px",
    className: "text-center",
    orderable: false,
    render: (data) => {
      if (isSortMode.value) return '<i class="fa fa-arrows-alt text-primary"></i>';
      if (data.youtube_url) {
        return `
          <button
            type="button"
            class="btn btn-sm btn-success js-bs-tooltip-enabled play-btn"
            data-bs-toggle="tooltip"
            aria-label="播放影片"
            data-bs-title="播放影片"
            data-id="${data.id}"
          >
            <i class="fa fa-play"></i>
          </button>`;
      }
      return `<span class="text-muted">無影片</span>`;
    },
  },
  {
    title: "啟用狀態",
    data: "is_active",
    width: "100px",
    className: "text-center",
    orderable: false,
    render: (val, type, row) => {
      if (isSortMode.value) 
        return `  
            <span class="badge ${val ? 'bg-success' : 'bg-secondary'}">
              ${val ? '啟用' : '停用'}
            </span>`;
      if (can("admin.lives.edit")) {
        return `
          <div class="form-check form-switch">
            <input
              class="form-check-input toggle-active checked-btn js-bs-tooltip-enabled"
              type="checkbox"
              data-id="${row.id}"
              data-bs-toggle="tooltip"
              aria-label="啟用/停用"
              data-bs-title="啟用/停用"
              ${val ? "checked" : ""}
            >
          </div>`;
      }
      return `
        <span class="badge ${val ? "bg-success" : "bg-secondary"}">
          ${val ? "啟用" : "停用"}
        </span>`;
    },
  },
  {
    title: "前台",
    data: null,
    width: "60px",
    className: "text-center",
    orderable: false,
    render: (data) => {
      if (isSortMode.value) return '<span class="text-muted">---</span>';
      const frontendUrl = route('live.index', { id: data.id });
      return `<a href="${frontendUrl}" target="_blank" class="btn btn-sm btn-outline-primary js-bs-tooltip-enabled" title="查看前台" data-bs-toggle="tooltip" data-bs-title="查看前台">
          <i class="fa fa-eye"></i>
      </a>`;
    },
  },
  {
    title: "排序",
    data: "sort_order",
    defaultContent: "-",
    className: "text-center",
    width: "80px",
    orderable: true,
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
      if (isSortMode.value) return '<span class="text-muted">---</span>';
      let btns = "";
      if (can("admin.lives.edit")) {
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
      if (can("admin.lives.delete")) {
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
      return btns || "---";
    },
  },
];

/* 選項：保留 baseOptions，並在 drawCallback 前先跑 baseOptions.drawCallback */
const baseOptions = DataTableHelper.getBaseOptions();

const options = reactive({
  ...baseOptions,
  ajax: (data, callback) => {
    // 強制預設排序為 sort_order
    if (!data.order || data.order.length === 0) {
      data.order = [{ column: 5, dir: 'asc' }];
    }
    DataTableHelper.fetchTableData(route("admin.lives"), data, callback, rows, "lives");
  },
  // 依「排序」欄位 (index 5) 預設排序
  order: [[5, "asc"]],
  rowReorder: false,
  // ✅ 用 createdRow 寫入 tr 的 data-id，給排序使用
  createdRow: (row, data) => {
    row.setAttribute("data-id", data.id);
  },
  // ✅ 先跑 baseOptions.drawCallback（內含 tooltip + search 修正），再綁事件/初始化排序
  drawCallback: function () {
    baseOptions.drawCallback?.call(this);
    DataTableHelper.bindTableButtonEvents({
      edit: editLive,
      delete: destroy,
      check: toggleActive,
      play: playVideo,
    });

    if (isSortMode.value) {
      initSortable();
    } else if (sortableInstance.value) {
      try { sortableInstance.value.destroy(); } catch {}
      sortableInstance.value = null;
    }
  },
});

/* DataTable instance helpers */
const getDataTableInstance = () => {
  if (dt.value && typeof dt.value.page !== "undefined") return dt.value;
  if (table.value?.dt) return table.value.dt;
  if (table.value?.$el) {
    const el = table.value.$el.querySelector("table");
    if (el && window.$?.fn?.DataTable) {
      try { return window.$(el).DataTable(); } catch {}
    }
  }
  return null;
};

const initializeDataTable = () => {
  try {
    if (table.value) dt.value = DataTableHelper.createDataTable(table.value);
  } catch (e) {
    console.error("初始化 DataTable 時發生錯誤:", e);
  }
};

const reloadTable = () => {
  try {
    const inst = getDataTableInstance();
    if (inst?.ajax?.reload) {
      inst.ajax.reload(() => {
        // 重新載入後強制依排序欄位升冪
        try { inst.order([5, "asc"]).draw(false); } catch {}
      }, false);
    }
    else initializeDataTable();
  } catch (e) {
    console.error("重載表格時發生錯誤:", e);
    sweetAlert?.error({ msg: "重載表格失敗，請刷新頁面" });
  }
};

// 初次載入後強制套用預設排序（避免 stateSave 或外部狀態干擾）
const enforceDefaultSort = () => {
  const inst = getDataTableInstance();
  if (inst?.order) {
    try { inst.order([5, "asc"]).draw(false); } catch {}
  }
};

/* 播放影片 */
const playVideo = (id) => {
  const live = rows.value.find((r) => r.id == id);
  if (live && videoPlayerRef.value) {
    currentVideo.value = {
      id: live.id,
      title: live.title_zh || live.title_en || "直播",
      video_type: "youtube",
      youtube_url: live.youtube_url,
      content_type: "live",
      seq: 1,
      description: { zh_TW: "直播內容" },
    };
    videoPlayerRef.value.openModal();
  }
};

/* 關閉播放器時清理狀態，確保下次為全新載入 */
const handlePlayerClosed = () => {
  // 清空當前影片，避免 reactivity 緩存
  currentVideo.value = null;
};

/* 編輯 / 刪除 / 狀態切換 */
const editLive = (id) => router.get(route("admin.lives.edit", id));

const destroy = (id) => {
  sweetAlert.deleteConfirm("確認是否刪除", () => {
    isLoading.value = true;
    router.delete(route("admin.lives.delete", id), {
      onSuccess: (finalRes) => {
        const res = finalRes.props.flash?.result || finalRes.props.result;
        if (res?.status) {
          sweetAlert.resultData(res);
          reloadTable();
        } else {
          sweetAlert.error({ msg: "刪除失敗，請重試！" });
        }
      },
      onError: () => sweetAlert.error({ msg: "刪除失敗，請重試！" }),
      onFinish: () => (isLoading.value = false),
    });
  });
};

const toggleActive = (id) => {
  isLoading.value = true;
  router.put(route("admin.lives.toggle-active"), { id }, {
    onSuccess: (finalRes) => {
      const res = finalRes.props.flash?.result || finalRes.props.result;
      if (res?.status) {
        sweetAlert.resultData(res);
        reloadTable();
      } else {
        sweetAlert.error({ msg: "狀態切換失敗，請重試！" });
      }
    },
    onError: () => sweetAlert.error({ msg: "狀態切換失敗，請重試！" }),
    onFinish: () => (isLoading.value = false),
  });
};

/* 排序：初始化/進入/取消/儲存 */
const initSortable = async () => {
  await nextTick();
  const tbody = table.value?.$el?.querySelector("tbody");
  if (!tbody) return;

  if (sortableInstance.value) {
    try { sortableInstance.value.destroy(); } catch {}
    sortableInstance.value = null;
  }

  sortableInstance.value = Sortable.create(tbody, {
    handle: ".sort-handle",
    animation: 150,
  });
};

const toggleSortMode = () => {
  isSortMode.value = true;

  nextTick(() => {
    const inst = getDataTableInstance();
    if (!inst?.page) {
      sweetAlert?.error({ msg: "表格尚未載入完成，請稍後再試" });
      isSortMode.value = false;
      return;
    }
    try {
      // ✅ 記住目前分頁與排序
      previousState.value = {
        len: inst.page.len(),
        order: inst.order(), // ex: [[5,'asc']]
      };

      // 顯示全部並依「排序」欄 (index 5) 升冪
      inst.page.len(9999).order([5, "asc"]).draw();
    } catch (e) {
      console.error("設定排序模式時發生錯誤:", e);
      sweetAlert?.error({ msg: "無法進入排序模式，請重新整理頁面" });
      isSortMode.value = false;
    }
  });
};

const cancelSort = () => {
  isSortMode.value = false;

  if (sortableInstance.value) {
    try { sortableInstance.value.destroy(); } catch {}
    sortableInstance.value = null;
  }

  nextTick(() => {
    const inst = getDataTableInstance();
    if (inst?.page) {
      try {
        const { len, order } = previousState.value || {};
        if (len) inst.page.len(len);
        if (order?.length) inst.order(order);
        inst.draw(false);
      } catch (e) {
        console.warn("恢復分頁/排序設定時發生錯誤:", e);
      }
    }
    reloadTable();
  });
};

const saveSort = () => {
  sweetAlert.confirm("確定更新排序嗎？", () => {
    try {
      isLoading.value = true;

      // ✅ 直接依照 DOM 目前 tr 順序讀取 data-id，最準確
      const tbody = table.value?.$el?.querySelector("tbody");
      if (!tbody) throw new Error("找不到表格內容");

      const ids = Array.from(tbody.querySelectorAll("tr"))
        .map((tr) => parseInt(tr.getAttribute("data-id")))
        .filter((id) => Number.isInteger(id));

      if (!ids.length) throw new Error("無法取得排序資料");

      const sortData = ids.map((id, idx) => ({ id, sort_order: idx + 1 }));

      router.put(
        route("admin.lives.sort"),
        { items: sortData },
        {
          onSuccess: (response) => {
            const result = response.props.flash?.result || response.props.result;
            if (result?.status) {
              sweetAlert.success(result);
              cancelSort();
            } else {
              sweetAlert.error({ msg: "排序更新失敗，請重試！" });
            }
          },
          onError: () => sweetAlert.error({ msg: "排序更新失敗，請重試！" }),
          onFinish: () => (isLoading.value = false),
        }
      );
    } catch (e) {
      console.error("儲存排序時發生錯誤:", e);
      sweetAlert.error({ msg: "排序更新失敗" });
      isLoading.value = false;
    }
  });
};

/* 掛載 */
onMounted(() => {
  try {
    initializeDataTable();
    // 稍後套用預設排序，確保新增/編輯返回列表時排序正確
    setTimeout(enforceDefaultSort, 200);
  } catch (e) {
    console.error("組件掛載時發生錯誤:", e);
  }
});
</script>

<script>
export default { layout: Layout };
</script>

<style scoped>
.sortable-table tbody tr { cursor: move; }
.sortable-table tbody tr:hover { background-color: #f8f9fa; }
.sort-handle { cursor: grab; }
.sort-handle:active { cursor: grabbing; }
</style>
