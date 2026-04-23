<!-- Modules/MenuSetting/Vue/Index.vue -->
<!-- 選單管理 - 列表頁 (DataTable 分頁版) -->
<template>
    <div class="content">
        <BreadcrumbItem />

        <div class="block block-rounded">
            <div class="block-header block-header-default">
                <h3 class="block-title">選單管理</h3>
                <div class="block-options">
                    <Link 
                        :href="route('admin.menu-settings.add')" 
                        class="btn btn-sm btn-primary"
                    >
                        <i class="fa fa-plus me-1"></i>
                        新增
                    </Link>
                </div>
            </div>

            <div class="block-content block-content-full">
                <!-- DataTable -->
                <DataTable
                    class="table table-bordered table-striped table-vcenter"
                    :columns="columns"
                    :options="options"
                    ref="table"
                />
            </div>
        </div>
    </div>
</template>

<script>
import { ref, reactive, onMounted, inject } from "vue";
import { Link, router } from "@inertiajs/vue3";
import Layout from "@/Shared/Admin/Layout.vue";
import BreadcrumbItem from "@/Shared/Admin/Partials/BreadcrumbItem.vue";
import DataTablesCore from "datatables.net-bs5";
import DataTable from "datatables.net-vue3";
import DataTableHelper from "@/utils/datatableHelper.js";

DataTable.use(DataTablesCore);

export default {
    components: {
        BreadcrumbItem,
        DataTable,
        Link,
    },
    setup() {
        const table = ref(null);
        const rows = ref([]);
        const sweetAlert = inject('$sweetAlert');
        
        const columns = [
            {
                title: "排序",
                data: "seq",
                width: "60px",
                className: "text-center",
            },
            {
                title: "選單名稱",
                data: "title",
                render: (data, type, row) => {
                    // 用縮排表示層級
                    const indent = '　'.repeat(row.level || 0);
                    return indent + data;
                }
            },
            {
                title: "父層",
                data: "parent_title",
                width: "120px",
            },
            {
                title: "層級",
                data: "level",
                width: "60px",
                className: "text-center",
            },
            {
                title: "類型",
                data: "type_label",
                width: "80px",
                className: "text-center",
                render: (data, type, row) => {
                    return row.type == 1 
                        ? '<span class="badge bg-info">顯示</span>'
                        : '<span class="badge bg-secondary">不顯示</span>';
                }
            },
            {
                title: "連結",
                data: "url",
                render: (data) => {
                    return data || '<span class="text-muted">-</span>';
                }
            },
            {
                title: "狀態",
                data: "status",
                className: "text-center",
                width: "80px",
                render: (data) => {
                    return data 
                        ? '<span class="badge bg-success">啟用</span>'
                        : '<span class="badge bg-secondary">停用</span>';
                }
            },
            {
                title: "操作",
                data: null,
                orderable: false,
                className: "text-center",
                width: "120px",
                render: (data, type, row) => {
                    return `
                        <button type="button" class="btn btn-sm btn-info edit-btn me-1" data-id="${row.id}" title="編輯"><i class="fa fa-pencil-alt"></i></button>
                        <button type="button" class="btn btn-sm btn-danger delete-btn" data-id="${row.id}" title="刪除"><i class="fa fa-trash"></i></button>
                    `;
                }
            },
        ];

        const options = reactive({
            ...DataTableHelper.getBaseOptions(),
            ajax: (data, callback) => {
                DataTableHelper.fetchTableData(
                    route("admin.menu-settings.index"),
                    data,
                    callback,
                    rows,
                    'list'
                );
            },
            drawCallback: function () {
                DataTableHelper.defaultDrawCallback();
                DataTableHelper.bindTableButtonEvents({
                    edit: editBtn,
                    delete: destroy,
                });
            },
            order: [[0, "asc"]],
        });

        const editBtn = (id) => {
            router.get(route('admin.menu-settings.edit', id));
        };

        const destroy = (id) => {
            const item = rows.value.find(r => r.id == id);
            const itemName = item?.title || '';
            
            sweetAlert.confirm(`確定要刪除「${itemName}」嗎？`, () => {
                router.delete(route('admin.menu-settings.destroy', id), {
                    preserveScroll: true,
                    onSuccess: () => {
                        sweetAlert.success({ msg: '刪除成功' });
                        reloadTable();
                    },
                    onError: () => {
                        sweetAlert.error({ msg: '刪除失敗，此選單可能還有子選單' });
                    }
                });
            });
        };

        let dt;
        onMounted(() => {
            const el = table.value;
            dt = DataTableHelper.createDataTable(el);
        });

        const reloadTable = () => {
            if (dt) {
                dt.ajax.reload(null, false);
            }
        };

        return {
            table,
            rows,
            columns,
            options,
        };
    },
    layout: Layout,
};
</script>

<style scoped>
@import 'bootstrap';
@import 'datatables.net-bs5';
</style>
