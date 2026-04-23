<!-- Modules/GiftActivitySetting/Vue/Form.vue -->
<!-- 贈品活動設定 - 新增 / 編輯表單 -->
<template>
    <div class="content">
        <BreadcrumbItem />

        <div class="block block-rounded">
            <div class="block-header block-header-default">
                <h3 class="block-title">{{ isEdit ? '編輯贈品活動' : '新增贈品活動' }}</h3>
            </div>

            <div class="block-content block-content-full">
                <form @submit.prevent="submit">

                    <!-- ==================== 基本資訊 ==================== -->
                    <div class="section-divider">
                        <span><i class="fa fa-file-alt me-1"></i>基本資訊</span>
                    </div>

                    <div class="row mb-4">
                        <!-- 活動名稱 -->
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">
                                <span class="text-danger">*</span> 活動名稱
                            </label>
                            <input
                                v-model="form.title"
                                type="text"
                                class="form-control"
                                :class="{ 'is-invalid': form.errors.title }"
                                placeholder="請輸入活動名稱"
                            />
                            <div v-if="form.errors.title" class="invalid-feedback">{{ form.errors.title }}</div>
                        </div>

                        <!-- 活動日期 -->
                        <div class="col-md-5">
                            <label class="form-label fw-semibold">
                                <span class="text-danger">*</span> 活動日期
                            </label>
                            <div class="d-flex align-items-center gap-2">
                                <DatePicker
                                    v-model="form.start_date"
                                    placeholder="開始日期"
                                    icon-class="fa fa-calendar"
                                    :has-error="!!form.errors.start_date"
                                />
                                <span class="text-muted fw-semibold">至</span>
                                <DatePicker
                                    v-model="form.end_date"
                                    placeholder="結束日期"
                                    icon-class="fa fa-calendar"
                                    :min-date="form.start_date || undefined"
                                    :has-error="!!form.errors.end_date"
                                />
                            </div>
                            <div v-if="form.errors.start_date" class="text-danger small mt-1">{{ form.errors.start_date }}</div>
                            <div v-if="form.errors.end_date" class="text-danger small mt-1">{{ form.errors.end_date }}</div>
                        </div>

                        <!-- 狀態 -->
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">狀態</label>
                            <select v-model="form.status" class="form-select">
                                <option value="active">啟用</option>
                                <option value="draft">草稿</option>
                            </select>
                        </div>
                    </div>

                    <!-- ==================== 條件設定 ==================== -->
                    <div class="section-divider">
                        <span><i class="fa fa-filter me-1"></i>條件設定</span>
                    </div>

                    <!-- 條件類型 -->
                    <div class="mb-4">
                        <label class="form-label fw-semibold">條件類型</label>
                        <div class="d-flex gap-4">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" v-model="form.condition_type" value="all" id="condAll" />
                                <label class="form-check-label" for="condAll">全部</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" v-model="form.condition_type" value="order_total" id="condOrderTotal" />
                                <label class="form-check-label" for="condOrderTotal">全單滿多少</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" v-model="form.condition_type" value="category" id="condCategory" />
                                <label class="form-check-label" for="condCategory">商品分類</label>
                            </div>
                        </div>
                    </div>

                    <!-- 指定商品分類（條件類型=商品分類時顯示） -->
                    <div v-if="form.condition_type === 'category'" class="mb-4">
                        <label class="form-label fw-semibold">指定商品分類</label>
                        <TreeCheckbox
                            v-model="form.condition_category_ids"
                            :nodes="categories"
                        />
                        <div v-if="form.errors.condition_category_ids" class="text-danger small mt-1">{{ form.errors.condition_category_ids }}</div>
                    </div>

                    <!-- 滿足金額 -->
                    <div class="mb-4">
                        <label class="form-label fw-semibold">滿足金額</label>
                        <div class="input-group" style="max-width: 300px;">
                            <button type="button" class="btn btn-outline-secondary" @click="decrementField('condition_amount')">
                                <i class="fa fa-minus"></i>
                            </button>
                            <input
                                v-model.number="form.condition_amount"
                                type="number"
                                class="form-control text-center"
                                :class="{ 'is-invalid': form.errors.condition_amount }"
                                min="0"
                            />
                            <button type="button" class="btn btn-outline-secondary" @click="incrementField('condition_amount')">
                                <i class="fa fa-plus"></i>
                            </button>
                        </div>
                        <div v-if="form.errors.condition_amount" class="text-danger small mt-1">{{ form.errors.condition_amount }}</div>
                    </div>

                    <!-- ==================== 贈品選擇 ==================== -->
                    <div class="section-divider">
                        <span><i class="fa fa-gift me-1"></i>贈品選擇</span>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold">選擇可發送的贈品</label>
                        <select
                            class="form-select"
                            :class="{ 'is-invalid': form.errors.gift_products }"
                            @change="addGiftProduct($event)"
                        >
                            <option value="" selected disabled>請選擇贈品</option>
                            <option
                                v-for="gp in availableGiftProducts"
                                :key="gp.id"
                                :value="gp.id"
                            >{{ gp.label }}</option>
                        </select>
                        <div v-if="form.errors.gift_products" class="text-danger small mt-1">{{ form.errors.gift_products }}</div>

                        <!-- 已選贈品列表 -->
                        <div v-if="form.gift_products.length > 0" class="mt-3">
                            <div class="d-flex flex-column gap-2">
                                <div
                                    v-for="(item, idx) in form.gift_products"
                                    :key="idx"
                                    class="d-flex align-items-center gap-2 p-2 bg-light border rounded"
                                >
                                    <i class="fa fa-gift text-warning"></i>
                                    <span class="fw-semibold">{{ getProductLabel(item.product_id) }}</span>

                                    <!-- SKU 選擇 -->
                                    <template v-if="getProductSkus(item.product_id).length > 0">
                                        <span class="text-muted">-</span>
                                        <select
                                            class="form-select form-select-sm"
                                            style="max-width: 220px;"
                                            :value="item.sku_id"
                                            @change="updateSkuId(idx, $event)"
                                        >
                                            <option :value="null">請選擇規格</option>
                                            <option
                                                v-for="sku in getProductSkus(item.product_id)"
                                                :key="sku.id"
                                                :value="sku.id"
                                            >{{ sku.label }}</option>
                                        </select>
                                    </template>

                                    <!-- 數量 -->
                                    <div class="input-group ms-auto" style="max-width: 140px;">
                                        <button type="button" class="btn btn-sm btn-outline-secondary" @click="changeQty(idx, -1)">
                                            <i class="fa fa-minus"></i>
                                        </button>
                                        <input
                                            type="number"
                                            class="form-control form-control-sm text-center"
                                            :value="item.qty"
                                            min="1"
                                            @change="setQty(idx, $event)"
                                        />
                                        <button type="button" class="btn btn-sm btn-outline-secondary" @click="changeQty(idx, 1)">
                                            <i class="fa fa-plus"></i>
                                        </button>
                                    </div>

                                    <button
                                        type="button"
                                        class="btn btn-sm btn-outline-danger"
                                        @click="removeGiftProduct(idx)"
                                    >
                                        <i class="fa fa-times"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ==================== 按鈕列 ==================== -->
                    <div class="d-flex justify-content-end mt-4 pt-3 border-top gap-2">
                        <Link :href="route('admin.gift-activity-settings.index')" class="btn btn-secondary px-4">
                            返回
                        </Link>
                        <button
                            type="button"
                            class="btn btn-primary px-4"
                            :disabled="form.processing"
                            @click="submit"
                        >
                            <span v-if="form.processing">
                                <i class="fa fa-spinner fa-spin me-1"></i> 處理中...
                            </span>
                            <span v-else>送出</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script>
