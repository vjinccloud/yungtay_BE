<!-- Modules/NewsManagement/Vue/Form.vue -->
<!-- 最新消息管理 - 新增 / 編輯表單 -->
<template>
    <div class="content">
        <BreadcrumbItem />

        <div class="block block-rounded">
            <div class="block-header block-header-default">
                <h3 class="block-title">
                    <Link class="btn btn-sm btn-alt-secondary" :href="route('admin.news-management.index')">
                        <i class="fa fa-arrow-left me-1"></i>
                        返回列表
                    </Link>
                </h3>
            </div>

            <div class="block-content block-content-full">
                <form @submit.prevent="submit">

                    <!-- 分類選擇 -->
                    <div class="mb-4">
                        <label class="form-label">分類 <span class="text-danger">*</span></label>
                        <select
                            v-model="form.category_id"
                            class="form-select"
                            :class="{ 'is-invalid': form.errors.category_id }"
                        >
                            <option value="">-- 請選擇分類 --</option>
                            <option
                                v-for="cat in props.categories"
                                :key="cat.id"
                                :value="cat.id"
                            >{{ cat.name }}</option>
                        </select>
                        <div v-if="form.errors.category_id" class="invalid-feedback">
                            {{ form.errors.category_id }}
                        </div>
                    </div>

                    <!-- 標題 -->
                    <div class="mb-4">
                        <label class="form-label">標題 <span class="text-danger">*</span></label>
                        <input
                            v-model="form.title.zh_TW"
                            type="text"
                            class="form-control"
                            :class="{ 'is-invalid': form.errors['title.zh_TW'] }"
                            placeholder="請輸入標題"
                        />
                        <div v-if="form.errors['title.zh_TW']" class="invalid-feedback">
                            {{ form.errors['title.zh_TW'] }}
                        </div>
                    </div>

                    <!-- 描述 -->
                    <div class="mb-4">
                        <label class="form-label">描述</label>
                        <textarea
                            v-model="form.description"
                            class="form-control"
                            :class="{ 'is-invalid': form.errors.description }"
                            placeholder="請輸入描述（選填）"
                            rows="3"
                        ></textarea>
                        <div v-if="form.errors.description" class="invalid-feedback">
                            {{ form.errors.description }}
                        </div>
                    </div>

                    <!-- 上架日期 -->
                    <div class="mb-4">
                        <label class="form-label">上架日期 <span class="text-danger">*</span></label>
                        <DatePicker
                            v-model="form.published_date"
                            placeholder="選擇上架日期"
                            :has-error="!!form.errors.published_date"
                            :class="{ 'is-invalid': form.errors.published_date }"
                        />
                        <div v-if="form.errors.published_date" class="invalid-feedback">
                            {{ form.errors.published_date }}
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
                            />
                            <label class="form-check-label" for="is_active">啟用狀態</label>
                        </div>
                    </div>

                    <!-- 列表圖片 -->
                    <div class="mb-4">
                        <label class="form-label">
                            列表圖片 <small class="text-muted">(建議尺寸: 500×285)</small>
                            <span class="text-danger">*</span>
                        </label>
                        <div style="width: 50%;">
                            <Slim
                                ref="slimImage"
                                :label="'圖片拖移至此，建議尺寸 500px * 285px'"
                                :width="500"
                                :height="285"
                                :ratio="'500:285'"
                                :initialImage="props.data?.img || ''"
                                @cleared="form.slimCleared = true"
                                :class="{ 'is-invalid': !!form.errors.slim }"
                            />
                        </div>
                        <div
                            v-show="form.errors.slim"
                            class="text-danger mt-2"
                            style="font-size: 0.875rem;"
                        >
                            {{ form.errors.slim || '' }}
                        </div>
                    </div>

                    <!-- 內容編輯器 -->
                    <div class="mb-4">
                        <label class="form-label">內容 <span class="text-danger">*</span></label>
                        <CKEditor4
                            v-model="form.content.zh_TW"
                            name="content_zh_tw"
                        />
                        <div
                            v-show="form.errors['content.zh_TW']"
                            class="text-danger mt-2"
                            style="font-size: 0.875rem;"
                        >
                            {{ form.errors['content.zh_TW'] || '' }}
                        </div>
                    </div>

                    <!-- 標籤 -->
                    <div class="mb-4">
                        <label class="form-label">標籤</label>
                        <EditableTags
                            v-model="form.tags"
                            :placeholder="'輸入標籤後按 Enter'"
                            :emptyText="'點擊添加標籤'"
                            :separator="','"
                            :trimValue="true"
                        />
                        <div v-if="form.errors.tags" class="text-danger mt-1" style="font-size: 0.875rem;">
                            {{ form.errors.tags }}
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
import { ref, inject, onBeforeUnmount } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import Layout from "@/Shared/Admin/Layout.vue";
import BreadcrumbItem from "@/Shared/Admin/Partials/BreadcrumbItem.vue";
import CKEditor4 from "@/Plugin/CKEditor4.vue";
import DatePicker from "@/Plugin/DatePicker.vue";
import Slim from "@/Plugin/Slim.vue";
import EditableTags from "@/Plugin/EditableTags.vue";
import { FormValidator, useSubmitForm, getSlimValue, destroySlim } from '@/utils';

const props = defineProps({
    data:       { type: Object, default: null },
    isEdit:     { type: Boolean, default: false },
    categories: { type: Array, default: () => [] },
});

const { submitForm: performSubmit } = useSubmitForm();
const sweetAlert = inject('$sweetAlert');

const slimImage = ref(null);

const form = useForm({
    category_id: props.data?.category_id || '',
    title: {
        zh_TW: props.data?.title?.zh_TW || '',
    },
    content: {
        zh_TW: props.data?.content?.zh_TW || '',
    },
    description: props.data?.description || '',
    tags: props.data?.tags || '',
    published_date: props.data?.published_date || '',
    is_active: props.data?.is_active ?? true,
    slim: null,
    slimCleared: false,
});

// 驗證規則
const getRules = () => ({
    category_id: ['required'],
    title: {
        zh_TW: ['required', 'string', ['max', 255]],
    },
    content: {
        zh_TW: ['required'],
    },
    published_date: ['required'],
    slim: props.isEdit ? [] : ['required'],
});

const validator = new FormValidator(form, getRules);

const submit = async () => {
    try {
        form.clearErrors();

        // 取得 Slim 圖片
        form.slim = getSlimValue(slimImage.value);

        // 驗證
        const hasErrors = await validator.hasErrors();
        if (hasErrors) {
            sweetAlert.error({ msg: '提交失敗，請檢查是否有欄位錯誤！' });
            return;
        }

        const url = props.isEdit
            ? route('admin.news-management.update', props.data.id)
            : route('admin.news-management.store');
        const method = props.isEdit ? 'put' : 'post';

        performSubmit({ form, url, method });
    } catch (error) {
        console.error('提交表單時發生錯誤:', error);
        sweetAlert.error({ msg: '系統錯誤，請稍後再試！' });
    }
};

const back = () => window.history.back();

onBeforeUnmount(() => {
    destroySlim(slimImage.value);
});
</script>

<script>
export default {
    layout: Layout,
};
</script>
