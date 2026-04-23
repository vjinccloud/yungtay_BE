<!-- Modules/HomeImageSetting/Vue/Form.vue -->
<!-- 首頁圖片設定 - 單一設定頁面 -->
<template>
    <div class="content">
        <BreadcrumbItem />

        <div class="block block-rounded">
            <div class="block-header block-header-default">
                <h3 class="block-title">首頁圖片設定</h3>
            </div>

            <div class="block-content block-content-full">
                <form @submit.prevent="submit">

                    <!-- ========== 中文標題 ========== -->
                    <div class="mb-4">
                        <label class="form-label">
                            標題（中文）
                            <span class="text-danger">*</span>
                        </label>
                        <input
                            v-model="form.title.zh_TW"
                            type="text"
                            class="form-control"
                            :class="{ 'is-invalid': form.errors['title.zh_TW'] }"
                            placeholder="請輸入中文標題"
                            @blur="validator.singleField('title.zh_TW')"
                        >
                        <div v-if="form.errors['title.zh_TW']" class="invalid-feedback">
                            {{ form.errors['title.zh_TW'] }}
                        </div>
                    </div>

                    <!-- ========== 英文標題 ========== -->
                    <div class="mb-4">
                        <label class="form-label">
                            標題（英文）
                            <span class="text-danger">*</span>
                        </label>
                        <input
                            v-model="form.title.en"
                            type="text"
                            class="form-control"
                            :class="{ 'is-invalid': form.errors['title.en'] }"
                            placeholder="Please enter English title"
                            @blur="validator.singleField('title.en')"
                        >
                        <div v-if="form.errors['title.en']" class="invalid-feedback">
                            {{ form.errors['title.en'] }}
                        </div>
                    </div>

                    <!-- ========== 中文版圖片 ========== -->
                    <div class="mb-4">
                        <label class="form-label">
                            圖片（中文版）
                            <small class="text-muted">(建議尺寸: 1328*512)</small>
                            <span class="text-danger">*</span>
                        </label>
                        <div style="max-width: 400px;">
                            <Slim
                                ref="slimImageZh"
                                :label="'圖片拖移至此，建議尺寸 1328px * 512px'"
                                :width="1328"
                                :height="512"
                                :ratio="'1328:512'"
                                :initialImage="props.data?.image_zh || ''"
                                @cleared="form.slimImageZhCleared = true"
                                :class="{ 'is-invalid': !!form.errors.slimImageZh }"
                            />
                            <div v-if="form.errors.slimImageZh" class="text-danger mt-1" style="font-size: 0.875rem;">
                                {{ form.errors.slimImageZh }}
                            </div>
                        </div>
                    </div>

                    <!-- ========== 英文版圖片 ========== -->
                    <div class="mb-4">
                        <label class="form-label">
                            圖片（英文版）
                            <small class="text-muted">(建議尺寸: 1328*512)</small>
                            <span class="text-danger">*</span>
                        </label>
                        <div style="max-width: 400px;">
                            <Slim
                                ref="slimImageEn"
                                :label="'圖片拖移至此，建議尺寸 1328px * 512px'"
                                :width="1328"
                                :height="512"
                                :ratio="'1328:512'"
                                :initialImage="props.data?.image_en || ''"
                                @cleared="form.slimImageEnCleared = true"
                                :class="{ 'is-invalid': !!form.errors.slimImageEn }"
                            />
                            <div v-if="form.errors.slimImageEn" class="text-danger mt-1" style="font-size: 0.875rem;">
                                {{ form.errors.slimImageEn }}
                            </div>
                        </div>
                    </div>

                    <!-- ========== 送出按鈕 ========== -->
                    <div class="text-end">
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
import { ref, inject, onBeforeUnmount } from 'vue'
import { useForm } from '@inertiajs/vue3'
import Layout from "@/Shared/Admin/Layout.vue";
import BreadcrumbItem from "@/Shared/Admin/Partials/BreadcrumbItem.vue";
import Slim from "@/Plugin/Slim.vue";
import { FormValidator, useSubmitForm, getSlimValue, destroySlim } from '@/utils';

const props = defineProps({
    data: {
        type: Object,
        default: null
    }
});

const { submitForm: performSubmit } = useSubmitForm();

// Slim 參考
const slimImageZh = ref(null);
const slimImageEn = ref(null);

// 表單資料
const form = useForm({
    title: {
        zh_TW: props.data?.title?.zh_TW || '',
        en: props.data?.title?.en || ''
    },
    slimImageZh: null,
    slimImageZhCleared: false,
    slimImageEn: null,
    slimImageEnCleared: false,
});

// 驗證規則
const getRules = () => ({
    'title.zh_TW': ['required'],
    'title.en': ['required'],
});

const validator = new FormValidator(form, getRules);
const sweetAlert = inject('$sweetAlert');

// 提交表單
const submit = async () => {
    try {
        form.clearErrors();

        // 取得 Slim 圖片資料
        form.slimImageZh = getSlimValue(slimImageZh.value);
        form.slimImageEn = getSlimValue(slimImageEn.value);

        const url = route('admin.home-image-setting.update');
        const method = 'put';

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

// 組件卸載時清理
onBeforeUnmount(() => {
    destroySlim(slimImageZh.value);
    destroySlim(slimImageEn.value);
});
</script>

<script>
export default {
    layout: Layout,
};
</script>
