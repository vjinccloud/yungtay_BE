<!-- Modules/BannerManagement/Vue/Index.vue -->
<!-- Banner管理 - 列表頁（含篩選 + 分頁） -->
<template>
    <div class="content">
        <BreadcrumbItem />

        <div class="block block-rounded">
            <div class="block-header block-header-default">
                <h3 class="block-title">Banner管理</h3>
                <div class="block-options">
                    <Link :href="route('admin.banner-management.create')" class="btn btn-sm btn-primary">
                        <i class="fa fa-plus me-1"></i> 新增Banner
                    </Link>
                </div>
            </div>

            <!-- 篩選列 -->
            <div class="block-content block-content-full border-bottom bg-body-light">
                <div class="row g-2 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label small mb-1">關鍵字</label>
                        <input
                            type="text"
                            class="form-control form-control-sm"
                            v-model="filterForm.keyword"
                            placeholder="搜尋標題 / 連結..."
                            @keydown.enter="applyFilter"
                        />
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small mb-1">狀態</label>
                        <select class="form-select form-select-sm" v-model="filterForm.status">
                            <option value="">全部</option>
                            <option value="1">啟用</option>
                            <option value="0">停用</option>
                        </select>
                    </div>
                    <div class="col-md-3 d-flex gap-2">
                        <button class="btn btn-sm btn-alt-primary" @click="applyFilter">
                            <i class="fa fa-search me-1"></i> 搜尋
                        </button>
                        <button class="btn btn-sm btn-alt-secondary" @click="resetFilter">
                            <i class="fa fa-undo me-1"></i> 重置
                        </button>
                    </div>
                </div>
            </div>

            <div class="block-content block-content-full">
                <div v-if="items.data.length === 0" class="text-center text-muted py-5">
                    <i class="fa fa-image fa-3x mb-3 d-block opacity-50"></i>
                    <p>尚無Banner，點擊「新增Banner」開始建立</p>
                </div>

                <template v-else>
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-vcenter">
                            <thead>
                                <tr>
                                    <th style="width: 60px;" class="text-center">排序</th>
                                    <th style="width: 120px;" class="text-center">桌機圖片</th>
                                    <th style="width: 80px;" class="text-center">手機圖片</th>
                                    <th>標題</th>
                                    <th style="width: 80px;" class="text-center">狀態</th>
                                    <th style="width: 140px;" class="text-center">更新時間</th>
                                    <th style="width: 130px;" class="text-center">操作</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="item in items.data" :key="item.id">
                                    <td class="text-center text-muted">{{ item.sort_order }}</td>
                                    <td class="text-center">
                                        <img
                                            v-if="item.desktop_image"
                                            :src="item.desktop_image"
                                            class="img-thumbnail"
                                            style="width: 100px; height: 56px; object-fit: cover;"
                                        />
                                        <span v-else class="text-muted small">無圖</span>
                                    </td>
                                    <td class="text-center">
                                        <img
                                            v-if="item.mobile_image"
                                            :src="item.mobile_image"
                                            class="img-thumbnail"
                                            style="width: 46px; height: 67px; object-fit: cover;"
                                        />
                                        <span v-else class="text-muted small">無圖</span>
                                    </td>
                                    <td>
                                        <strong>{{ item.title || '(未設定標題)' }}</strong>
                                        <div v-if="item.url" class="small text-muted text-truncate" style="max-width: 250px;">
                                            {{ item.url }}
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <span
                                            class="badge"
                                            :class="item.is_active ? 'bg-success' : 'bg-secondary'"
                                        >
                                            {{ item.is_active ? '啟用' : '停用' }}
                                        </span>
                                    </td>
                                    <td class="text-center small">
                                        {{ item.updated_at }}
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group btn-group-sm">
                                            <Link :href="route('admin.banner-management.edit', item.id)" class="btn btn-outline-primary" title="編輯">
                                                <i class="fa fa-pencil-alt"></i>
                                            </Link>
                                            <button class="btn btn-outline-danger" title="刪除" @click="deleteItem(item)">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- 分頁 -->
                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <div class="text-muted small">
                            顯示第 {{ items.from }}–{{ items.to }} 筆，共 {{ items.total }} 筆
                        </div>
                        <nav v-if="items.last_page > 1">
                            <ul class="pagination pagination-sm mb-0">
                                <li class="page-item" :class="{ disabled: !items.prev_page_url }">
                                    <a class="page-link" href="#" @click.prevent="goToPage(items.current_page - 1)">
                                        <i class="fa fa-chevron-left"></i>
                                    </a>
                                </li>
                                <li
                                    v-for="p in pageRange"
                                    :key="p"
                                    class="page-item"
                                    :class="{ active: p === items.current_page, disabled: p === '...' }"
                                >
                                    <a v-if="p !== '...'" class="page-link" href="#" @click.prevent="goToPage(p)">{{ p }}</a>
                                    <span v-else class="page-link">…</span>
                                </li>
                                <li class="page-item" :class="{ disabled: !items.next_page_url }">
                                    <a class="page-link" href="#" @click.prevent="goToPage(items.current_page + 1)">
                                        <i class="fa fa-chevron-right"></i>
                                    </a>
                                </li>
                            </ul>
                        </nav>
                    </div>
                </template>
            </div>
        </div>
    </div>
