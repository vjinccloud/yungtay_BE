<template>
  <div class="content">
    <BreadcrumbItem />
    <div class="block block-rounded">
      <div class="block-header block-header-default">
        <h3 class="block-title"></h3>
        <Link
          class="btn btn-primary"
          :href="route('admin.administration-settings.add')"
          v-if="can('admin.administration-settings.add')"
        >
            <i class="fa-solid fa-plus opacity-50 me-1 "></i>新增角色
        </Link>
      </div>
      <div class="block-content block-content-full">
        <!-- 使用 datatables.net-vue3 -->
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


<script>
    import Layout from "@/Shared/Admin/Layout.vue";
    import BreadcrumbItem from "@/Shared/Admin/Partials/BreadcrumbItem.vue";
    import DataTablesCore from "datatables.net-bs5";
    import DataTable from "datatables.net-vue3";

    import { router } from "@inertiajs/vue3";
    DataTable.use(DataTablesCore);

    import { ref, reactive, onMounted, nextTick,inject } from "vue";

    import DataTableHelper from "@/utils/datatableHelper.js";
    export default {
        components: {
            BreadcrumbItem,
            DataTable,
        },
        setup() {
            const can = inject('can');
            const table = ref(null); // 引用 DataTable DOM 元素
            const rows = ref([]); // 用於存儲數據

            const columns = [
                {
                    title: "#",
                    data: null,
                    className: "text-center",
                    orderable: false, // 禁用排序
                    render: (data, type, row, meta) => {
                        const currentPageStart = meta.settings._iDisplayStart; // 當前頁起始索引
                        return currentPageStart + meta.row + 1; // 遞增行號
                    },
                },
                { title: "角色名稱", data: "name" },
                { title: "角色描述", data: "description" },
                { title: "管理者人數", data: "users_count", orderable: false, },
                { title: "建立時間", data: "created_at" },
                { title: "更新時間", data: "updated_at",  },
                {
                    title: "功能",
                    data: null,
                    orderable: false,
                    width: "120px",
                    className: "text-center",
                    render: (data, type, row) =>{
                        let btnHtml = "";
                        if(can('admin.administration-settings.edit'))
                            btnHtml +=`<button type="button" class="btn btn-sm btn-info js-bs-tooltip-enabled edit-btn me-2" data-bs-toggle="tooltip" aria-label="編輯" data-bs-title="編輯" data-id="${row.id}"><i class="fa fa-edit"></i></button>`;
                        if(can('admin.administration-settings.delete'))
                            btnHtml += `<button  type="button" class="btn btn-sm btn-danger js-bs-tooltip-enabled delete-btn" data-bs-toggle="tooltip" aria-label="刪除" data-bs-title="刪除" data-id="${row.id}"><i class="fa-solid fa-trash"></i></button>`;
                        return btnHtml;
                    },
                },
            ].filter(col => col !== null);;


            const options = reactive({
                ...DataTableHelper.getBaseOptions(), // 使用基礎配置
                ajax: (data, callback) => {
                    DataTableHelper.fetchTableData(
                        route("admin.administration-settings"),
                        data,
                        callback,
                        rows,
                        'roles',
                    )},
                drawCallback: function () {
                    DataTableHelper.defaultDrawCallback();
                    DataTableHelper.bindTableButtonEvents({
                        edit: editBtn,
                        delete: destroy,
                        check: toggleActive,
                    });
                },
                order: [[4, "asc"]],

            });

            const editBtn = (id) => {
                router.get(route('admin.administration-settings.edit', id));
            };

            const sweetAlert = inject('$sweetAlert');
            const destroy = (id) => {
                // 顯示刪除確認框
                sweetAlert.deleteConfirm('確認是否刪除', () => {
                    // 發送 DELETE 請求
                    router.delete(route('admin.administration-settings.delete', id), {
                        onSuccess: (finalRes) => {
                            const res = finalRes.props.result;
                            sweetAlert.resultData(res); // 顯示成功提示
                            reloadTable(); // 重載表格
                        },
                        onError: () => {
                            sweetAlert.error('刪除失敗，請重試！'); // 顯示失敗提示
                        },
                    });
                });
            };

            const toggleActive = (id)=>{
                router.put(route('admin.admin-settings.toggle-active'),{id:id}, {
                    onSuccess: (finalRes) => {
                        const res = finalRes.props.result;
                        sweetAlert.resultData(res); // 顯示成功提示
                        reloadTable(); // 重載表格
                    },
                    onError: () => {
                        sweetAlert.error('刪除失敗，請重試！'); // 顯示失敗提示
                    },
                });
            }

            let dt;
            onMounted(() => {
                const el = table.value; // 獲取 DataTable 的 DOM 元素
                dt = DataTableHelper.createDataTable(el);
            });

            const reloadTable = () => {
                if (dt) {
                    dt.ajax.reload(null, false); // 重新加载表格数据，但保持分页状态
                }
            };

            return {
                rows,
                columns,
                options,
                table,
                can
            };
        },
        layout: Layout,
    };
</script>



<style scoped>
@import 'bootstrap';
@import 'datatables.net-bs5';
</style>
