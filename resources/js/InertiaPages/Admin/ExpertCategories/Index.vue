<!-- resources/js/InertiaPages/Admin/ExpertCategories/Index.vue -->
<template>
    <div class="content">
        <BreadcrumbItem />

        <div class="block block-rounded">
            <div class="block-header block-header-default">
                <h3 class="block-title">專家分類管理</h3>
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
                        :href="route('admin.expert-categories.add')"
                        v-if="can('admin.expert-categories.add')"
                    >
                        <i class="fa-solid fa-plus opacity-50 me-1"></i>新增專家分類
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
import { ref, reactive, onMounted, inject, nextTick } from "vue";
import DataTableHelper from "@/utils/datatableHelper";
import { router, Link } from "@inertiajs/vue3";
import Sortable from "sortablejs";

DataTable.use(DataTablesCore);

const can = inject("can");
const sweetAlert = inject('$sweetAlert');
const table = ref(null);
const dt = ref(null);
const sortableInstance = ref(null);
const isSortMode = ref(false);
const rows = ref([]);
const hasData = ref(false);

// 表格欄位設定
const columns = [
    {
        title: "#",
        data: null,
        className: "text-center",
        orderable: false,
        width: "60px",
        render: (data, type, row, meta) => {
            const idx = meta.row + 1;
            setTimeout(() => {
                const tr = document.querySelector(`tbody tr:nth-child(${idx})`);
                if (tr) tr.setAttribute('data-id', row.id);
            }, 0);
            if (isSortMode.value) {
                return `<div class="sort-handle" style="cursor: move;"><i class="fa fa-grip-vertical"></i></div>`;
            }
            return meta.settings._iDisplayStart + idx;
        },
    },
    {
        title: "分類名稱",
        data: "name_zh",
        width: "300px",
    },
    {
        title: "專家數量",
        data: "experts_count",
        className: "text-center",
        width: "100px",
    },
    {
        title: "排序",
        data: "sort_order",
        className: "text-center",
        width: "80px",
    },
    {
        title: "啟用狀態",
        data: "is_active",
        width: "100px",
        className: "text-center",
        render: (val, type, row) => {
            if (can("admin.expert-categories.edit")) {
                return `
                <div class="form-check form-switch">
                    <input class="form-check-input toggle-active checked-btn" type="checkbox" data-id="${row.id}" ${val ? "checked" : ""}>
                </div>`;
            }
            return `<span class="badge ${val ? 'bg-success' : 'bg-secondary'}">${val ? '啟用' : '停用'}</span>`;
        },
    },
    {
        title: "修改日期",
        data: "updated_at",
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
            let btns = "";
            if (can("admin.expert-categories.edit")) {
                btns += `<button type="button" class="btn btn-sm btn-info edit-btn me-2" data-id="${data.id}"><i class="fa fa-edit"></i></button>`;
            }
            if (can("admin.expert-categories.delete")) {
                btns += `<button type="button" class="btn btn-sm btn-danger delete-btn" data-id="${data.id}"><i class="fa fa-trash"></i></button>`;
            }
            return btns || "-";
        },
    },
];

// DataTable 選項
const options = reactive({
    ...DataTableHelper.getBaseOptions(),
    ajax: (data, callback) => {
        DataTableHelper.fetchTableData(
            route("admin.expert-categories"),
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
            delete: confirmDelete,
            check: toggleActive,
        });
        hasData.value = rows.value.length > 0;
        if (isSortMode.value) initSortable();
    },
    order: [[0, "asc"]],
});

// 事件處理
onMounted(() => {
    nextTick(() => {
        dt.value = table.value?.dt;
    });
});

const editCategory = (id) => {
    router.visit(route("admin.expert-categories.edit", id));
};

const toggleActive = (id) => {
    router.put(route("admin.expert-categories.toggle-active"), { id }, {
        preserveScroll: true,
        onSuccess: () => dt.value?.ajax.reload(null, false),
    });
};

const confirmDelete = (id) => {
    sweetAlert.deleteConfirm("確定要刪除嗎？", () => {
        router.delete(route("admin.expert-categories.delete", id), {
            preserveScroll: true,
            onSuccess: () => dt.value?.ajax.reload(null, false),
        });
    });
};

// 排序功能
const toggleSortMode = () => {
    isSortMode.value = true;
    nextTick(() => {
        dt.value?.ajax.reload(null, false);
    });
};

const cancelSortMode = () => {
    isSortMode.value = false;
    dt.value?.ajax.reload(null, false);
};

const initSortable = () => {
    const tbody = document.querySelector(".table tbody");
    if (!tbody || sortableInstance.value) return;

    sortableInstance.value = Sortable.create(tbody, {
        animation: 150,
        handle: ".sort-handle",
        onEnd: () => {
            rows.value = Array.from(tbody.querySelectorAll("tr")).map(tr => tr.dataset.id);
        },
    });
};

const saveSortOrder = () => {
    const tbody = document.querySelector(".table tbody");
    const sortedIds = Array.from(tbody.querySelectorAll("tr")).map(tr => tr.dataset.id);

    router.post(route("admin.expert-categories.sort"), { sorted_ids: sortedIds }, {
        preserveScroll: true,
        onSuccess: () => {
            sweetAlert.showToast("排序更新成功", "success");
            isSortMode.value = false;
            sortableInstance.value?.destroy();
            sortableInstance.value = null;
            dt.value?.ajax.reload(null, false);
        },
    });
};
</script>

<script>
export default { layout: Layout };
</script>

<style scoped>
.sortable-table tbody tr {
    cursor: move;
}
.sort-handle {
    padding: 5px;
}
</style>
