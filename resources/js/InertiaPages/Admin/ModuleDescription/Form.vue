<!-- resources/js/InertiaPages/Admin/News/Form.vue -->
<template>
    <div class="content">
        <BreadcrumbItem />

        <div class="block block-rounded">
            <div class="block-header block-header-default">
                <h3 class="block-title">
                    <Link class="btn btn-sm btn-alt-secondary" :href="route('admin.module-descriptions')">
                        <i class="fa fa-arrow-left me-1"></i>
                        返回列表
                    </Link>
                </h3>
            </div>
            
            <div class="block-content block-content-full">

                <form @submit.prevent="submit">
                    
                    <!-- 模組類型 -->
                    <div class="mb-4">
                        <label class="form-label">模組類型 <span class="text-danger">*</span></label>
                        <select class="form-control" 
                               v-model="form.module_key"
                               :class="{ 'is-invalid': form.errors.module_key }"
                               @blur="validator.singleField('module_key')">
                            <option value="">請選擇模組類型</option>
                            <option v-for="(label, key) in moduleKeys" :key="key" :value="key">
                                {{ label }}
                            </option>
                        </select>
                        <div v-if="form.errors.module_key" class="invalid-feedback">
                            {{ form.errors.module_key }}
                        </div>
                        <small class="form-text text-muted">選擇此模組描述要應用的頁面</small>
                    </div>
                    

                    <!-- SEO描述（僅中文） -->
                    <div class="mb-4">
                        <label class="form-label">SEO描述 <span class="text-danger">*</span></label>
                        <div class="mb-3">
                            <textarea class="form-control" rows="4"
                                      v-model="form.meta_description.zh_TW"
                                      :class="{ 'is-invalid': form.errors.meta_description?.zh_TW }"
                                      placeholder="請輸入SEO描述"
                                      @blur="validator.singleField('meta_description.zh_TW')">
                            </textarea>
                            <div v-if="form.errors.meta_description?.zh_TW" class="invalid-feedback">
                                {{ form.errors.meta_description?.zh_TW }}
                            </div>
                        </div>
                        <small class="form-text text-muted">此描述會顯示在前台頁面的SEO meta標籤</small>
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
import { useForm, Link } from '@inertiajs/vue3'
import Layout from "@/Shared/Admin/Layout.vue";
import BreadcrumbItem from "@/Shared/Admin/Partials/BreadcrumbItem.vue";
import { FormValidator, useSubmitForm } from '@/utils';

// 接收 props
const props = defineProps({
    moduleDescription: {
        type: Object,
        default: null
    },
    moduleKeys: {
        type: Object,
        default: () => ({})
    }
});

// 引入 submitForm 方法
const { submitForm: performSubmit } = useSubmitForm();

// 表單資料
const form = useForm({
    module_key: props.moduleDescription?.module_key || '',
    meta_description: {
        zh_TW: props.moduleDescription?.meta_description?.zh_TW || ''
    }
});

// 定義驗證規則
const getRules = () => ({
    module_key: ['required'],
    meta_description: {
        zh_TW: ['required']
    }
});

// 建立驗證器
const validator = new FormValidator(form, getRules);
const sweetAlert = inject('$sweetAlert');

// 提交表單
const submit = async () => {
    try {
        form.clearErrors();
        
        // 設定提交參數
        const url = props.moduleDescription?.id
            ? route('admin.module-descriptions.update', props.moduleDescription.id)
            : route('admin.module-descriptions.store');
        const method = props.moduleDescription?.id ? 'put' : 'post';

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
