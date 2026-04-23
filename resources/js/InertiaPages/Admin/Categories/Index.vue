<!-- resources/js/InertiaPages/Admin/Categories/Index.vue -->
<template>
    <div class="content">
      <BreadcrumbItem />

      <div class="block block-rounded">
        <div class="block-header block-header-default">
          <h3 class="block-title"></h3>
          <div class="block-options">
            <button
              class="btn btn-info me-2"
              @click="toggleSortMode"
              v-if="!isSortMode && rows.length > 1"
            >
              <i class="fa fa-sort"></i>
              更新排序
            </button>
            <Link
              class="btn btn-primary"
              :href="route(`${routePrefix}.add`)"
              v-if="can(`${routePrefix}.add`)"
            >
              <i class="fa-solid fa-plus opacity-50 me-1"></i>新增{{ categoryTitle }}
            </Link>
          </div>
        </div>

        <div class="block-content block-content-full">
          <!-- 排序模式提示 -->
          <div class="alert alert-info d-flex align-items-center" v-if="isSortMode">
            <i class="fa fa-info-circle me-2"></i>
            <div>
              <strong>排序模式已啟用</strong> - 拖曳表格列來調整順序
              <button class="btn btn-sm btn-success ms-3" @click="saveSortOrder">
                <i class="fa fa-check"></i> 儲存排序
              </button>
              <button class="btn btn-sm btn-secondary ms-2" @click="cancelSortMode">
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
        </div>
      </div>
    </div>
