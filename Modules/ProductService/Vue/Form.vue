<!-- Modules/ProductService/Vue/Form.vue -->
<!-- 產品及服務 - 新增/編輯頁面 -->
<template>
    <div class="content">
        <BreadcrumbItem />

        <div class="block block-rounded">
            <div class="block-header block-header-default">
                <h3 class="block-title">{{ isEdit ? '編輯' : '新增' }}產品及服務</h3>
                <div class="block-options">
                    <Link 
                        :href="route('admin.product-services.index')" 
                        class="btn btn-sm btn-secondary"
                    >
                        <i class="fa fa-arrow-left me-1"></i>
                        返回列表
                    </Link>
                </div>
            </div>

            <div class="block-content block-content-full">
                <form @submit.prevent="submit">

                    <!-- ========== 中文名稱 ========== -->
                    <div class="mb-4">
                        <label class="form-label">
                            名稱（中文）
                            <span class="text-danger">*</span>
                        </label>
                        <input
                            v-model="form.name.zh_TW"
                            type="text"
                            class="form-control"
                            :class="{ 'is-invalid': form.errors['name.zh_TW'] }"
                            placeholder="請輸入中文名稱"
                        >
                        <div v-if="form.errors['name.zh_TW']" class="invalid-feedback">
                            {{ form.errors['name.zh_TW'] }}
                        </div>
                    </div>

                    <!-- ========== 英文名稱 ========== -->
                    <div class="mb-4">
                        <label class="form-label">
                            名稱（英文）
                            <span class="text-danger">*</span>
                        </label>
                        <input
                            v-model="form.name.en"
                            type="text"
                            class="form-control"
                            :class="{ 'is-invalid': form.errors['name.en'] }"
                            placeholder="Please enter English name"
                        >
                        <div v-if="form.errors['name.en']" class="invalid-feedback">
                            {{ form.errors['name.en'] }}
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
                            :href="route('admin.product-services.index')" 
                            class="btn btn-secondary me-2"
                        >
                            取消
                        </Link>
                        <button
                            type="submit"
                            class="btn btn-primary"
                            :disabled="form.processing"
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
import { inject } from 'vue'
import { useForm, Link } from '@inertiajs/vue3'
import Layout from "@/Shared/Admin/Layout.vue";
import BreadcrumbItem from "@/Shared/Admin/Partials/BreadcrumbItem.vue";

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

const sweetAlert = inject('$sweetAlert');

// 表單資料
const form = useForm({
    name: {
        zh_TW: props.data?.name?.zh_TW || '',
        en: props.data?.name?.en || ''
    },
    sort: props.data?.sort ?? 0,
    is_enabled: props.data?.is_enabled ?? true,
});

// 提交表單
const submit = () => {
    const url = props.isEdit 
        ? route('admin.product-services.update', props.data.id)
        : route('admin.product-services.store');
    const method = props.isEdit ? 'put' : 'post';

    form[method](url, {
        onSuccess: () => {
            sweetAlert.success({ msg: props.isEdit ? '更新成功' : '新增成功' });
        },
        onError: () => {
            sweetAlert.error({ msg: '儲存失敗，請檢查欄位' });
        }
    });
};

defineOptions({
    layout: Layout
});
</script>
