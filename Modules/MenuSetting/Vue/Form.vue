<!-- Modules/MenuSetting/Vue/Form.vue -->
<!-- 選單管理 - 新增/編輯頁面 -->
<template>
    <div class="content">
        <BreadcrumbItem />

        <div class="block block-rounded">
            <div class="block-header block-header-default">
                <h3 class="block-title">{{ isEdit ? '編輯' : '新增' }}選單</h3>
                <div class="block-options">
                    <Link 
                        :href="route('admin.menu-settings.index')" 
                        class="btn btn-sm btn-secondary"
                    >
                        <i class="fa fa-arrow-left me-1"></i>
                        返回列表
                    </Link>
                </div>
            </div>

            <div class="block-content block-content-full">
                <form @submit.prevent="submit">

                    <!-- ========== 選單名稱 ========== -->
                    <div class="mb-4">
                        <label class="form-label">
                            選單名稱
                            <span class="text-danger">*</span>
                        </label>
                        <input
                            v-model="form.title"
                            type="text"
                            class="form-control"
                            :class="{ 'is-invalid': form.errors.title }"
                            placeholder="請輸入選單名稱"
                        >
                        <div v-if="form.errors.title" class="invalid-feedback">
                            {{ form.errors.title }}
                        </div>
                    </div>

                    <!-- ========== 父層選單 ========== -->
                    <div class="mb-4">
                        <label class="form-label">
                            父層選單
                            <span class="text-danger">*</span>
                        </label>
                        <select
                            v-model="form.parent_id"
                            class="form-select"
                            :class="{ 'is-invalid': form.errors.parent_id }"
                        >
                            <option 
                                v-for="option in parentOptions" 
                                :key="option.value" 
                                :value="option.value"
                            >
                                {{ option.label }}
                            </option>
                        </select>
                        <div v-if="form.errors.parent_id" class="invalid-feedback">
                            {{ form.errors.parent_id }}
                        </div>
                    </div>

                    <!-- ========== 顯示類型 ========== -->
                    <div class="mb-4">
                        <label class="form-label">
                            顯示類型
                            <span class="text-danger">*</span>
                        </label>
                        <select
                            v-model="form.type"
                            class="form-select"
                            :class="{ 'is-invalid': form.errors.type }"
                        >
                            <option :value="1">顯示在選單</option>
                            <option :value="0">不顯示</option>
                        </select>
                        <div v-if="form.errors.type" class="invalid-feedback">
                            {{ form.errors.type }}
                        </div>
                    </div>

                    <!-- ========== 連結網址 ========== -->
                    <div class="mb-4">
                        <label class="form-label">連結網址</label>
                        <input
                            v-model="form.url"
                            type="text"
                            class="form-control"
                            :class="{ 'is-invalid': form.errors.url }"
                            placeholder="例如：admin/news"
                        >
                        <div v-if="form.errors.url" class="invalid-feedback">
                            {{ form.errors.url }}
                        </div>
                        <small class="text-muted">選單對應的後台路徑</small>
                    </div>

                    <!-- ========== 路由名稱 ========== -->
                    <div class="mb-4">
                        <label class="form-label">路由名稱</label>
                        <input
                            v-model="form.url_name"
                            type="text"
                            class="form-control"
                            :class="{ 'is-invalid': form.errors.url_name }"
                            placeholder="例如：admin.news"
                        >
                        <div v-if="form.errors.url_name" class="invalid-feedback">
                            {{ form.errors.url_name }}
                        </div>
                        <small class="text-muted">Laravel 路由名稱，用於權限判斷</small>
                    </div>

                    <!-- ========== 圖標 ========== -->
                    <div class="mb-4">
                        <label class="form-label">圖標</label>
                        <input
                            v-model="form.icon_image"
                            type="text"
                            class="form-control"
                            :class="{ 'is-invalid': form.errors.icon_image }"
                            placeholder="例如：fa fa-bars"
                        >
                        <div v-if="form.errors.icon_image" class="invalid-feedback">
                            {{ form.errors.icon_image }}
                        </div>
                        <small class="text-muted">FontAwesome 圖標 class</small>
                    </div>

                    <!-- ========== 排序 ========== -->
                    <div class="mb-4">
                        <label class="form-label">排序</label>
                        <input
                            v-model.number="form.seq"
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
                                v-model="form.status"
                                type="checkbox"
                                class="form-check-input"
                                id="status"
                            >
                            <label class="form-check-label" for="status">
                                啟用
                            </label>
                        </div>
                    </div>

                    <!-- ========== 送出按鈕 ========== -->
                    <div class="text-end">
                        <Link 
                            :href="route('admin.menu-settings.index')" 
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
    },
    parentOptions: {
        type: Array,
        default: () => []
    }
});

const sweetAlert = inject('$sweetAlert');

// 表單資料
const form = useForm({
    title: props.data?.title || '',
    parent_id: props.data?.parent_id ?? 0,
    type: props.data?.type ?? 1,
    url: props.data?.url || '',
    url_name: props.data?.url_name || '',
    icon_image: props.data?.icon_image || '',
    seq: props.data?.seq ?? 0,
    status: props.data?.status ?? true,
});

// 提交表單
const submit = () => {
    const url = props.isEdit 
        ? route('admin.menu-settings.update', props.data.id)
        : route('admin.menu-settings.store');
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
