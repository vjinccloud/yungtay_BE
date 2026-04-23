<!-- resources/js/InertiaPages/Admin/Shared/Themes/Index.vue -->
<!-- 共用的主題列表頁面組件（影音主題/節目主題） -->
<template>
    <div class="content">
      <BreadcrumbItem />

      <div class="block block-rounded">
        <div class="block-header block-header-default">
            <h3 class="block-title">{{ title }}</h3>
            <div class="block-options">
                <button
                    class="btn btn-info me-2"
                    @click="toggleSortMode"
                    v-if="hasData && !isSortMode"
                >
                    <i class="fa fa-sort"></i>
                    更新排序
                </button>
                <Link
                    class="btn btn-primary"
                    :href="addRoute"
                    v-if="can(addPermission)"
                >
                    <i class="fa-solid fa-plus opacity-50 me-1"></i>{{ addButtonText }}
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
                :columns="mergedColumns"
                :options="mergedOptions"
                ref="table"
            />
        </div>
      </div>
    </div>
</template>

<script setup>
import BreadcrumbItem from "@/Shared/Admin/Partials/BreadcrumbItem.vue";
import DataTablesCore from "datatables.net-bs5";
import DataTable from "datatables.net-vue3";
import { ref, reactive, onMounted, inject, computed, nextTick, onBeforeUnmount } from "vue";
import DataTableHelper from "@/utils/datatableHelper";
import { router, Link } from "@inertiajs/vue3";
import Sortable from "sortablejs";

DataTable.use(DataTablesCore);

// Props
const props = defineProps({
    contentType: {
        type: String,
        required: true,
        validator: (value) => ['drama', 'program', 'radio'].includes(value)
    }
})

// Injects
const can = inject("can");
const sweetAlert = inject('$sweetAlert');
// 與舊動線相容：從全域提供的 route 幫手（若存在）產生 URL
const route = inject('route', (name, ...params) => window?.route ? window.route(name, ...params) : name);
const isLoading = inject('isLoading')

// Refs
const table = ref(null);
const dt = ref(null);
const sortableInstance = ref(null);

// State
const isSortMode = ref(false);
const rows = ref([]);
const hasData = ref(false);

// 計算屬性
const config = computed(() => {
    const configs = {
        drama: {
            title: '影音主題管理',
            addButtonText: '新增影音主題',
            addRoute: 'admin.drama-themes.add',
            addPermission: 'admin.drama-themes.add',
            editPermission: 'admin.drama-themes.edit',
            deletePermission: 'admin.drama-themes.delete',
            indexRoute: 'admin.drama-themes',
            editRoute: 'admin.drama-themes.edit',
            deleteRoute: 'admin.drama-themes.delete',
            toggleRoute: 'admin.drama-themes.toggle-active',
            sortRoute: 'admin.drama-themes.sort',
            responseKey: 'themes',
            contentLabel: '影音'
        },
        program: {
            title: '節目主題管理',
            addButtonText: '新增節目主題',
            addRoute: 'admin.program-themes.add',
            addPermission: 'admin.program-themes.add',
            editPermission: 'admin.program-themes.edit',
            deletePermission: 'admin.program-themes.delete',
            indexRoute: 'admin.program-themes',
            editRoute: 'admin.program-themes.edit',
            deleteRoute: 'admin.program-themes.delete',
            toggleRoute: 'admin.program-themes.toggle-active',
            sortRoute: 'admin.program-themes.sort',
            responseKey: 'themes',
            contentLabel: '節目'
        },
        radio: {
            title: '廣播主題管理',
            addButtonText: '新增廣播主題',
            addRoute: 'admin.radio-themes.add',
            addPermission: 'admin.radio-themes.add',
            editPermission: 'admin.radio-themes.edit',
            deletePermission: 'admin.radio-themes.delete',
            indexRoute: 'admin.radio-themes',
            editRoute: 'admin.radio-themes.edit',
            deleteRoute: 'admin.radio-themes.delete',
            toggleRoute: 'admin.radio-themes.toggle-active',
            sortRoute: 'admin.radio-themes.sort',
            responseKey: 'themes',
            contentLabel: '廣播'
        }
    }
    return configs[props.contentType]
})

const title = computed(() => config.value.title)
const addButtonText = computed(() => config.value.addButtonText)
const addRoute = computed(() => route(config.value.addRoute))
const addPermission = computed(() => config.value.addPermission)

