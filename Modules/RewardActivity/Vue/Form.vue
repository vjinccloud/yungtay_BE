<!-- Modules/RewardActivity/Vue/Form.vue -->
<!-- 回饋活動 - 新增 / 編輯表單 -->
<template>
    <div class="content">
        <BreadcrumbItem />

        <div class="block block-rounded">
            <div class="block-header block-header-default">
                <h3 class="block-title">{{ readonly ? '檢視回饋活動' : (isEdit ? '編輯回饋活動' : '新增回饋活動') }}</h3>
            </div>

            <div v-if="readonly" class="block-content bg-warning-light border-bottom">
                <div class="d-flex align-items-center py-2">
                    <i class="fa fa-lock text-warning me-2"></i>
                    <span class="fw-semibold text-warning">此活動已啟用，僅可檢視，無法編輯。若要結束活動請回列表刪除。</span>
                </div>
            </div>

            <div class="block-content block-content-full">
                <fieldset :disabled="readonly">
                <form @submit.prevent="submit">

                    <!-- ==================== 活動基本設定 ==================== -->
                    <div class="section-divider">
                        <span>活動基本設定</span>
                    </div>

                    <!-- 活動標題 -->
                    <div class="row mb-4 align-items-center">
                        <label class="col-sm-2 col-form-label fw-semibold">
                            活動標題
                            <span class="text-danger">*</span>
                        </label>
                        <div class="col-sm-6">
                            <input
                                v-model="form.title"
                                type="text"
                                class="form-control"
                                :class="{ 'is-invalid': form.errors.title }"
                                placeholder="請輸入活動標題"
                            />
                            <div v-if="form.errors.title" class="invalid-feedback">{{ form.errors.title }}</div>
                        </div>
                    </div>

                    <!-- 活動時間 -->
                    <div class="row mb-4 align-items-center">
                        <label class="col-sm-2 col-form-label fw-semibold">
                            活動時間
                            <span class="text-danger">*</span>
                        </label>
                        <div class="col-sm-8">
                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                <div style="min-width: 200px;">
                                    <DatePicker
                                        v-model="form.start_date"
                                        placeholder="開始日期"
                                        icon-class="fa fa-calendar"
                                        :has-error="!!form.errors.start_date"
                                    />
                                </div>
                                <span class="text-muted fw-semibold">至</span>
                                <div style="min-width: 200px;">
                                    <DatePicker
                                        v-model="form.end_date"
                                        placeholder="結束日期"
                                        icon-class="fa fa-calendar"
                                        :min-date="form.start_date || undefined"
                                        :has-error="!!form.errors.end_date"
                                    />
                                </div>
                            </div>
                            <div v-if="form.errors.start_date" class="text-danger small mt-1">{{ form.errors.start_date }}</div>
                            <div v-if="form.errors.end_date" class="text-danger small mt-1">{{ form.errors.end_date }}</div>
                        </div>
                    </div>

                    <!-- 活動描述 -->
                    <div class="row mb-4">
                        <label class="col-sm-2 col-form-label fw-semibold">
                            活動描述
                            <span class="text-danger">*</span>
                        </label>
                        <div class="col-sm-8">
                            <textarea
                                v-model="form.description"
                                class="form-control"
                                :class="{ 'is-invalid': form.errors.description }"
                                rows="4"
                                placeholder="請輸入活動描述"
                            ></textarea>
                            <div v-if="form.errors.description" class="invalid-feedback">{{ form.errors.description }}</div>
                        </div>
                    </div>

                    <!-- 狀態設置 -->
                    <div class="row mb-4 align-items-center">
                        <label class="col-sm-2 col-form-label fw-semibold">
                            狀態設置
                            <span class="text-danger">*</span>
                        </label>
                        <div class="col-sm-6">
                            <div class="d-flex gap-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" v-model="form.status" value="active" id="statusActive" />
                                    <label class="form-check-label" for="statusActive">啟用</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" v-model="form.status" value="draft" id="statusDraft" />
                                    <label class="form-check-label" for="statusDraft">草稿</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 前台是否顯示 -->
                    <div class="row mb-4 align-items-center">
                        <label class="col-sm-2 col-form-label fw-semibold">
                            前台是否顯示
                        </label>
                        <div class="col-sm-6">
                            <div class="d-flex gap-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" v-model="form.show_on_frontend" :value="true" id="showYes" />
                                    <label class="form-check-label" for="showYes">啟用</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" v-model="form.show_on_frontend" :value="false" id="showNo" />
                                    <label class="form-check-label" for="showNo">關閉</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 優惠代碼 -->
                    <div class="row mb-4 align-items-center">
                        <label class="col-sm-2 col-form-label fw-semibold">
                            優惠代碼
                            <span class="text-danger">*</span>
                        </label>
                        <div class="col-sm-4">
                            <input
                                v-model="form.promo_code"
                                type="text"
                                class="form-control"
                                :class="{ 'is-invalid': form.errors.promo_code }"
                                placeholder="請輸入優惠代碼"
                            />
                            <div v-if="form.errors.promo_code" class="invalid-feedback">{{ form.errors.promo_code }}</div>
                        </div>
                    </div>

                    <!-- ==================== 條件設置 ==================== -->
                    <div class="section-divider">
                        <span>條件設置</span>
                    </div>

                    <!-- 條件類型 -->
                    <div class="row mb-4 align-items-start">
                        <label class="col-sm-2 col-form-label fw-semibold">
                            條件類型
                            <span class="text-danger">*</span>
                        </label>
                        <div class="col-sm-8">
                            <div class="d-flex flex-column gap-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" v-model="form.condition_type" value="all" id="condAll" />
                                    <label class="form-check-label" for="condAll">全部（無條件）</label>
                                </div>
                                <div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" v-model="form.condition_type" value="order_total" id="condOrderTotal" />
                                        <label class="form-check-label" for="condOrderTotal">全單達到</label>
                                    </div>
                                    <div v-if="form.condition_type === 'order_total'" class="ms-4 mt-2">
                                        <div class="input-group" style="max-width: 250px;">
                                            <span class="input-group-text">$</span>
                                            <input
                                                v-model.number="form.condition_order_total"
                                                type="number"
                                                class="form-control"
                                                :class="{ 'is-invalid': form.errors.condition_order_total }"
                                                min="1"
                                                placeholder="請輸入金額"
                                            />
                                        </div>
                                        <div v-if="form.errors.condition_order_total" class="text-danger small mt-1">{{ form.errors.condition_order_total }}</div>
                                    </div>
                                </div>
                                <div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" v-model="form.condition_type" value="category" id="condCategory" />
                                        <label class="form-check-label" for="condCategory">指定分類</label>
                                    </div>
                                    <div v-if="form.condition_type === 'category'" class="ms-4 mt-2">
                                        <TreeCheckbox
                                            v-model="form.condition_category_ids"
                                            :nodes="categories"
                                        />
                                        <div v-if="form.errors.condition_category_ids" class="text-danger small mt-1">{{ form.errors.condition_category_ids }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ==================== 獎勵設定 ==================== -->
                    <div class="section-divider">
                        <span>獎勵設定</span>
                    </div>

                    <!-- 獎勵類型 -->
                    <div class="row mb-4 align-items-center">
                        <label class="col-sm-2 col-form-label fw-semibold">
                            獎勵類型
                            <span class="text-danger">*</span>
                        </label>
                        <div class="col-sm-4">
                            <select v-model="form.reward_type" class="form-select">
                                <option value="shopping_credit">購物金</option>
                                <option value="percentage_discount">百分比折扣</option>
                            </select>
                        </div>
                    </div>

                    <!-- 獎勵數值 -->
                    <div class="row mb-4 align-items-center">
                        <label class="col-sm-2 col-form-label fw-semibold">
                            獎勵數值
                            <span class="text-danger">*</span>
                        </label>
                        <div class="col-sm-3">
                            <div class="input-group">
                                <button type="button" class="btn btn-outline-secondary" @click="decrementField('reward_value')">
                                    <i class="fa fa-minus"></i>
                                </button>
                                <input
                                    v-model.number="form.reward_value"
                                    type="number"
                                    class="form-control text-center"
                                    :class="{ 'is-invalid': form.errors.reward_value }"
                                    min="0"
                                />
                                <button type="button" class="btn btn-outline-secondary" @click="incrementField('reward_value')">
                                    <i class="fa fa-plus"></i>
                                </button>
                                <span v-if="form.reward_type === 'percentage_discount'" class="input-group-text">%</span>
                            </div>
                            <small v-if="form.reward_type === 'percentage_discount'" class="text-muted mt-1 d-block">請輸入 1 ~ 100 之間的數值</small>
                            <div v-if="form.errors.reward_value" class="text-danger small mt-1">{{ form.errors.reward_value }}</div>
                        </div>
                    </div>

                    <!-- 購物金有效期限（僅購物金類型顯示） -->
                    <div v-if="form.reward_type === 'shopping_credit'" class="row mb-4 align-items-start">
                        <label class="col-sm-2 col-form-label fw-semibold">
                            購物金有效期限
                        </label>
                        <div class="col-sm-6">
                            <div class="d-flex flex-column gap-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" v-model="form.credit_expiry_type" value="unlimited" id="expiryUnlimited" />
                                    <label class="form-check-label" for="expiryUnlimited">無限制</label>
                                </div>
                                <div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" v-model="form.credit_expiry_type" value="days" id="expiryDays" />
                                        <label class="form-check-label" for="expiryDays">指定天數</label>
                                    </div>
                                    <div v-if="form.credit_expiry_type === 'days'" class="ms-4 mt-2">
                                        <div class="input-group" style="max-width: 200px;">
                                            <button type="button" class="btn btn-outline-secondary" @click="decrementField('credit_expiry_days')">
                                                <i class="fa fa-minus"></i>
                                            </button>
                                            <input
                                                v-model.number="form.credit_expiry_days"
                                                type="number"
                                                class="form-control text-center"
                                                :class="{ 'is-invalid': form.errors.credit_expiry_days }"
                                                min="1"
                                            />
                                            <button type="button" class="btn btn-outline-secondary" @click="incrementField('credit_expiry_days')">
                                                <i class="fa fa-plus"></i>
                                            </button>
                                            <span class="input-group-text">天</span>
                                        </div>
                                        <div v-if="form.errors.credit_expiry_days" class="text-danger small mt-1">{{ form.errors.credit_expiry_days }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ==================== 活動限制 ==================== -->
                    <div class="section-divider">
                        <span>活動限制</span>
                    </div>

                    <!-- 回饋次數 -->
                    <div class="row mb-4 align-items-start">
                        <label class="col-sm-2 col-form-label fw-semibold">
                            回饋次數
                        </label>
                        <div class="col-sm-8">
                            <div class="d-flex flex-column gap-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" v-model="form.redemption_limit_type" value="unlimited" id="limitUnlimited" />
                                    <label class="form-check-label" for="limitUnlimited">不限</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" v-model="form.redemption_limit_type" value="once_per_member" id="limitOnce" />
                                    <label class="form-check-label" for="limitOnce">每位會員僅限一次</label>
                                </div>
                                <div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" v-model="form.redemption_limit_type" value="site_total" id="limitSiteTotal" />
                                        <label class="form-check-label" for="limitSiteTotal">全站使用次數</label>
                                    </div>
                                    <div v-if="form.redemption_limit_type === 'site_total'" class="ms-4 mt-2">
                                        <div class="input-group" style="max-width: 200px;">
                                            <button type="button" class="btn btn-outline-secondary" @click="decrementField('redemption_site_total')">
                                                <i class="fa fa-minus"></i>
                                            </button>
                                            <input
                                                v-model.number="form.redemption_site_total"
                                                type="number"
                                                class="form-control text-center"
                                                :class="{ 'is-invalid': form.errors.redemption_site_total }"
                                                min="1"
                                            />
                                            <button type="button" class="btn btn-outline-secondary" @click="incrementField('redemption_site_total')">
                                                <i class="fa fa-plus"></i>
                                            </button>
                                            <span class="input-group-text">次</span>
                                        </div>
                                        <div v-if="form.errors.redemption_site_total" class="text-danger small mt-1">{{ form.errors.redemption_site_total }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ==================== 按鈕列 ==================== -->
                    <div class="d-flex justify-content-between mt-4 pt-3 border-top">
                        <Link :href="route('admin.reward-activities.index')" class="btn btn-secondary px-4">
                            <i class="fa fa-arrow-left me-1"></i> 返回
                        </Link>
                        <button
                            v-if="!readonly"
                            type="button"
                            class="btn btn-primary px-4"
                            :disabled="form.processing"
                            @click="submit"
                        >
                            <span v-if="form.processing">
                                <i class="fa fa-spinner fa-spin me-1"></i> 處理中...
                            </span>
                            <span v-else>
                                <i class="fa fa-save me-1"></i> 送出
                            </span>
                        </button>
                    </div>
                </form>
                </fieldset>
            </div>
        </div>
    </div>
</template>

<script>
import { inject } from "vue";
import { useForm, Link } from "@inertiajs/vue3";
import Layout from "@/Shared/Admin/Layout.vue";
import BreadcrumbItem from "@/Shared/Admin/Partials/BreadcrumbItem.vue";
import DatePicker from "@/Plugin/DatePicker.vue";
import TreeCheckbox from "@/Shared/Admin/Components/TreeCheckbox.vue";
import { FormValidator, useSubmitForm } from '@/utils';

export default {
    components: { BreadcrumbItem, Link, DatePicker, TreeCheckbox },
    props: {
        data:       { type: Object, default: null },
        isEdit:     { type: Boolean, default: false },
        readonly:   { type: Boolean, default: false },
        categories: { type: Array, default: () => [] },
    },
    setup(props) {
        const d = props.data;
        const sweetAlert = inject('$sweetAlert');
        const { submitForm: performSubmit } = useSubmitForm();

        const form = useForm({
            title:                  d?.title ?? '',
            start_date:             d?.start_date ?? '',
            end_date:               d?.end_date ?? '',
            description:            d?.description ?? '',
            status:                 d?.status ?? 'active',
            show_on_frontend:       d?.show_on_frontend ?? true,
            promo_code:             d?.promo_code ?? '',
            condition_type:         d?.condition_type ?? 'all',
            condition_order_total:  d?.condition_order_total ?? 0,
            condition_category_ids: d?.condition_category_ids ?? [],
            reward_type:            d?.reward_type ?? 'shopping_credit',
            reward_value:           d?.reward_value ?? 0,
            credit_expiry_type:     d?.credit_expiry_type ?? 'unlimited',
            credit_expiry_days:     d?.credit_expiry_days ?? 1,
            redemption_limit_type:  d?.redemption_limit_type ?? 'unlimited',
            redemption_site_total:  d?.redemption_site_total ?? 0,
        });

        const getRules = () => {
            const rules = {
                'title':       ['required'],
                'start_date':  ['required'],
                'end_date':    ['required'],
                'description': ['required'],
                'promo_code':  ['required'],
                'reward_value': form.reward_type === 'percentage_discount'
                    ? ['required', 'integer', 'minValue(1)', 'maxValue(100)']
                    : ['required', 'integer', 'minValue(0)'],
            };

            if (form.condition_type === 'order_total') {
                rules['condition_order_total'] = ['required', 'integer', 'minValue(1)'];
            }
            if (form.condition_type === 'category') {
                rules['condition_category_ids'] = ['required'];
            }
            if (form.reward_type === 'shopping_credit') {
                rules['credit_expiry_type'] = ['required'];
                if (form.credit_expiry_type === 'days') {
                    rules['credit_expiry_days'] = ['required', 'integer', 'minValue(1)'];
                }
            }
            if (form.redemption_limit_type === 'site_total') {
                rules['redemption_site_total'] = ['required', 'integer', 'minValue(1)'];
            }

            return rules;
        };

        const validator = new FormValidator(form, getRules);

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
                    ? route('admin.reward-activities.update', d.id)
                    : route('admin.reward-activities.store');
                const method = props.isEdit ? 'put' : 'post';

                performSubmit({ form, url, method });
            } catch (error) {
                console.error('提交表單時發生錯誤:', error);
                sweetAlert.error({ msg: '系統錯誤，請稍後再試！' });
            }
        };

        return {
            form,
            validator,
            isEdit: props.isEdit,
            readonly: props.readonly,
            categories: props.categories,
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
