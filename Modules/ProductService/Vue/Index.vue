<!-- Modules/ProductService/Vue/Index.vue -->
<!-- 產品及服務 - 列表頁 (DataTable 分頁版) -->
<template>
    <div class="content">
        <BreadcrumbItem />

        <div class="block block-rounded">
            <div class="block-header block-header-default">
                <h3 class="block-title">產品及服務</h3>
                <div class="block-options">
                    <Link 
                        :href="route('admin.product-services.add')" 
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
                data: "sort",
                width: "60px",
                className: "text-center",
            },
            {
                title: "名稱（中文）",
                data: "name_zh",
            },
            {
                title: "名稱（英文）",
                data: "name_en",
            },
            {
                title: "狀態",
                data: "is_enabled",
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
                    route("admin.product-services.index"),
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
            router.get(route('admin.product-services.edit', id));
        };

        const destroy = (id) => {
            const item = rows.value.find(r => r.id == id);
            const itemName = item?.name_zh || '';
            
            sweetAlert.confirm(`確定要刪除「${itemName}」嗎？`, () => {
                router.delete(route('admin.product-services.destroy', id), {
                    preserveScroll: true,
                    onSuccess: () => {
                        sweetAlert.success({ msg: '刪除成功' });
                        reloadTable();
                    },
                    onError: () => {
                        sweetAlert.error({ msg: '刪除失敗' });
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
