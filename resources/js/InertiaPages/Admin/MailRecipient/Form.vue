<!-- resources/js/InertiaPages/Admin/MailRecipient/Form.vue -->
<template>
    <div class="content">
        <BreadcrumbItem />

        <div class="block block-rounded">
            <div class="block-content block-content-full">

                <form @submit.prevent="submit">
                    
                    <!-- 收件類型 -->
                    <div class="mb-4">
                        <label class="form-label">收件類型 <span class="text-danger">*</span></label>
                        <select
                            v-model="form.type_id"
                            class="form-select"
                            :class="{'is-invalid': form.errors.type_id}"
                            @change="() => validator.singleField('type_id')"
                        >
                            <option v-for="mailType in mailTypes" :key="mailType.id" :value="mailType.id">
                                {{ mailType.name }}
                            </option>
                        </select>
                        <div v-if="form.errors.type_id" class="invalid-feedback">
                            {{ form.errors.type_id }}
                        </div>
                    </div>

                    <!-- 收信人名稱 -->
                    <div class="mb-4">
                        <label class="form-label">收信人名稱 <span class="text-danger">*</span></label>
                        <input
                            v-model="form.name"
                            type="text"
                            class="form-control"
                            :class="{'is-invalid': form.errors.name}"
                            placeholder="請輸入收信人名稱"
                            @blur="validator.singleField('name')"
                            maxlength="50"
                        >
                        <div v-if="form.errors.name" class="invalid-feedback">
                            {{ form.errors.name }}
                        </div>
                    </div>

                    <!-- 電子信箱 -->
                    <div class="mb-4">
                        <label class="form-label">電子信箱 <span class="text-danger">*</span></label>
                        <input
                            v-model="form.email"
                            type="email"
                            class="form-control"
                            :class="{'is-invalid': form.errors.email}"
                            placeholder="請輸入電子信箱"
                            @blur="validator.singleField('email')"
                            maxlength="100"
                        >
                        <div v-if="form.errors.email" class="invalid-feedback">
                            {{ form.errors.email }}
                        </div>
                    </div>

                    <!-- 啟用狀態 -->
                    <div class="mb-4">
                        <div class="form-check form-switch">
                            <input
                                v-model="form.status"
                                class="form-check-input"
                                type="checkbox"
                                id="status"
                                :true-value="true"
                                :false-value="false"
                            >
                            <label class="form-check-label" for="status">
                                啟用狀態
                            </label>
                        </div>
                    </div>
                    

                    <!-- 送出按鈕 -->
                    <div class="text-end">
                        <button
                            type="button"
                            class="btn btn-secondary me-2"
                            @click="back"
                        >
                            回上一頁
                        </button>
                        <button
                            type="button"
                            class="btn btn-primary"
                            :disabled="form.processing"
                            @click="submit"
                        >
                            <span v-if="form.processing">
                                <i class="fa fa-spinner fa-spin me-1"></i>
                                處理中...
                            </span>
                            <span v-else>
                                <i class="fa fa-save me-1"></i>
                                儲存
                            </span>
                        </button>
                    </div>
                </form>

            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, inject } from 'vue'
import { useForm } from '@inertiajs/vue3'
import Layout from "@/Shared/Admin/Layout.vue";
import BreadcrumbItem from "@/Shared/Admin/Partials/BreadcrumbItem.vue";
import { FormValidator, useSubmitForm } from '@/utils';

// 接收 props
const props = defineProps({
    mailRecipient: {
        type: Object,
        default: null
    },
    mailTypes: {
        type: Array,
        default: () => []
    }
});

// 引入 submitForm 方法
const { submitForm: performSubmit } = useSubmitForm();

// 表單資料
const form = useForm({
    type_id: props.mailRecipient?.type_id || (props.mailTypes.length > 0 ? props.mailTypes[0].id : ''),
    name: props.mailRecipient?.name || '',
    email: props.mailRecipient?.email || '',
    status: props.mailRecipient?.status !== undefined ? props.mailRecipient.status : true
});

// 定義驗證規則
const getRules = () => ({
    type_id: ['required'],
    name: ['required', 'string', ['min', 2], ['max', 50]],
    email: ['required', 'email', ['max', 100]],
    status: ['required']
});

// 建立驗證器
const validator = new FormValidator(form, getRules);
const sweetAlert = inject('$sweetAlert');

// 提交表單
const submit = async () => {
    try {
        form.clearErrors();
        
        // 設定提交參數
        const url = props.mailRecipient?.id
            ? route('admin.mail-recipients.update', props.mailRecipient.id)
            : route('admin.mail-recipients.store');
        const method = props.mailRecipient?.id ? 'put' : 'post';

        // 驗證表單
        const hasErrors = await validator.hasErrors();
        if (!hasErrors) {
            performSubmit({ form, url, method });
        } else {
            sweetAlert.error({
                msg: '提交失敗，請檢查是否有欄位錯誤！'
            });
        }
    } catch (error) {
        console.error('提交表單時發生錯誤:', error);
        sweetAlert.error({
            msg: '系統錯誤，請稍後再試！'
        });
    }
};

// 返回上一頁
const back = () => {
    window.history.back();
};
</script>

<script>
export default {
    layout: Layout,
};
</script>
