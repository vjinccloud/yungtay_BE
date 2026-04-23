<!-- Modules/ProductListing/Vue/Index.vue -->
<!-- 商品上架管理 - 列表頁（含篩選 + 分頁） -->
<template>
    <div class="content">
        <BreadcrumbItem />

        <div class="block block-rounded">
            <div class="block-header block-header-default">
                <h3 class="block-title">商品列表</h3>
                <div class="block-options">
                    <Link :href="route('admin.product-listings.create')" class="btn btn-sm btn-primary">
                        <i class="fa fa-plus me-1"></i> 新增商品
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
                            placeholder="搜尋商品名稱..."
                            @keydown.enter="applyFilter"
                        />
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small mb-1">狀態</label>
                        <select class="form-select form-select-sm" v-model="filterForm.status">
                            <option value="">全部</option>
                            <option value="1">上架</option>
                            <option value="0">下架</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small mb-1">分類</label>
                        <TreeSelect
                            v-model="filterForm.category_id"
                            :nodes="categories"
                            placeholder="全部"
                        />
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small mb-1">熱銷</label>
                        <select class="form-select form-select-sm" v-model="filterForm.is_hot">
                            <option value="">全部</option>
                            <option value="1">是</option>
                            <option value="0">否</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small mb-1">商品類型</label>
                        <select class="form-select form-select-sm" v-model="filterForm.type">
                            <option value="">全部</option>
                            <option value="regular">一般商品</option>
                            <option value="gift">贈品</option>
                        </select>
                    </div>
                    <div class="col-md-1 d-flex gap-2">
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
                <div v-if="products.data.length === 0" class="text-center text-muted py-5">
                    <i class="fa fa-box-open fa-3x mb-3 d-block opacity-50"></i>
                    <p>尚無商品，點擊「新增商品」開始建立</p>
                </div>

                <template v-else>
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-vcenter">
                            <thead>
                                <tr>
                                    <th style="width: 60px;" class="text-center">排序</th>
                                    <th style="width: 80px;" class="text-center">主圖</th>
                                    <th>商品名稱</th>
                                    <th style="width: 90px;" class="text-center">類型</th>
                                    <th style="width: 120px;" class="text-center">分類</th>
                                    <th style="width: 100px;" class="text-center">售價</th>
                                    <th style="width: 120px;" class="text-center">規格組合</th>
                                    <th style="width: 70px;" class="text-center">熱銷</th>
                                    <th style="width: 80px;" class="text-center">狀態</th>
                                    <th style="width: 150px;" class="text-center">操作</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="product in products.data" :key="product.id">
                                    <td class="text-center text-muted">{{ product.seq }}</td>
                                    <td class="text-center">
                                        <img
                                            v-if="product.main_image"
                                            :src="product.main_image"
                                            class="img-thumbnail"
                                            style="width: 50px; height: 50px; object-fit: cover;"
                                        />
                                        <span v-else class="text-muted small">無圖</span>
                                    </td>
                                    <td>
                                        <strong>{{ product.name_primary }}</strong>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge" :class="product.type === 'gift' ? 'bg-warning text-dark' : 'bg-primary'">
                                            {{ product.type === 'gift' ? '贈品' : '一般' }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <template v-if="product.category_name">
                                            <span
                                                v-for="(cat, ci) in product.category_name.split(', ')"
                                                :key="ci"
                                                class="badge bg-primary me-1 mb-1"
                                            >{{ cat }}</span>
                                        </template>
                                        <span v-else class="text-muted small">未分類</span>
                                    </td>
                                    <td class="text-center">${{ product.price }}</td>
                                    <td class="text-center">
                                        <span v-if="product.combo_name" class="badge bg-info">{{ product.combo_name }}</span>
                                        <span v-else class="text-muted small">—</span>
                                    </td>
                                    <td class="text-center">
                                        <i v-if="product.is_hot" class="fa fa-fire text-danger"></i>
                                        <span v-else class="text-muted">—</span>
                                    </td>
                                    <td class="text-center">
                                        <span
                                            class="badge cursor-pointer"
                                            :class="product.status ? 'bg-success' : 'bg-secondary'"
                                            @click="toggleStatus(product.id)"
                                        >
                                            {{ product.status ? '上架' : '下架' }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group btn-group-sm">
                                            <Link :href="route('admin.product-listings.edit', product.id)" class="btn btn-outline-primary" title="編輯">
                                                <i class="fa fa-pencil-alt"></i>
                                            </Link>
                                            <button class="btn btn-outline-danger" title="刪除" @click="deleteProduct(product)">
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
                            顯示第 {{ products.from }}–{{ products.to }} 筆，共 {{ products.total }} 筆
                        </div>
                        <nav v-if="products.last_page > 1">
                            <ul class="pagination pagination-sm mb-0">
                                <li class="page-item" :class="{ disabled: !products.prev_page_url }">
                                    <a class="page-link" href="#" @click.prevent="goToPage(products.current_page - 1)">
                                        <i class="fa fa-chevron-left"></i>
                                    </a>
                                </li>
                                <li
                                    v-for="p in pageRange"
                                    :key="p"
                                    class="page-item"
                                    :class="{ active: p === products.current_page, disabled: p === '...' }"
                                >
                                    <a v-if="p !== '...'" class="page-link" href="#" @click.prevent="goToPage(p)">{{ p }}</a>
                                    <span v-else class="page-link">…</span>
                                </li>
                                <li class="page-item" :class="{ disabled: !products.next_page_url }">
                                    <a class="page-link" href="#" @click.prevent="goToPage(products.current_page + 1)">
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
import { ref, reactive, computed, inject } from "vue";
import { Link, router } from "@inertiajs/vue3";
import Layout from "@/Shared/Admin/Layout.vue";
import BreadcrumbItem from "@/Shared/Admin/Partials/BreadcrumbItem.vue";
import TreeSelect from "@/Shared/Admin/Components/TreeSelect.vue";

export default {
    components: { BreadcrumbItem, Link, TreeSelect },
    props: {
        products:   { type: Object, default: () => ({ data: [], total: 0, current_page: 1, last_page: 1 }) },
        categories: { type: Array, default: () => [] },
        filters:    { type: Object, default: () => ({}) },
    },
    setup(props) {
        const sweetAlert = inject('$sweetAlert');

        // ===== 篩選 =====
        const filterForm = reactive({
            keyword:     props.filters?.keyword || '',
            status:      props.filters?.status ?? '',
            category_id: props.filters?.category_id ?? '',
            is_hot:      props.filters?.is_hot ?? '',
            type:        props.filters?.type ?? '',
        });

        const buildQuery = (extra = {}) => {
            const params = {};
            if (filterForm.keyword)     params.keyword     = filterForm.keyword;
            if (filterForm.status !== '') params.status     = filterForm.status;
            if (filterForm.category_id !== '') params.category_id = filterForm.category_id;
            if (filterForm.is_hot !== '') params.is_hot     = filterForm.is_hot;
            if (filterForm.type !== '')   params.type       = filterForm.type;
            return { ...params, ...extra };
        };

        const applyFilter = () => {
            router.get(route('admin.product-listings.index'), buildQuery(), {
                preserveState: true,
                preserveScroll: true,
            });
        };

        const resetFilter = () => {
            filterForm.keyword = '';
            filterForm.status = '';
            filterForm.category_id = '';
            filterForm.is_hot = '';
            filterForm.type = '';
            router.get(route('admin.product-listings.index'), {}, {
                preserveState: true,
                preserveScroll: true,
            });
        };

        // ===== 分頁 =====
        const goToPage = (page) => {
            if (page < 1 || page > props.products.last_page) return;
            router.get(route('admin.product-listings.index'), buildQuery({ page }), {
                preserveState: true,
                preserveScroll: true,
            });
        };

        const pageRange = computed(() => {
            const total = props.products.last_page;
            const current = props.products.current_page;
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

        // ===== 操作 =====
        const toggleStatus = (id) => {
            axios.put(route('admin.product-listings.toggle-active'), { id })
                .then(res => {
                    if (res.data.status) {
                        sweetAlert.success({ msg: res.data.msg });
                        router.reload({ only: ['products'] });
                    }
                })
                .catch(() => { sweetAlert.error({ msg: '操作失敗' }); });
        };

        const deleteProduct = (product) => {
            const name = product.name_primary || product.name;
            sweetAlert.confirm(`確定要刪除商品「${name}」嗎？`, () => {
                router.delete(route('admin.product-listings.destroy', product.id), {
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
            toggleStatus,
            deleteProduct,
        };
    },
    layout: Layout,
};
</script>

<style scoped>
.cursor-pointer { cursor: pointer; }
</style>
