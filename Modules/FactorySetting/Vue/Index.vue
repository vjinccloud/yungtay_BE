<!-- Modules/FactorySetting/Vue/Index.vue -->
<!-- 工廠設定 - 列表頁面 (DataTable 分頁版) -->
<template>
    <div class="content">
        <BreadcrumbItem />

        <div class="block block-rounded">
            <div class="block-header block-header-default">
                <h3 class="block-title">工廠設定</h3>
            </div>

            <div class="block-content block-content-full">
                <!-- 篩選區 -->
                <div class="row mb-4 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label">據點篩選</label>
                        <select v-model="filterRegionId" class="form-select" @change="reloadTable">
                            <option value="">全部據點</option>
                            <option v-for="region in regions" :key="region.id" :value="region.id">
                                {{ region.name?.zh_TW || region.name }}
                            </option>
                        </select>
                    </div>
                </div>

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
import { router } from "@inertiajs/vue3";
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
    },
    props: {
        regions: {
            type: Array,
            default: () => []
        }
    },
    setup(props) {
        const table = ref(null);
        const rows = ref([]);
        const filterRegionId = ref('');
        
        const columns = [
            {
                title: "ID",
                data: "id",
                width: "50px",
                className: "text-center",
            },
            {
                title: "據點",
                data: "region",
                width: "100px",
                orderable: false,
                render: (data) => {
                    if (data?.name?.zh_TW) {
                        return `<span class="badge bg-info">${data.name.zh_TW}</span>`;
                    }
                    return '-';
                }
            },
            {
                title: "名稱",
                data: "name",
                render: (data) => {
                    const zhName = data?.zh_TW || '-';
                    const enName = data?.en || '';
                    return `<div>${zhName}</div><small class="text-muted">${enName}</small>`;
                }
            },
            {
                title: "地址",
                data: "address",
                render: (data) => {
                    const zhAddr = data?.zh_TW || '-';
                    const enAddr = data?.en || '';
                    return `<div>${zhAddr}</div><small class="text-muted">${enAddr}</small>`;
                }
            },
            {
                title: "成立日期",
                data: "established_date",
                width: "120px",
                render: (data) => data || '-'
            },
            {
                title: "國家名稱",
                data: "country_name",
                width: "120px",
                render: (data) => data?.zh_TW || '-'
            },
            // {
            //     title: "媒體",
            //     data: null,
            //     orderable: false,
            //     className: "text-center",
            //     width: "100px",
            //     render: (data, type, row) => {
            //         let badges = '<div class="d-flex flex-wrap justify-content-center gap-1">';
            //         if (row.image) badges += '<span class="badge bg-success" title="主圖"><i class="fa fa-image"></i></span>';
            //         if (row.logo) badges += '<span class="badge bg-primary" title="Logo"><i class="fa fa-star"></i></span>';
            //         if (row.has_images) badges += '<span class="badge bg-warning" title="多張圖片"><i class="fa fa-images"></i></span>';
            //         if (row.has_visit_video) badges += '<span class="badge bg-danger" title="訪廠影片"><i class="fa fa-video"></i></span>';
            //         if (row.has_video_360) badges += '<span class="badge bg-secondary" title="360影片"><i class="fa fa-vr-cardboard"></i></span>';
            //         badges += '</div>';
            //         return badges;
            //     }
            // },
            {
                title: "排序",
                data: "sort",
                className: "text-center",
                width: "80px",
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
                width: "100px",
                render: (data, type, row) => {
                    return `<button type="button" class="btn btn-sm btn-alt-primary edit-btn" data-id="${row.id}" title="編輯"><i class="fa fa-pencil-alt"></i></button>`;
                }
            },
        ];

        const options = reactive({
            ...DataTableHelper.getBaseOptions(),
            language: {
                ...DataTableHelper.getBaseOptions().language,
                searchPlaceholder: "名稱或國家名稱",
            },
            ajax: (data, callback) => {
                DataTableHelper.fetchTableData(
                    route("admin.factory-settings.index"),
                    data,
                    callback,
                    rows,
                    'items',
                    { region_id: filterRegionId.value || undefined }
                );
            },
            drawCallback: function () {
                DataTableHelper.defaultDrawCallback();
                DataTableHelper.bindTableButtonEvents({
                    edit: editBtn,
                });
            },
            order: [[0, "asc"]],
        });

        const editBtn = (id) => {
            router.get(route('admin.factory-settings.edit', id));
        };

        let dt;
        onMounted(() => {
            // 獲取 DataTable API 實例
            dt = table.value?.dt;
        });

        const reloadTable = () => {
            // 確保使用正確的 DataTable API
            const dtApi = table.value?.dt || dt;
            if (dtApi) {
                // 第二個參數 true 表示重置到第一頁
                dtApi.ajax.reload(null, true);
            }
        };

        const resetFilter = () => {
            filterRegionId.value = '';
            reloadTable();
        };

        return {
            table,
            rows,
            columns,
            options,
            filterRegionId,
            reloadTable,
            resetFilter,
        };
    },
    layout: Layout,
};
</script>

<style scoped>
@import 'bootstrap';
@import 'datatables.net-bs5';
</style>
