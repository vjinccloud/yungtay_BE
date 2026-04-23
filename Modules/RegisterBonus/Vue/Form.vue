<!-- Modules/RegisterBonus/Vue/Form.vue -->
<!-- 註冊購物金 - 純設定頁面 -->
<template>
    <div class="content">
        <BreadcrumbItem />

        <div class="block block-rounded">
            <div class="block-content block-content-full">
                <form @submit.prevent="submit">

                    <!-- 啟用狀態 -->
                    <div class="row mb-4 align-items-center">
                        <label class="col-sm-2 col-form-label fw-semibold">啟用狀態</label>
                        <div class="col-sm-10">
                            <div class="d-flex align-items-center gap-2">
                                <span class="text-muted">停用</span>
                                <div class="form-check form-switch mb-0">
                                    <input
                                        class="form-check-input"
                                        type="checkbox"
                                        v-model="form.is_active"
                                        role="switch"
                                        style="width: 3rem; height: 1.5rem; cursor: pointer;"
                                    />
                                </div>
                                <span class="text-muted">啟用</span>
                            </div>
                        </div>
                    </div>

                    <!-- 贈送數值 -->
                    <div class="row mb-4 align-items-center">
                        <label class="col-sm-2 col-form-label fw-semibold text-primary">贈送數值</label>
                        <div class="col-sm-3">
                            <input
                                v-model.number="form.bonus_amount"
                                type="number"
                                class="form-control"
                                :class="{ 'is-invalid': form.errors.bonus_amount }"
                                min="0"
                            />
                            <div v-if="form.errors.bonus_amount" class="invalid-feedback">
                                {{ form.errors.bonus_amount }}
                            </div>
                        </div>
                    </div>

                    <!-- 有效期限 -->
                    <div class="row mb-4 align-items-center">
                        <label class="col-sm-2 col-form-label fw-semibold">有效期限</label>
                        <div class="col-sm-10">
                            <div class="d-flex align-items-center gap-4">
                                <div class="form-check">
                                    <input
                                        class="form-check-input"
                                        type="radio"
                                        id="expiry_unlimited"
                                        value="unlimited"
                                        v-model="form.expiry_type"
                                    />
                                    <label class="form-check-label" for="expiry_unlimited">無限制</label>
                                </div>
                                <div class="form-check">
                                    <input
                                        class="form-check-input"
                                        type="radio"
                                        id="expiry_days"
                                        value="days"
                                        v-model="form.expiry_type"
                                    />
                                    <label class="form-check-label text-primary fw-semibold" for="expiry_days">指定天數</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 有效天數（僅指定天數時顯示） -->
                    <div v-if="form.expiry_type === 'days'" class="row mb-4 align-items-center">
                        <label class="col-sm-2 col-form-label fw-semibold">有效天數</label>
                        <div class="col-sm-3">
                            <div class="d-flex align-items-center gap-2">
                                <div class="input-group" style="max-width: 180px;">
                                    <button type="button" class="btn btn-outline-secondary" @click="decrementDays">
                                        <i class="fa fa-minus"></i>
                                    </button>
                                    <input
                                        v-model.number="form.expiry_days"
                                        type="number"
                                        class="form-control text-center"
                                        :class="{ 'is-invalid': form.errors.expiry_days }"
                                        min="1"
                                    />
                                    <button type="button" class="btn btn-outline-secondary" @click="incrementDays">
                                        <i class="fa fa-plus"></i>
                                    </button>
                                </div>
                                <span class="text-muted">天</span>
                            </div>
                            <div v-if="form.errors.expiry_days" class="text-danger small mt-1">
                                {{ form.errors.expiry_days }}
                            </div>
                        </div>
                    </div>

                    <!-- 儲存按鈕 -->
                    <div class="row">
                        <div class="col-sm-10 offset-sm-2">
                            <button
                                type="button"
                                class="btn btn-primary px-4"
                                :disabled="form.processing"
                                @click="submit"
                            >
                                <span v-if="form.processing">
                                    <i class="fa fa-spinner fa-spin me-1"></i> 處理中...
                                </span>
                                <span v-else>儲存設定</span>
                            </button>
                        </div>
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
import { FormValidator, useSubmitForm } from '@/utils';

export default {
    components: { BreadcrumbItem },
    props: {
        data: { type: Object, default: null },
    },
    setup(props) {
        const d = props.data;
        const sweetAlert = inject('$sweetAlert');
        const { submitForm: performSubmit } = useSubmitForm();

        const form = useForm({
            is_active:    d?.is_active ?? true,
            bonus_amount: d?.bonus_amount ?? 100,
            expiry_type:  d?.expiry_type ?? 'unlimited',
            expiry_days:  d?.expiry_days ?? 1,
        });

        const getRules = () => {
            const rules = {
                'bonus_amount': ['required', 'integer', 'minValue(0)'],
            };
            if (form.expiry_type === 'days') {
                rules['expiry_days'] = ['required', 'integer', 'minValue(1)'];
            }
            return rules;
        };

        const validator = new FormValidator(form, getRules);

        const incrementDays = () => {
            form.expiry_days = (form.expiry_days || 0) + 1;
        };

        const decrementDays = () => {
            if (form.expiry_days > 1) {
                form.expiry_days = form.expiry_days - 1;
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

                const url = route('admin.register-bonus.update');
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
            incrementDays,
            decrementDays,
            submit,
        };
    },
    layout: Layout,
};
</script>
