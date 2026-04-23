<!-- Modules/NewsManagement/Vue/Index.vue -->
<!-- 最新消息管理 - 列表頁 -->
<template>
    <div class="content">
        <BreadcrumbItem />

        <div class="block block-rounded">
            <div class="block-header block-header-default">
                <h3 class="block-title">最新消息管理</h3>
                <div class="block-options">
                    <Link :href="route('admin.news-management.create')" class="btn btn-sm btn-primary">
                        <i class="fa fa-plus me-1"></i> 新增最新消息
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
                            placeholder="搜尋標題 / 描述..."
                            @keydown.enter="applyFilter"
                        />
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small mb-1">分類</label>
                        <select class="form-select form-select-sm" v-model="filterForm.category_id">
                            <option value="">全部</option>
                            <option
                                v-for="cat in categories"
                                :key="cat.id"
                                :value="cat.id"
                            >{{ cat.name }}</option>
                        </select>
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
                    <i class="fa fa-newspaper fa-3x mb-3 d-block opacity-50"></i>
                    <p>尚無最新消息，點擊「新增最新消息」開始建立</p>
                </div>

                <template v-else>
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-vcenter">
                            <thead>
                                <tr>
                                    <th style="width: 80px;" class="text-center">圖片</th>
                                    <th>標題</th>
                                    <th style="width: 120px;" class="text-center">分類</th>
                                    <th style="width: 110px;" class="text-center">上架日期</th>
                                    <th style="width: 100px;" class="text-center">首頁曝光</th>
                                    <th style="width: 100px;" class="text-center">置頂</th>
                                    <th style="width: 80px;" class="text-center">狀態</th>
                                    <th style="width: 140px;" class="text-center">更新時間</th>
                                    <th style="width: 130px;" class="text-center">操作</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="item in items.data" :key="item.id">
                                    <td class="text-center">
                                        <img
                                            v-if="item.img"
                                            :src="item.img"
                                            class="img-thumbnail"
                                            style="width: 60px; height: 34px; object-fit: cover;"
                                        />
                                        <span v-else class="text-muted small">無圖</span>
                                    </td>
                                    <td>
                                        <strong>{{ item.title || '(未設定標題)' }}</strong>
                                    </td>
                                    <td class="text-center small">
                                        {{ item.category_name || '-' }}
                                    </td>
                                    <td class="text-center small">
                                        {{ item.published_date || '-' }}
                                    </td>
                                    <td class="text-center">
                                        <div class="form-check form-switch d-flex justify-content-center">
                                            <input
                                                class="form-check-input"
                                                type="checkbox"
                                                :checked="item.is_homepage_featured"
                                                @change="toggleHomepageFeatured(item)"
                                                title="首頁曝光（最多4則）"
                                            />
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <div class="form-check form-switch d-flex justify-content-center">
                                            <input
                                                class="form-check-input"
                                                type="checkbox"
                                                :checked="item.is_pinned"
                                                @change="togglePinned(item)"
                                                title="置頂（最多3則）"
                                            />
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <div class="form-check form-switch d-flex justify-content-center">
                                            <input
                                                class="form-check-input"
                                                type="checkbox"
                                                :checked="item.is_active"
                                                @change="toggleActive(item)"
                                                title="啟用/停用"
                                            />
                                        </div>
                                    </td>
                                    <td class="text-center small">
                                        {{ item.updated_at }}
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group btn-group-sm">
                                            <Link :href="route('admin.news-management.edit', item.id)" class="btn btn-outline-primary" title="編輯">
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
        items:      { type: Object, default: () => ({ data: [], total: 0, current_page: 1, last_page: 1 }) },
        categories: { type: Array, default: () => [] },
        filters:    { type: Object, default: () => ({}) },
    },
    setup(props) {
        const sweetAlert = inject('$sweetAlert');

        const filterForm = reactive({
            keyword:     props.filters?.keyword || '',
            status:      props.filters?.status ?? '',
            category_id: props.filters?.category_id || '',
        });

        const buildQuery = (extra = {}) => {
            const params = {};
            if (filterForm.keyword)          params.keyword     = filterForm.keyword;
            if (filterForm.status !== '')     params.status      = filterForm.status;
            if (filterForm.category_id)      params.category_id = filterForm.category_id;
            return { ...params, ...extra };
        };

        const applyFilter = () => {
            router.get(route('admin.news-management.index'), buildQuery(), {
                preserveState: true,
                preserveScroll: true,
            });
        };

        const resetFilter = () => {
            filterForm.keyword = '';
            filterForm.status = '';
            filterForm.category_id = '';
            router.get(route('admin.news-management.index'), {}, {
                preserveState: true,
                preserveScroll: true,
            });
        };

        const goToPage = (page) => {
            if (page < 1 || page > props.items.last_page) return;
            router.get(route('admin.news-management.index'), buildQuery({ page }), {
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
            const name = item.title || `最新消息 #${item.id}`;
            sweetAlert.confirm(`確定要刪除「${name}」嗎？`, () => {
                router.delete(route('admin.news-management.destroy', item.id), {
                    preserveScroll: true,
                    onSuccess: () => sweetAlert.success({ msg: '刪除成功' }),
                    onError: () => sweetAlert.error({ msg: '刪除失敗' }),
                });
            });
        };

        const toggleActive = (item) => {
            router.put(route('admin.news-management.toggle-active'), { id: item.id }, {
                preserveScroll: true,
                onSuccess: (page) => {
                    const res = page.props.flash?.result;
                    if (res?.status) {
                        sweetAlert.success({ msg: res.msg });
                    } else {
                        sweetAlert.error({ msg: res?.msg || '操作失敗' });
                    }
                },
                onError: () => sweetAlert.error({ msg: '操作失敗' }),
            });
        };

        const toggleHomepageFeatured = (item) => {
            router.put(route('admin.news-management.toggle-homepage-featured'), { id: item.id }, {
                preserveScroll: true,
                onSuccess: (page) => {
                    const res = page.props.flash?.result;
                    if (res?.status) {
                        sweetAlert.success({ msg: res.msg });
                    } else {
                        sweetAlert.error({ msg: res?.msg || '操作失敗' });
                    }
                },
                onError: () => sweetAlert.error({ msg: '操作失敗' }),
            });
        };

        const togglePinned = (item) => {
            router.put(route('admin.news-management.toggle-pinned'), { id: item.id }, {
                preserveScroll: true,
                onSuccess: (page) => {
                    const res = page.props.flash?.result;
                    if (res?.status) {
                        sweetAlert.success({ msg: res.msg });
                    } else {
                        sweetAlert.error({ msg: res?.msg || '操作失敗' });
                    }
                },
                onError: () => sweetAlert.error({ msg: '操作失敗' }),
            });
        };

        return {
            filterForm,
            applyFilter,
            resetFilter,
            goToPage,
            pageRange,
            deleteItem,
            toggleActive,
            toggleHomepageFeatured,
            togglePinned,
        };
    },
    layout: Layout,
};
</script>
