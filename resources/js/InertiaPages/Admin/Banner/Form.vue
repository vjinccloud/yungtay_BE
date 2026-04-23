<!-- resources/js/InertiaPages/Admin/Banner/Form.vue -->
<template>
    <div class="content">
        <BreadcrumbItem />

        <div class="block block-rounded">
            <div class="block-header block-header-default">
                <h3 class="block-title">
                    <Link class="btn btn-sm btn-alt-secondary" :href="route('admin.banners')">
                        <i class="fa fa-arrow-left me-1"></i>
                        返回列表
                    </Link>
                </h3>
            </div>

            <div class="block-content block-content-full">

                <form @submit.prevent="submit">

                    <!-- 桌機版圖片 -->
                    <div class="mb-4">
                        <label class="form-label">桌機版圖片 <small class="text-muted">(建議尺寸: 1920×1067)</small> <span class="text-danger">*</span></label>
                        <div style="max-width: 50%;">
                            <Slim
                                ref="slimDesktop"
                                :label="'圖片拖移至此，建議尺寸 1920px * 1067px'"
                                :width="1920"
                                :height="1067"
                                :ratio="'1920:1067'"
                                :initialImage="props.banner?.desktop_image || ''"
                                @cleared="form.slimDesktopCleared = true"
                                :class="{ 'is-invalid': !!form.errors.slimDesktop }"
                            />
                            <div v-if="form.errors.slimDesktop" class="text-danger mt-1" style="font-size: 0.875rem;">
                                {{ form.errors.slimDesktop }}
                            </div>
                        </div>
                    </div>

                    <!-- 手機版圖片 -->
                    <div class="mb-4">
                        <label class="form-label">手機版圖片 <small class="text-muted">(建議尺寸: 600×869)</small> <span class="text-danger">*</span></label>
                        <div style="max-width: 300px;">
                            <Slim
                                ref="slimMobile"
                                :label="'圖片拖移至此，建議尺寸 600px * 869px'"
                                :width="600"
                                :height="869"
                                :ratio="'600:869'"
                                :initialImage="props.banner?.mobile_image || ''"
                                @cleared="form.slimMobileCleared = true"
                                :class="{ 'is-invalid': !!form.errors.slimMobile }"
                            />
                            <div v-if="form.errors.slimMobile" class="text-danger mt-1" style="font-size: 0.875rem;">
                                {{ form.errors.slimMobile }}
                            </div>
                        </div>
                    </div>

                    <!-- 標題欄位（暫時隱藏）
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
                            >
                            <div v-if="form.errors.title?.zh_TW" class="invalid-feedback">
                                {{ form.errors.title?.zh_TW }}
                            </div>
                        </div>
                    </div>
                    -->

                    <!-- 簡述1（蓋字區塊）（暫時隱藏）
                    <div class="mb-4">
                        <label class="form-label">簡述1（蓋字區塊） <span class="text-danger">*</span></label>
                        <div class="mb-3">
                            <input
                                v-model="form.subtitle_1.zh_TW"
                                type="text"
                                class="form-control"
                                :class="{'is-invalid': form.errors.subtitle_1?.zh_TW}"
                                placeholder="中文簡述1"
                                @blur="validator.singleField('subtitle_1.zh_TW')"
                            >
                            <div v-if="form.errors.subtitle_1?.zh_TW" class="invalid-feedback">
                                {{ form.errors.subtitle_1?.zh_TW }}
                            </div>
                        </div>
                    </div>
                    -->

                    <!-- 簡述2（暫時隱藏）
                    <div class="mb-4">
                        <label class="form-label">簡述2 <span class="text-danger">*</span></label>
                        <div class="mb-3">
                            <textarea
                                v-model="form.subtitle_2.zh_TW"
                                class="form-control"
                                :class="{'is-invalid': form.errors.subtitle_2?.zh_TW}"
                                placeholder="中文簡述2"
                                rows="3"
                                @blur="validator.singleField('subtitle_2.zh_TW')"
                            ></textarea>
                            <div v-if="form.errors.subtitle_2?.zh_TW" class="invalid-feedback">
                                {{ form.errors.subtitle_2?.zh_TW }}
                            </div>
                        </div>
                    </div>
                    -->

                    <!-- 連結網址（暫時隱藏）
                    <div class="mb-4">
                        <label class="form-label">連結網址</label>
                        <input
                            v-model="form.url"
                            type="url"
                            class="form-control"
                            :class="{'is-invalid': form.errors.url}"
                            placeholder="請輸入連結網址"
                            @blur="validator.singleField('url')"
                        >
                        <div v-if="form.errors.url" class="invalid-feedback">
                            {{ form.errors.url }}
                        </div>
                    </div>
                    -->

                    <!-- 標籤欄位（暫時隱藏）
                    <div class="mb-4">
                        <div class="mb-3">
                            <label class="form-label">標籤</label>
                            <EditableTags
                                v-model="form.tags.zh_TW"
                                :placeholder="'輸入標籤後按 Enter'"
                                :emptyText="'點擊添加標籤'"
                                :separator="','"
                                :trimValue="true"
                                :required="false"
                                @update="() => validator.singleField('tags.zh_TW')"
                            />
                            <div v-if="form.errors.tags?.zh_TW" class="text-danger mt-1" style="font-size: 0.875rem;">
                                {{ form.errors.tags?.zh_TW }}
                            </div>
                        </div>
                    </div>
                    -->

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
import { ref, inject, onBeforeUnmount, nextTick } from 'vue'
import { useForm, Link } from '@inertiajs/vue3'
import Layout from "@/Shared/Admin/Layout.vue";
import BreadcrumbItem from "@/Shared/Admin/Partials/BreadcrumbItem.vue";
import Slim from "@/Plugin/Slim.vue";
import EditableTags from "@/Plugin/EditableTags.vue";
import { FormValidator, useSubmitForm, getSlimValue, destroySlim } from '@/utils';

