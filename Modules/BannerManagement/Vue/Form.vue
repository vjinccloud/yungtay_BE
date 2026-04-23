<!-- Modules/BannerManagement/Vue/Form.vue -->
<!-- Banner管理 - 新增 / 編輯表單 -->
<template>
    <div class="content">
        <BreadcrumbItem />

        <div class="block block-rounded">
            <div class="block-header block-header-default">
                <h3 class="block-title">
                    <Link class="btn btn-sm btn-alt-secondary" :href="route('admin.banner-management.index')">
                        <i class="fa fa-arrow-left me-1"></i>
                        返回列表
                    </Link>
                </h3>
            </div>

            <div class="block-content block-content-full">
                <form @submit.prevent="submit">

                    <!-- 桌機版圖片 -->
                    <div class="mb-4">
                        <label class="form-label">
                            桌機版圖片 <small class="text-muted">(建議尺寸: 1920×1067)</small>
                            <span class="text-danger">*</span>
                        </label>
                        <div style="max-width: 50%;">
                            <Slim
                                ref="slimDesktop"
                                :label="'圖片拖移至此，建議尺寸 1920px * 1067px'"
                                :width="1920"
                                :height="1067"
                                :ratio="'1920:1067'"
                                :initialImage="props.data?.desktop_image || ''"
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
                        <label class="form-label">
                            手機版圖片 <small class="text-muted">(建議尺寸: 600×869)</small>
                            <span class="text-danger">*</span>
                        </label>
                        <div style="max-width: 300px;">
                            <Slim
                                ref="slimMobile"
                                :label="'圖片拖移至此，建議尺寸 600px * 869px'"
                                :width="600"
                                :height="869"
                                :ratio="'600:869'"
                                :initialImage="props.data?.mobile_image || ''"
                                @cleared="form.slimMobileCleared = true"
                                :class="{ 'is-invalid': !!form.errors.slimMobile }"
                            />
                            <div v-if="form.errors.slimMobile" class="text-danger mt-1" style="font-size: 0.875rem;">
                                {{ form.errors.slimMobile }}
                            </div>
                        </div>
                    </div>

                    <!-- 標題 -->
                    <div class="mb-4">
                        <label class="form-label">標題</label>
                        <input
                            v-model="form.title"
                            type="text"
                            class="form-control"
                            :class="{ 'is-invalid': form.errors.title }"
                            placeholder="請輸入Banner標題"
                        />
                        <div v-if="form.errors.title" class="invalid-feedback">{{ form.errors.title }}</div>
                    </div>

                    <!-- 連結網址 -->
                    <div class="mb-4">
                        <label class="form-label">連結網址</label>
                        <input
                            v-model="form.url"
                            type="url"
                            class="form-control"
                            :class="{ 'is-invalid': form.errors.url }"
                            placeholder="請輸入連結網址 (例: https://...)"
                        />
                        <div v-if="form.errors.url" class="invalid-feedback">{{ form.errors.url }}</div>
                    </div>

                    <!-- 排序 -->
                    <div class="mb-4">
                        <label class="form-label">排序</label>
                        <input
                            v-model.number="form.sort_order"
                            type="number"
                            min="0"
                            class="form-control"
                            :class="{ 'is-invalid': form.errors.sort_order }"
                            style="max-width: 150px;"
                        />
                        <div v-if="form.errors.sort_order" class="invalid-feedback">{{ form.errors.sort_order }}</div>
                    </div>

                    <!-- 啟用狀態 -->
                    <div class="mb-4">
                        <div class="form-check form-switch">
                            <input
                                v-model="form.is_active"
                                class="form-check-input"
                                type="checkbox"
                                id="is_active"
                            />
                            <label class="form-check-label" for="is_active">啟用狀態</label>
                        </div>
                    </div>

                    <!-- 按鈕區 -->
                    <div class="text-end">
                        <button type="button" class="btn btn-secondary me-2" @click="back">
                            回上一頁
                        </button>
                        <button type="submit" class="btn btn-primary" :disabled="form.processing">
                            <span v-if="form.processing">
                                <i class="fa fa-spinner fa-spin me-1"></i> 處理中...
                            </span>
                            <span v-else>
                                <i class="fa fa-save me-1"></i> 儲存
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onBeforeUnmount, inject } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import Layout from "@/Shared/Admin/Layout.vue";
import BreadcrumbItem from "@/Shared/Admin/Partials/BreadcrumbItem.vue";
import Slim from "@/Plugin/Slim.vue";
import { useSubmitForm, getSlimValue, destroySlim } from '@/utils';

const props = defineProps({
    data:   { type: Object, default: null },
    isEdit: { type: Boolean, default: false },
});

const { submitForm: performSubmit } = useSubmitForm();
const sweetAlert = inject('$sweetAlert');

const slimDesktop = ref(null);
const slimMobile  = ref(null);

const form = useForm({
    title:     props.data?.title || '',
    url:       props.data?.url || '',
    sort_order: props.data?.sort_order ?? 0,
    is_active: props.data?.is_active ?? true,
    slimDesktop: null,
    slimDesktopCleared: false,
    slimMobile: null,
    slimMobileCleared: false,
});

const submit = () => {
    form.clearErrors();

    // 取得 Slim 圖片資料
    form.slimDesktop = getSlimValue(slimDesktop.value);
    form.slimMobile  = getSlimValue(slimMobile.value);

    // 新增時必須有圖片
    if (!props.isEdit && !form.slimDesktop) {
        form.setError('slimDesktop', '請上傳桌機版圖片');
    }
    if (!props.isEdit && !form.slimMobile) {
        form.setError('slimMobile', '請上傳手機版圖片');
    }

    if (Object.keys(form.errors).length > 0) {
        sweetAlert.error({ msg: '請檢查欄位是否正確填寫' });
        return;
    }

    const url = props.isEdit
        ? route('admin.banner-management.update', props.data.id)
        : route('admin.banner-management.store');
    const method = props.isEdit ? 'put' : 'post';

    performSubmit({ form, url, method });
};

const back = () => window.history.back();

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
