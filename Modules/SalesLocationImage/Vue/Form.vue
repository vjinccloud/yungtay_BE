<!-- Modules/SalesLocationImage/Vue/Form.vue -->
<!-- 銷售據點圖片管理 - 單一設定頁面 -->
<template>
    <div class="content">
        <BreadcrumbItem />

        <div class="block block-rounded">
            <div class="block-header block-header-default">
                <h3 class="block-title">銷售據點圖片管理</h3>
            </div>

            <div class="block-content block-content-full">
                <form @submit.prevent="submit">

                    <!-- ========== 中文版圖片 ========== -->
                    <div class="mb-4">
                        <label class="form-label">
                            圖片（中文版）
                            <small class="text-muted">(建議尺寸: 1920*1080)</small>
                        </label>
                        <div style="max-width: 400px;">
                            <Slim
                                ref="slimImageZh"
                                :label="'圖片拖移至此，建議尺寸 1920px * 1080px'"
                                :width="1920"
                                :height="1080"
                                :ratio="'1920:1080'"
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
                            <small class="text-muted">(建議尺寸: 1920*1080)</small>
                        </label>
                        <div style="max-width: 400px;">
                            <Slim
                                ref="slimImageEn"
                                :label="'圖片拖移至此，建議尺寸 1920px * 1080px'"
                                :width="1920"
                                :height="1080"
                                :ratio="'1920:1080'"
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
import { getSlimValue, destroySlim, useSubmitForm } from '@/utils';

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
    image_zh: null,
    image_en: null,
    slimImageZhCleared: false,
    slimImageEnCleared: false,
});

const sweetAlert = inject('$sweetAlert');

// 提交表單
const submit = async () => {
    try {
        form.clearErrors();

        // 取得 Slim 圖片資料
        form.image_zh = getSlimValue(slimImageZh.value);
        form.image_en = getSlimValue(slimImageEn.value);

        const url = route('admin.sales-location-image.update');
        const method = 'put';

        performSubmit({ form, url, method });
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
import Layout from "@/Shared/Admin/Layout.vue";
export default {
    layout: Layout,
};
</script>
