<!-- Modules/PromotionActivity/Vue/Form.vue -->
<!-- 滿額免運設定 - 純設定頁面（無列表） -->
<template>
    <div class="content">
        <BreadcrumbItem />

        <div class="block block-rounded">
            <div class="block-header block-header-default">
                <h3 class="block-title">滿額免運設定</h3>
            </div>

            <div class="block-content block-content-full">
                <form @submit.prevent="submit">

                    <!-- ==================== 基本資訊 ==================== -->
                    <div class="section-divider">
                        <span>基本資訊</span>
                    </div>

                    <div class="row mb-4 align-items-center">
                        <div class="col-lg-6">
                            <div class="row align-items-center">
                                <label class="col-sm-3 col-form-label fw-semibold">
                                    標題
                                    <span class="text-danger">*</span>
                                </label>
                                <div class="col-sm-9">
                                    <input
                                        v-model="form.title"
                                        type="text"
                                        class="form-control"
                                        :class="{ 'is-invalid': form.errors.title }"
                                        placeholder="請輸入活動標題"
                                    />
                                    <div v-if="form.errors.title" class="invalid-feedback">
                                        {{ form.errors.title }}
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="d-flex align-items-center gap-3">
                                <label class="col-form-label fw-semibold mb-0">是否啟用</label>
                                <div class="form-check form-switch">
                                    <input
                                        class="form-check-input"
                                        type="checkbox"
                                        v-model="form.is_active"
                                        role="switch"
                                        style="width: 3rem; height: 1.5rem; cursor: pointer;"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ==================== 活動設定 ==================== -->
                    <div class="section-divider">
                        <span>活動設定</span>
                    </div>

                    <!-- 活動時間 -->
                    <div class="row mb-4 align-items-center">
                        <label class="col-sm-2 col-form-label fw-semibold text-primary">
                            活動時間
                        </label>
                        <div class="col-sm-10">
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

                    <!-- 滿額金額 & 抵扣金額 -->
                    <div class="row mb-4 align-items-center">
                        <label class="col-sm-2 col-form-label fw-semibold text-primary">
                            滿額金額
                        </label>
                        <div class="col-sm-3">
                            <div class="input-group">
                                <button type="button" class="btn btn-outline-secondary" @click="decrementField('min_amount')">
                                    <i class="fa fa-minus"></i>
                                </button>
                                <input
                                    v-model.number="form.min_amount"
                                    type="number"
                                    class="form-control text-center"
                                    :class="{ 'is-invalid': form.errors.min_amount }"
                                    min="0"
                                />
                                <button type="button" class="btn btn-outline-secondary" @click="incrementField('min_amount')">
                                    <i class="fa fa-plus"></i>
                                </button>
                            </div>
                            <div v-if="form.errors.min_amount" class="text-danger small mt-1">{{ form.errors.min_amount }}</div>
                        </div>

                        <label class="col-sm-2 col-form-label fw-semibold text-primary text-end">
                            抵扣金額
                        </label>
                        <div class="col-sm-3">
                            <div class="input-group">
                                <button type="button" class="btn btn-outline-secondary" @click="decrementField('discount_amount')">
                                    <i class="fa fa-minus"></i>
                                </button>
                                <input
                                    v-model.number="form.discount_amount"
                                    type="number"
                                    class="form-control text-center"
                                    :class="{ 'is-invalid': form.errors.discount_amount }"
                                    min="0"
                                />
                                <button type="button" class="btn btn-outline-secondary" @click="incrementField('discount_amount')">
                                    <i class="fa fa-plus"></i>
                                </button>
                            </div>
                            <div v-if="form.errors.discount_amount" class="text-danger small mt-1">{{ form.errors.discount_amount }}</div>
                        </div>
                    </div>

                    <!-- ==================== 條件設定 ==================== -->
                    <div class="section-divider">
                        <span>條件設定</span>
                    </div>

                    <!-- 指定商品分類 -->
                    <div class="row mb-4">
                        <label class="col-sm-2 col-form-label fw-semibold">
                            指定商品分類
                        </label>
                        <div class="col-sm-10">
                            <TreeCheckbox
                                v-model="form.category_ids"
                                :nodes="categories"
                            />
                            <small class="text-muted mt-1 d-block">可勾選多個分類，勾選父分類會自動選取所有子分類</small>
                        </div>
                    </div>

                    <!-- ==================== 送出按鈕 ==================== -->
                    <div class="text-end mt-4">
                        <button
                            type="button"
                            class="btn btn-primary px-4"
                            :disabled="form.processing"
                            @click="submit"
                        >
                            <span v-if="form.processing">
                                <i class="fa fa-spinner fa-spin me-1"></i> 處理中...
                            </span>
                            <span v-else>
                                <i class="fa fa-save me-1"></i> 儲存設定
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script>
import { inject } from "vue";
import { useForm } from "@inertiajs/vue3";
import Layout from "@/Shared/Admin/Layout.vue";
import BreadcrumbItem from "@/Shared/Admin/Partials/BreadcrumbItem.vue";
import DatePicker from "@/Plugin/DatePicker.vue";
import TreeCheckbox from "@/Shared/Admin/Components/TreeCheckbox.vue";
import { FormValidator, useSubmitForm } from '@/utils';

export default {
    components: { BreadcrumbItem, DatePicker, TreeCheckbox },
    props: {
        data:       { type: Object, default: null },
        categories: { type: Array, default: () => [] },
    },
    setup(props) {
        const d = props.data;
        const sweetAlert = inject('$sweetAlert');
        const { submitForm: performSubmit } = useSubmitForm();

        const form = useForm({
            title:           d?.title ?? '',
            is_active:       d?.is_active ?? true,
            start_date:      d?.start_date ?? '',
            end_date:        d?.end_date ?? '',
            min_amount:      d?.min_amount ?? 0,
            discount_amount: d?.discount_amount ?? 0,
            category_ids:    d?.category_ids ?? [],
        });

        const getRules = () => ({
            'title': ['required'],
            'start_date': ['required'],
            'end_date': ['required'],
            'min_amount': ['required', 'integer', 'minValue(0)'],
            'discount_amount': ['required', 'integer', 'minValue(0)'],
        });

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

                const url = route('admin.promotion-activity.update');
                const method = 'put';

                performSubmit({ form, url, method });
            } catch (error) {
                console.error('提交表單時發生錯誤:', error);
                sweetAlert.error({ msg: '系統錯誤，請稍後再試！' });
            }
        };

        return {
            form,
            validator,
            incrementField,
            decrementField,
            submit,
            categories: props.categories,
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