// 預設欄位設定
const defaultColumns = computed(() => [
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
    {
        title: "主題名稱（中文）",
        data: null,
        width: "200px",
        render: (val, type, row) => {
            // drama: name_zh；program: name.zh_TW
            return row?.name_zh ?? row?.name?.zh_TW ?? '';
        },
    },
    {
        title: "主題名稱（英文）",
        data: null,
        width: "200px",
        render: (val, type, row) => {
            // drama: name_en；program: name.en
            return row?.name_en ?? row?.name?.en ?? '';
        },
    },
    {
        title: `${config.value.contentLabel}數量`,
        data: `${props.contentType}s_count`,
        className: "text-center",
        width: "100px",
        defaultContent: "0",
    },
    {
        title: "排序",
        data: "sort_order",
        className: "text-center",
        width: "80px",
        visible: !isSortMode.value,
    },
    {
        title: "啟用狀態",
        data: "is_active",
        className: "text-center",
        width: "100px",
        render: (val, type, row) => {
            if (can(config.value.editPermission)) {
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
        title: "修改日期",
        data: "updated_at",
        width: "140px",
        defaultContent: "-",
    },
    {
        title: "功能",
        data: null,
        orderable: false,
        width: "120px",
        className: "text-center",
        render: (data) => {
            let btns = "";
            if (can(config.value.editPermission)) {
                // 使用傳統超連結，符合原本動線（可支援新分頁開啟）
                const editUrl = typeof route === 'function' ? route(config.value.editRoute, data.id) : '#';
                btns += `
                <a
                    href="${editUrl}"
                    class="btn btn-sm btn-info js-bs-tooltip-enabled me-2"
                    data-bs-toggle="tooltip"
                    aria-label="編輯"
                    data-bs-title="編輯"
                >
                    <i class="fa fa-edit"></i>
                </a>`;
            }
            if (can(config.value.deletePermission)) {
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
])

// 合併欄位
const mergedColumns = computed(() => defaultColumns.value)

// DataTable 選項
const defaultOptions = computed(() => ({
    ...DataTableHelper.getBaseOptions(),
    ajax: (data, callback) => {
        DataTableHelper.fetchTableData(
            route(config.value.indexRoute),
            data,
            callback,
            rows,
            config.value.responseKey
        );
    },
    // 確保每一列的 <tr> 都帶有 data-id，便於拖曳後依 DOM 讀取正確順序
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
            edit: editTheme,
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
    order: [[4, "asc"]], // 預設依「排序」欄位排序
}))

// 合併選項
const mergedOptions = computed(() => {
    return reactive({
        ...defaultOptions.value
    })
})

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
                        api.page.len(9999).order([4, 'asc']).draw(false);
                    } else {
                        // 若尚未就緒，延遲再試一次
                        setTimeout(() => {
                            const api2 = getDataTableInstance();
                            try {
                                api2?.page?.len(9999).order([4, 'asc']).draw(false);
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
                api.page.len(10).order([4, 'asc']).draw(false);
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
    const newOrder = [];
    
    trs.forEach((tr) => {
        const id = tr.getAttribute('data-id');
        if (id) {
            newOrder.push(parseInt(id));
        }
    });

    if (newOrder.length === 0) {
        sweetAlert.error({ msg: '無法取得排序資料' });
        return;
    }

    isLoading.value = true;

    // 根據 contentType 決定請求格式和方法
    if (props.contentType === 'drama') {
        // 影音主題：使用 PUT 請求，傳送 { ids: newOrder }
        router.put(route(config.value.sortRoute), { ids: newOrder }, {
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
    } else {
        // 節目/廣播主題：使用 PUT 請求，傳送純 ID 陣列
        router.put(route(config.value.sortRoute), newOrder, {
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
    }
};

// 二次確認：送出儲存排序
const confirmSaveSortOrder = () => {
    sweetAlert.confirm(`確定更新${config.value.contentLabel}主題排序嗎？`, () => {
        saveSortOrder();
    }, '此操作將更新主題的顯示順序');
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
const editTheme = (id) => {
    router.get(route(config.value.editRoute, id));
}

// 刪除
const destroy = (id) => {
    sweetAlert.deleteConfirm('確認是否刪除', () => {
        isLoading.value = true;
        router.delete(route(config.value.deleteRoute, id), {
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
    router.put(route(config.value.toggleRoute), { id: id }, {
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
}

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
})
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