import { computed, inject } from "vue";
import { useForm, Link } from "@inertiajs/vue3";
import Layout from "@/Shared/Admin/Layout.vue";
import BreadcrumbItem from "@/Shared/Admin/Partials/BreadcrumbItem.vue";
import DatePicker from "@/Plugin/DatePicker.vue";
import TreeCheckbox from "@/Shared/Admin/Components/TreeCheckbox.vue";
import { FormValidator, useSubmitForm } from '@/utils';

export default {
    components: { BreadcrumbItem, Link, DatePicker, TreeCheckbox },
    props: {
        data:         { type: Object, default: null },
        isEdit:       { type: Boolean, default: false },
        categories:   { type: Array, default: () => [] },
        giftProducts: { type: Array, default: () => [] },
    },
    setup(props) {
        const d = props.data;
        const sweetAlert = inject('$sweetAlert');
        const { submitForm: performSubmit } = useSubmitForm();

        const form = useForm({
            title:                  d?.title ?? '',
            start_date:             d?.start_date ?? '',
            end_date:               d?.end_date ?? '',
            status:                 d?.status ?? 'draft',
            condition_type:         d?.condition_type ?? 'all',
            condition_amount:       d?.condition_amount ?? 0,
            condition_category_ids: d?.condition_category_ids ?? [],
            gift_products:          d?.gift_products ?? [],
        });

        const getRules = () => {
            const rules = {
                'title':      ['required'],
                'start_date': ['required'],
                'end_date':   ['required'],
                'gift_products': ['required'],
            };

            if (form.condition_type === 'order_total') {
                rules['condition_amount'] = ['required', 'integer', 'minValue(1)'];
            }
            if (form.condition_type === 'category') {
                rules['condition_category_ids'] = ['required'];
            }

            return rules;
        };

        const validator = new FormValidator(form, getRules);

        // 取得商品名稱
        const getProductLabel = (productId) => {
            const gp = props.giftProducts.find(g => g.id === productId);
            return gp ? gp.label : `商品 #${productId}`;
        };

        // 取得商品的 SKU 列表
        const getProductSkus = (productId) => {
            const gp = props.giftProducts.find(g => g.id === productId);
            return gp?.skus ?? [];
        };

        // 可選贈品（排除已選）
        const availableGiftProducts = computed(() => {
            const selectedIds = new Set((form.gift_products || []).map(g => g.product_id));
            return props.giftProducts.filter(gp => !selectedIds.has(gp.id));
        });

        const addGiftProduct = (e) => {
            const id = parseInt(e.target.value, 10);
            if (id && !form.gift_products.some(g => g.product_id === id)) {
                form.gift_products.push({ product_id: id, sku_id: null, qty: 1 });
            }
            e.target.value = '';
        };

        const updateSkuId = (idx, e) => {
            const val = e.target.value;
            form.gift_products[idx].sku_id = val ? parseInt(val, 10) : null;
        };

        const removeGiftProduct = (idx) => {
            form.gift_products.splice(idx, 1);
        };

        const changeQty = (idx, delta) => {
            const newVal = (form.gift_products[idx].qty || 1) + delta;
            form.gift_products[idx].qty = Math.max(1, newVal);
        };

        const setQty = (idx, e) => {
            const val = parseInt(e.target.value, 10);
            form.gift_products[idx].qty = val >= 1 ? val : 1;
        };

        const incrementField = (field) => {
            form[field] = (form[field] || 0) + 1;
        };

        const decrementField = (field) => {
            if (form[field] > 0) {
                form[field] = form[field] - 1;
            }
        };

        const submit = async () => {
            try {
                form.clearErrors();

                const hasErrors = await validator.hasErrors();
                if (hasErrors) {
                    sweetAlert.error({ msg: '提交失敗，請檢查是否有欄位錯誤！' });
                    return;
                }

                const url = props.isEdit
                    ? route('admin.gift-activity-settings.update', d.id)
                    : route('admin.gift-activity-settings.store');
                const method = props.isEdit ? 'put' : 'post';

                performSubmit({ form, url, method });
            } catch (error) {
                console.error('提交表單時發生錯誤:', error);
                sweetAlert.error({ msg: '系統錯誤，請稍後再試！' });
            }
        };

        return {
            form,
            isEdit: props.isEdit,
            categories: props.categories,
            getProductLabel,
            getProductSkus,
            availableGiftProducts,
            addGiftProduct,
            updateSkuId,
            removeGiftProduct,
            changeQty,
            setQty,
            incrementField,
            decrementField,
            submit,
        };
    },
    layout: Layout,
};
</script>

<style scoped>
.section-divider {
    position: relative;
    text-align: center;
    margin: 2rem 0 1.5rem;
    border-top: 1px solid #dee2e6;
}
.section-divider span {
    position: relative;
    top: -0.75em;
    background: #fff;
    padding: 0 1rem;
    font-size: 1rem;
    font-weight: 600;
    color: #6c757d;
}
</style>
