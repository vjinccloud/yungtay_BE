<!-- resources/js/InertiaPages/Admin/Experts/Index.vue -->
<template>
    <div class="content">
        <BreadcrumbItem />

        <div class="block block-rounded">
            <div class="block-header block-header-default">
                <h3 class="block-title">專家管理</h3>
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
                        :href="route('admin.experts.add')"
                        v-if="can('admin.experts.add')"
                    >
                        <i class="fa-solid fa-plus opacity-50 me-1"></i>新增專家
                    </Link>
                </div>
            </div>

            <div class="block-content block-content-full">
                <!-- 篩選器 -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <label class="form-label">分類篩選</label>
                        <select v-model="filter.category_id" class="form-select" @change="reloadTable">
                            <option value="">全部分類</option>
                            <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                                {{ cat.name_zh }}
                            </option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">狀態篩選</label>
                        <select v-model="filter.is_active" class="form-select" @change="reloadTable">
                            <option value="">全部狀態</option>
                            <option value="1">啟用</option>
                            <option value="0">停用</option>
                        </select>
                    </div>
                </div>

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

const props = defineProps({
    categories: {
        type: Array,
        default: () => []
    }
});

const can = inject("can");
const sweetAlert = inject('$sweetAlert');
const table = ref(null);
const dt = ref(null);
const sortableInstance = ref(null);
const isSortMode = ref(false);
const rows = ref([]);
const hasData = ref(false);

const filter = reactive({
    category_id: '',
    is_active: ''
});

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
        title: "圖片",
        data: "image",
        className: "text-center",
        orderable: false,
        width: "100px",
        render: (val) => {
            if (val) {
                return `<img src="${val}" class="img-thumbnail" style="max-width: 80px; max-height: 80px;">`;
            }
            return `<span class="text-muted">無圖片</span>`;
        },
    },
    {
        title: "姓名",
        data: "name_zh",
        width: "150px",
    },
    {
        title: "職稱",
        data: "title_zh",
        width: "150px",
    },
    {
        title: "分類",
        data: "category_name",
        width: "120px",
    },
    {
        title: "領域標籤",
        data: "tags",
        width: "200px",
        render: (val) => {
            if (!val) return '-';
            const tags = val.split(',').map(t => t.trim()).filter(t => t);
            if (tags.length === 0) return '-';
            return tags.map(t => `<span class="badge bg-info me-1">${t}</span>`).join('');
        },
    },
    {
        title: "排序",
        data: "sort_order",
        className: "text-center",
        width: "80px",
    },
    {
        title: "首席專家",
        data: "is_featured",
        width: "100px",
        className: "text-center",
        render: (val, type, row) => {
            if (can("admin.experts.edit")) {
                return `
                <div class="form-check form-switch">
                    <input class="form-check-input toggle-featured" type="checkbox" data-id="${row.id}" ${val ? "checked" : ""}>
                </div>`;
            }
            return `<span class="badge ${val ? 'bg-warning' : 'bg-secondary'}">${val ? '是' : '否'}</span>`;
        },
    },
    {
        title: "啟用狀態",
        data: "is_active",
        width: "100px",
        className: "text-center",
        render: (val, type, row) => {
            if (can("admin.experts.edit")) {
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
            if (can("admin.experts.edit")) {
                btns += `<button type="button" class="btn btn-sm btn-info edit-btn me-2" data-id="${data.id}"><i class="fa fa-edit"></i></button>`;
            }
            if (can("admin.experts.delete")) {
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
            route("admin.experts"),
            data,
            callback,
            rows,
            "experts",
            {
                category_id: filter.category_id,
                field_id: filter.field_id,
                is_active: filter.is_active,
            }
        );
    },
    drawCallback: () => {
        DataTableHelper.defaultDrawCallback();
        DataTableHelper.bindTableButtonEvents({
            edit: editExpert,
            delete: confirmDelete,
            check: toggleActive,
        });
        // 綁定首席專家切換事件
        document.querySelectorAll('.toggle-featured').forEach(el => {
            el.addEventListener('change', (e) => {
                toggleFeatured(e.target.dataset.id);
            });
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

const reloadTable = () => {
    dt.value?.ajax.reload(null, false);
};

const editExpert = (id) => {
    router.visit(route("admin.experts.edit", id));
};

const toggleActive = (id) => {
    router.put(route("admin.experts.toggle-active"), { id }, {
        preserveScroll: true,
        onSuccess: () => dt.value?.ajax.reload(null, false),
    });
};

const toggleFeatured = (id) => {
    router.put(route("admin.experts.toggle-featured"), { id }, {
        preserveScroll: true,
        onSuccess: () => dt.value?.ajax.reload(null, false),
    });
};

const confirmDelete = (id) => {
    sweetAlert.deleteConfirm("確定要刪除嗎？", () => {
        router.delete(route("admin.experts.delete", id), {
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

    router.post(route("admin.experts.sort"), { sorted_ids: sortedIds }, {
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