</template>

<script>
import { reactive, computed, inject } from "vue";
import { Link, router } from "@inertiajs/vue3";
import Layout from "@/Shared/Admin/Layout.vue";
import BreadcrumbItem from "@/Shared/Admin/Partials/BreadcrumbItem.vue";

export default {
    components: { BreadcrumbItem, Link },
    props: {
        items:   { type: Object, default: () => ({ data: [], total: 0, current_page: 1, last_page: 1 }) },
        filters: { type: Object, default: () => ({}) },
    },
    setup(props) {
        const sweetAlert = inject('$sweetAlert');

        const filterForm = reactive({
            keyword: props.filters?.keyword || '',
            status:  props.filters?.status ?? '',
        });

        const buildQuery = (extra = {}) => {
            const params = {};
            if (filterForm.keyword)       params.keyword = filterForm.keyword;
            if (filterForm.status !== '')  params.status  = filterForm.status;
            return { ...params, ...extra };
        };

        const applyFilter = () => {
            router.get(route('admin.banner-management.index'), buildQuery(), {
                preserveState: true,
                preserveScroll: true,
            });
        };

        const resetFilter = () => {
            filterForm.keyword = '';
            filterForm.status = '';
            router.get(route('admin.banner-management.index'), {}, {
                preserveState: true,
                preserveScroll: true,
            });
        };

        const goToPage = (page) => {
            if (page < 1 || page > props.items.last_page) return;
            router.get(route('admin.banner-management.index'), buildQuery({ page }), {
                preserveState: true,
                preserveScroll: true,
            });
        };

        const pageRange = computed(() => {
            const total = props.items.last_page;
            const current = props.items.current_page;
            if (total <= 7) {
                return Array.from({ length: total }, (_, i) => i + 1);
            }
            const pages = [];
            pages.push(1);
            if (current > 3) pages.push('...');
            for (let i = Math.max(2, current - 1); i <= Math.min(total - 1, current + 1); i++) {
                pages.push(i);
            }
            if (current < total - 2) pages.push('...');
            pages.push(total);
            return pages;
        });

        const deleteItem = (item) => {
            const name = item.title || `Banner #${item.id}`;
            sweetAlert.confirm(`確定要刪除「${name}」嗎？`, () => {
                router.delete(route('admin.banner-management.destroy', item.id), {
                    preserveScroll: true,
                    onSuccess: () => sweetAlert.success({ msg: '刪除成功' }),
                    onError: () => sweetAlert.error({ msg: '刪除失敗' }),
                });
            });
        };

        return {
            filterForm,
            applyFilter,
            resetFilter,
            goToPage,
            pageRange,
            deleteItem,
        };
    },
    layout: Layout,
};
</script>