// 接收 props
const props = defineProps({
    banner: {
        type: Object,
        default: null
    }
});

// 引入 submitForm 方法
const { submitForm: performSubmit } = useSubmitForm();

// Slim 參考
const slimDesktop = ref(null);
const slimMobile = ref(null);

// 表單資料
const form = useForm({
    // 多語言欄位（僅中文）
    title: {
        zh_TW: props.banner?.title?.zh_TW || ''
    },
    subtitle_1: {
        zh_TW: props.banner?.subtitle_1?.zh_TW || ''
    },
    subtitle_2: {
        zh_TW: props.banner?.subtitle_2?.zh_TW || ''
    },
    url: props.banner?.url || '',
    tags: {
        zh_TW: props.banner?.tags?.zh_TW || ''
    },

    // 系統欄位
    is_active: props.banner?.is_active ?? true,

    // Slim 圖片資料
    slimDesktop: null,
    slimDesktopCleared: false,
    slimMobile: null,
    slimMobileCleared: false,
});

// 定義驗證規則（標題、簡述、連結、標籤欄位已隱藏，不需驗證）
const getRules = () => ({
    slimDesktop: props.banner?.id ? [] : ['required'],
    slimMobile: props.banner?.id ? [] : ['required']
});

// 建立驗證器
const validator = new FormValidator(form, getRules);
const sweetAlert = inject('$sweetAlert');

// 提交表單
const submit = async () => {
    try {
        form.clearErrors();

        // 取得 Slim 圖片資料
        form.slimDesktop = getSlimValue(slimDesktop.value);
        form.slimMobile = getSlimValue(slimMobile.value);

        // 設定提交參數
        const url = props.banner?.id
            ? route('admin.banners.update', props.banner.id)
            : route('admin.banners.store');
        const method = props.banner?.id ? 'put' : 'post';

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

// 組件卸載時清理
onBeforeUnmount(() => {
    destroySlim(slimDesktop.value);
    destroySlim(slimMobile.value);
});
</script>

<script>
export default {
    layout: Layout,
};
</script>