</template>

  <script setup>
  import Layout from "@/Shared/Admin/Layout.vue";
  import BreadcrumbItem from "@/Shared/Admin/Partials/BreadcrumbItem.vue";
  import DataTablesCore from "datatables.net-bs5";
  import DataTable from "datatables.net-vue3";
  import { ref, reactive, onMounted, inject, computed, nextTick } from "vue";
  import DataTableHelper from "@/utils/datatableHelper";
  import { router, Link } from "@inertiajs/vue3";
  import Sortable from 'sortablejs';

  // Props
  const props = defineProps({
    categoryType: String,
    categoryTitle: String,
    allowSubcategories: Boolean,
    maxLevel: Number,
    category: Object,
  })

  // 注入服務
  const can = inject("can");
  const sweetAlert = inject('$sweetAlert');
  const isLoading = inject('isLoading');

  // 響應式資料
  const table = ref(null);
  const rows = ref([]);
  const dt = ref(null);
  const isSortMode = ref(false);
  const sortableInstance = ref(null);
  const searchTerm = ref('');

  DataTable.use(DataTablesCore);

  // 計算路由前綴
  const routePrefix = computed(() => {
    const routeMap = {
      'drama': 'admin.drama-categories',
      'program': 'admin.program-categories',
      'radio': 'admin.radio-categories',
      'article': 'admin.article-categories',
      'news': 'admin.news-categories',
    };
    return routeMap[props.categoryType] || 'admin.categories';
  });

  // 表格欄位設定
  const columns = computed(() => {
    const cols = [
      {
          title: "#",
        data: null,
        className: "text-center",
        orderable: false,
        width: "50px",
        render: (data, type, row, meta) => {
            // 為整行加上 data-id 屬性
            setTimeout(() => {
            const currentRow = document.querySelector(`tbody tr:nth-child(${meta.row + 1})`);
            if (currentRow) {
                currentRow.setAttribute('data-id', row.id);
            }
            }, 0);

            if (isSortMode.value) {
            return `<i class="fa fa-grip-vertical text-muted sort-handle" style="cursor: move;"></i>`;
            }
            return meta.settings._iDisplayStart + meta.row + 1;
        },
    },
    {
      title: props.allowSubcategories === false ? "分類標題" : "主分類標題",
      data: null,
      render: (data) => {
        return `<div class="fw-semibold">${data.name_zh_tw || data.name?.zh_TW || ''}</div>`;
      },
    },
    // 英文欄位已隱藏（此案無多語系）
    {
      title: "排序",
      data: "seq",
      className: "text-center",
      width: "120px",
      orderable: !isSortMode.value,
      render: (data) => {
        if (isSortMode.value) {
          return `<i class="fa fa-arrows-alt text-primary"></i>`;
        }
        return data || 0;
      },
    },
    {
      title: "狀態",
      data: "status",
      className: "text-center",
      width: "80px",
      orderable: false,
      render: (val, type, row) => {
        if (can(`${routePrefix.value}.edit`) && !isSortMode.value) {
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
      className: "text-center",
      defaultContent: "-",
    },
    {
      title: "功能",
      data: null,
      orderable: false,
      className: "text-center",
      width: "120px",
      render: (data) => {
        if (isSortMode.value) {
          return '---';
        }

        let btns = "";
        if (can(`${routePrefix.value}.edit`)) {
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
        if (can(`${routePrefix.value}.delete`)) {
          btns += `
            <button
              type="button"
              class="btn btn-sm btn-danger js-bs-tooltip-enabled delete-btn"
              data-bs-toggle="tooltip"
              aria-label="刪除"
              data-id="${data.id}"
              data-name="${data.name_zh_tw || data.name?.zh_TW || ''}"
            >
              <i class="fa-solid fa-trash"></i>
            </button>`;
        }
        return btns || '---';
      },
    },
    ];
    
    // 根據是否允許子分類，動態插入次分類數量欄位
    if (props.allowSubcategories !== false) {
      // 找到排序欄位的位置，在它之前插入次分類數量
      const sortIndex = cols.findIndex(col => col.title === "排序");
      cols.splice(sortIndex, 0, {
        title: "次分類數量",
        data: "children_count",
        className: "text-center",
        width: "100px",
        orderable: false,
        render: (data) => data || 0,
      });
    }
    
    return cols;
  });

  // DataTable 選項
  const options = reactive({
    ...DataTableHelper.getBaseOptions(),
    ajax: (data, callback) => {
      // 添加分類類型篩選和主分類篩選
      data.categoryType = props.categoryType;
      data.parent_id = null; // 只要主分類

      DataTableHelper.fetchTableData(
        route(routePrefix.value),
        data,
        callback,
        rows,
        "categories"
      );
    },
    drawCallback: () => {
      DataTableHelper.defaultDrawCallback();
      DataTableHelper.bindTableButtonEvents({
        edit: editCategory,
        delete: destroy,
        check: toggleActive,
      });

      // 如果是排序模式，初始化排序功能
      if (isSortMode.value) {
        initSortable();
      }
    },
    // 根據是否有子分類，動態設定排序欄位索引
    // 有子分類時，排序欄位是索引 4（因為插入了次分類數量欄位）
    // 沒有子分類時，排序欄位是索引 3
    order: [[props.allowSubcategories !== false ? 4 : 3, "asc"]], // 依「排序」欄位排序
    rowReorder: false,
  });

  // 獲取 DataTable 實例的輔助函數
  const getDataTableInstance = () => {
    // 嘗試多種方式獲取 DataTable 實例
    if (dt.value && typeof dt.value.page !== 'undefined') {
      return dt.value;
    }

    if (table.value && table.value.dt) {
      return table.value.dt;
    }

    // 最後嘗試通過 DOM 元素獲取
    if (table.value && table.value.$el) {
      const tableElement = table.value.$el.querySelector('table');
      if (tableElement && window.$ && window.$(tableElement).DataTable) {
        try {
          return window.$(tableElement).DataTable();
        } catch (e) {
          console.warn('無法透過 jQuery 取得 DataTable 實例:', e);
        }
      }
    }

    return null;
  };

  // 方法
  const editCategory = (id) => {
    router.get(route(`${routePrefix.value}.edit`, id));
  }

  // 狀態刪除
  const destroy = (id) => {
    sweetAlert.deleteConfirm(`確定要刪除嗎？`, () => {
      isLoading.value = true;
      router.delete(route(`${routePrefix.value}.delete`, id), {
        onSuccess: (finalRes) => {
          const res = finalRes.props.flash?.result;
          if (res) {
            // 無論成功或失敗都顯示訊息
            sweetAlert.resultData(res);

            // 只有成功時才重新載入表格
            if (res.status) {
              reloadTable();
            }
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

  // 狀態變更
  const toggleActive = (id) => {
    isLoading.value = true;
    router.put(route(`${routePrefix.value}.toggle-active`), { id: id }, {
      onSuccess: (finalRes) => {
        const res = finalRes.props.flash?.result;
        if (res && res.status) {
          sweetAlert.resultData(res);
          reloadTable();
        }
      },
      onError: () => {
        sweetAlert.error('狀態切換失敗，請重試！');
      },
      onFinish: () => {
        isLoading.value = false;
      },
    });
  }

  // 更新排序
  const toggleSortMode = () => {
    isSortMode.value = true;

    // 等待下一個 tick 確保 DOM 更新
    nextTick(() => {
      const tableInstance = getDataTableInstance();
      if (tableInstance && tableInstance.page) {
        try {
          // 設定顯示全部資料並按 seq 升序排序
          tableInstance
            .page.len(9999)
            .order([4, 'asc'])  // 第5欄(seq)升序
            .draw();
        } catch (error) {
          console.error('設定排序模式時發生錯誤:', error);
          sweetAlert?.error({ msg: '無法進入排序模式，請重新整理頁面' });
          isSortMode.value = false;
        }
      } else {
        console.error('DataTable 實例未找到');
        sweetAlert?.error({ msg: '表格尚未載入完成，請稍後再試' });
        isSortMode.value = false;
      }
    });
  };

  const cancelSortMode = () => {
    isSortMode.value = false;

    if (sortableInstance.value) {
      try {
        sortableInstance.value.destroy();
        sortableInstance.value = null;
      } catch (error) {
        console.warn('銷毀 Sortable 實例時發生錯誤:', error);
        sortableInstance.value = null;
      }
    }

    // 恢復原本的分頁設定
    nextTick(() => {
      const tableInstance = getDataTableInstance();
      if (tableInstance && tableInstance.page) {
        try {
          tableInstance.page.len(10).draw(); // 恢復每頁10筆
        } catch (error) {
          console.warn('恢復分頁設定時發生錯誤:', error);
        }
      }
      reloadTable();
    });
  };

  const saveSortOrder = () => {
    sweetAlert.confirm('確定更新排序嗎？', () => {
      const tableBody = table.value?.$el?.querySelector('tbody');
      if (!tableBody) {
        sweetAlert.error({ msg: '找不到表格內容' });
        return;
      }

      const rows = Array.from(tableBody.querySelectorAll('tr'));
      const newOrder = rows.map(row => {
        const id = row.getAttribute('data-id');
        return id;
      }).filter(id => id !== null).map(id => parseInt(id));

      if (newOrder.length === 0) {
        sweetAlert.error({ msg: '無法取得排序資料' });
        return;
      }

      isLoading.value = true;
      router.put(route(`${routePrefix.value}.sort`), { ids: newOrder }, {
        onSuccess: (finalRes) => {
          const res = finalRes.props.flash?.result;
          sweetAlert.resultData(res, null, () => {
            // 這個 callback 會在 SweetAlert 對話框關閉後執行
            if (res && res.status) {
              cancelSortMode();
            }
          });
        },
        onError: () => {
          sweetAlert.error('排序失敗，請重試！');
        },
        onFinish: () => {
          isLoading.value = false;
        },
      });
    });
  };

  // 初始化排序
  const initSortable = () => {
    if (sortableInstance.value) {
      try {
        sortableInstance.value.destroy();
      } catch (error) {
        console.warn('銷毀舊 Sortable 實例時發生錯誤:', error);
      }
      sortableInstance.value = null;
    }

    const tableBody = table.value?.$el?.querySelector('tbody');
    if (tableBody) {
      try {
        sortableInstance.value = Sortable.create(tableBody, {
          animation: 150,
          handle: '.sort-handle',
          ghostClass: 'sortable-ghost',
          chosenClass: 'sortable-chosen',
          dragClass: 'sortable-drag'
        });
      } catch (error) {
        console.error('初始化 Sortable 時發生錯誤:', error);
        sweetAlert?.error({ msg: '排序功能初始化失敗' });
      }
    }
  };

  const reloadTable = () => {
    const tableInstance = getDataTableInstance();
    if (tableInstance && tableInstance.ajax && tableInstance.ajax.reload) {
      tableInstance.ajax.reload(null, false);
    } else {
      // 備用方案：重新初始化
      setTimeout(() => {
        dt.value = DataTableHelper.createDataTable(table.value);
      }, 100);
    }
  };

  // 搜尋功能
  const search = () => {
    const tableInstance = getDataTableInstance();
    if (tableInstance) {
      tableInstance.search(searchTerm.value).draw();
    }
  };

  onMounted(() => {
    dt.value = DataTableHelper.createDataTable(table.value);
  });
  </script>

  <script>
  export default {
    layout: Layout,
  };
  </script>

  <style scoped>
  </style>
