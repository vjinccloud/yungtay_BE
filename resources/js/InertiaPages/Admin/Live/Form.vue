<!-- resources/js/InertiaPages/Admin/Live/Form.vue -->
<template>
    <div class="content">
        <BreadcrumbItem />

        <div class="block block-rounded">
            <div class="block-header block-header-default">
                <h3 class="block-title">
                    <Link class="btn btn-sm btn-alt-secondary" :href="route('admin.lives')">
                        <i class="fa fa-arrow-left me-1"></i>
                        返回列表
                    </Link>
                </h3>
            </div>
            
            <div class="block-content block-content-full">

                <form @submit.prevent="submit">
                    <!-- 標題欄位（支援多語系） -->
                    <div class="mb-4">
                        <label class="form-label">標題 <span class="text-danger">*</span></label>
                        <div class="mb-3">
                            <input
                                v-model="form.title.zh_TW"
                                type="text"
                                class="form-control"
                                :class="{'is-invalid': form.errors.title?.zh_TW}"
                                placeholder="中文標題"
                                @blur="validator.singleField('title.zh_TW')"
                                required
                            >
                            <div v-if="form.errors.title?.zh_TW" class="invalid-feedback">
                                {{ form.errors.title?.zh_TW }}
                            </div>
                        </div>
                        <div>
                            <input
                                v-model="form.title.en"
                                type="text"
                                class="form-control"
                                :class="{'is-invalid': form.errors.title?.en}"
                                placeholder="English Title"
                                @blur="validator.singleField('title.en')"
                            >
                            <div v-if="form.errors.title?.en" class="invalid-feedback">
                                {{ form.errors.title?.en }}
                            </div>
                        </div>
                    </div>

                    <!-- YouTube 直播連結 -->
                    <div class="mb-4">
                        <label class="form-label">YouTube 直播連結</label>
                        <input
                            v-model="form.youtube_url"
                            type="url"
                            class="form-control"
                            :class="{'is-invalid': form.errors.youtube_url}"
                            placeholder="https://www.youtube.com/watch?v=..."
                            @blur="validator.singleField('youtube_url')"
                        >
                        <div v-if="form.errors.youtube_url" class="invalid-feedback">
                            {{ form.errors.youtube_url }}
                        </div>
                    </div>


                    <!-- 啟用狀態 -->
                    <div class="mb-4">
                        <div class="form-check form-switch">
                            <input
                                v-model="form.is_active"
                                class="form-check-input"
                                type="checkbox"
                                id="is_active"
                            >
                            <label class="form-check-label" for="is_active">
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
import { useForm, Link } from '@inertiajs/vue3'
import Layout from "@/Shared/Admin/Layout.vue";
import BreadcrumbItem from "@/Shared/Admin/Partials/BreadcrumbItem.vue";
import { FormValidator, useSubmitForm } from '@/utils';

// 接收 props
const props = defineProps({
    live: {
        type: Object,
        default: null
    }
});

// 引入 submitForm 方法
const { submitForm: performSubmit } = useSubmitForm();

// 表單資料
const form = useForm({
    // 多語系欄位
    title: {
        zh_TW: props.live?.title?.zh_TW || '',
        en: props.live?.title?.en || ''
    },
    youtube_url: props.live?.youtube_url || '',
    is_active: props.live?.is_active ?? true,
});

// 定義驗證規則
const getRules = () => ({
    title: {
        zh_TW: ['required', 'string', ['max', 255]],
        en: ['required','string', ['max', 255]],
    },
    youtube_url: ['required', 'url'],
});

// 建立驗證器
const validator = new FormValidator(form, getRules);
const sweetAlert = inject('$sweetAlert');

// 提交表單
const submit = async () => {
    try {
        form.clearErrors();
        
        // 設定提交參數
        const url = props.live?.id
            ? route('admin.lives.update', props.live.id)
            : route('admin.lives.store');
        const method = props.live?.id ? 'put' : 'post';

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
