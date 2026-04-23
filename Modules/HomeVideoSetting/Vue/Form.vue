<!-- Modules/HomeVideoSetting/Vue/Form.vue -->
<!-- 首頁影片管理 - 新增/編輯頁面 -->
<template>
    <div class="content">
        <BreadcrumbItem />

        <div class="block block-rounded">
            <div class="block-header block-header-default">
                <h3 class="block-title">{{ isEdit ? '編輯' : '新增' }}首頁影片</h3>
                <div class="block-options">
                    <Link 
                        :href="route('admin.home-video-settings.index')" 
                        class="btn btn-sm btn-secondary"
                    >
                        <i class="fa fa-arrow-left me-1"></i>
                        返回列表
                    </Link>
                </div>
            </div>

            <div class="block-content block-content-full">
                <form @submit.prevent="submit">

                    <!-- ========== 多語言標題欄位（使用迴圈） ========== -->
                    <div v-for="locale in locales" :key="locale.key" class="mb-4">
                        <label class="form-label">
                            標題（{{ locale.label }}）
                            <span class="text-danger">*</span>
                        </label>
                        <input
                            v-model="form.title[locale.key]"
                            type="text"
                            class="form-control"
                            :class="{ 'is-invalid': form.errors[`title.${locale.key}`] }"
                            :placeholder="locale.placeholder"
                            @blur="validator.singleField(`title.${locale.key}`)"
                        >
                        <div v-if="form.errors[`title.${locale.key}`]" class="invalid-feedback">
                            {{ form.errors[`title.${locale.key}`] }}
                        </div>
                    </div>

                    <!-- ========== 多語言影片欄位（使用迴圈） ========== -->
                    <div v-for="locale in locales" :key="`video_${locale.key}`" class="mb-4">
                        <label class="form-label">
                            影片（{{ locale.label }}版）
                        </label>
                        
                        <!-- 現有影片預覽 -->
                        <div v-if="getVideoData(locale.suffix)?.path && !videoCleared[locale.suffix]" class="mb-3">
                            <div class="bg-dark p-3 rounded">
                                <p class="text-light mb-2">目前影片：{{ getVideoData(locale.suffix)?.name }}</p>
                                <video 
                                    controls 
                                    width="100%" 
                                    style="max-width: 500px;"
                                    :src="getVideoData(locale.suffix)?.path"
                                ></video>
                                <div class="mt-2">
                                    <button 
                                        type="button" 
                                        class="btn btn-sm btn-danger"
                                        @click="clearVideo(locale.suffix)"
                                    >
                                        <i class="fa fa-trash me-1"></i>
                                        移除影片
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- 影片上傳區 -->
                        <div v-show="!getVideoData(locale.suffix)?.path || videoCleared[locale.suffix]">
                            <VideoUploader
                                :ref="el => setVideoRef(locale.suffix, el)"
                                :uploadUrl="route('admin.uploads.tmp.upload')"
                                :limit="1"
                                :extensions="['mp4']"
                                :maxSize="100"
                                @uploaded="(data) => onVideoUploaded(locale.suffix, data)"
                                @removed="() => onVideoRemoved(locale.suffix)"
                            />
                        </div>

                        <div v-if="form.errors[`video_${locale.suffix}_path`]" class="text-danger mt-1" style="font-size: 0.875rem;">
                            {{ form.errors[`video_${locale.suffix}_path`] }}
                        </div>
                    </div>

                    <!-- ========== 排序 ========== -->
                    <div class="mb-4">
                        <label class="form-label">排序</label>
                        <input
                            v-model.number="form.sort"
                            type="number"
                            class="form-control"
                            style="max-width: 150px;"
                            min="0"
                        >
                        <small class="text-muted">數字越小越前面</small>
                    </div>

                    <!-- ========== 啟用狀態 ========== -->
                    <div class="mb-4">
                        <div class="form-check form-switch">
                            <input
                                v-model="form.is_enabled"
                                type="checkbox"
                                class="form-check-input"
                                id="is_enabled"
                            >
                            <label class="form-check-label" for="is_enabled">
                                啟用
                            </label>
                        </div>
                    </div>

                    <!-- ========== 送出按鈕 ========== -->
                    <div class="text-end">
                        <Link 
                            :href="route('admin.home-video-settings.index')" 
                            class="btn btn-secondary me-2"
                        >
                            取消
                        </Link>
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
import { ref, reactive, inject } from 'vue'
import { useForm, Link } from '@inertiajs/vue3'
import Layout from "@/Shared/Admin/Layout.vue";
import BreadcrumbItem from "@/Shared/Admin/Partials/BreadcrumbItem.vue";
import VideoUploader from "@/Plugin/VideoUploader.vue";
import { FormValidator, useSubmitForm } from '@/utils';

// ===== 多語言設定 =====
const locales = [
    { key: 'zh_TW', label: '中文', suffix: 'zh', placeholder: '請輸入中文標題' },
    { key: 'en', label: '英文', suffix: 'en', placeholder: 'Please enter English title' },
];

const props = defineProps({
    data: {
        type: Object,
        default: null
    },
    isEdit: {
        type: Boolean,
        default: false
    }
});

const { submitForm: performSubmit } = useSubmitForm();
const sweetAlert = inject('$sweetAlert');

// 影片上傳器參考
const videoRefs = reactive({});
const setVideoRef = (suffix, el) => {
    videoRefs[suffix] = el;
};

// 影片清除狀態
const videoCleared = reactive({
    zh: false,
    en: false,
});

// 表單資料
const form = useForm({
    title: {
        zh_TW: props.data?.title?.zh_TW || '',
        en: props.data?.title?.en || ''
    },
    // 中文版影片
    video_zh_path: props.data?.video_zh?.path || null,
    video_zh_name: props.data?.video_zh?.name || null,
    video_zh_cleared: false,
    // 英文版影片
    video_en_path: props.data?.video_en?.path || null,
    video_en_name: props.data?.video_en?.name || null,
    video_en_cleared: false,
    // 排序與狀態
    sort: props.data?.sort ?? 0,
    is_enabled: props.data?.is_enabled ?? true,
});

// 取得影片資料
const getVideoData = (suffix) => {
    return {
        path: form[`video_${suffix}_path`],
        name: form[`video_${suffix}_name`],
    };
};

// 影片上傳成功
const onVideoUploaded = (suffix, data) => {
    // 後端回傳格式: { url, video_file_path, original_filename, ... }
    form[`video_${suffix}_path`] = data.video_file_path || data.url;
    form[`video_${suffix}_name`] = data.original_filename || data.filename;
    form[`video_${suffix}_cleared`] = false;
    videoCleared[suffix] = false;
};

// 影片移除
const onVideoRemoved = (suffix) => {
    // 不清除路徑，保留原始資料
};

// 清除影片
const clearVideo = (suffix) => {
    form[`video_${suffix}_path`] = null;
    form[`video_${suffix}_name`] = null;
    form[`video_${suffix}_cleared`] = true;
    videoCleared[suffix] = true;
};

// 驗證規則
const getRules = () => ({
    'title.zh_TW': ['required'],
    'title.en': ['required'],
});

const validator = new FormValidator(form, getRules);

// 提交表單
const submit = async () => {
    try {
        form.clearErrors();

        const url = props.isEdit 
            ? route('admin.home-video-settings.update', props.data.id)
            : route('admin.home-video-settings.store');
        const method = props.isEdit ? 'put' : 'post';

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

defineOptions({
    layout: Layout
});
</script>

<style scoped>
.video-uploader-wrapper {
    max-width: 500px;
}
</style>
