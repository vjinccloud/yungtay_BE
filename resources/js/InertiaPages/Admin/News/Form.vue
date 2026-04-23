<!-- resources/js/InertiaPages/Admin/News/Form.vue -->
<template>
    <div class="content">
        <BreadcrumbItem />

        <div class="block block-rounded">
            <div class="block-header block-header-default">
                <h3 class="block-title">
                    <Link class="btn btn-sm btn-alt-secondary" :href="route('admin.news')">
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
                            :class="{'is-invalid': form.errors.category_id}"
                            @blur="validator.singleField('category_id')"
                            required
                        >
                            <option value="">-- 請選擇分類 --</option>
                            <option
                                v-for="category in props.categories"
                                :key="category.id"
                                :value="category.id"
                            >
                                {{ category.name }}
                            </option>
                        </select>
                        <div v-if="form.errors.category_id" class="invalid-feedback">
                            {{ form.errors.category_id }}
                        </div>
                    </div>

                    <!-- 標題欄位 -->
                    <div class="mb-4">
                        <div class="mb-3">
                            <label class="form-label">標題 <span class="text-danger">*</span></label>
                            <input
                                v-model="form.title.zh_TW"
                                type="text"
                                class="form-control"
                                :class="{'is-invalid': form.errors.title?.zh_TW}"
                                placeholder="請輸入標題"
                                @blur="validator.singleField('title.zh_TW')"
                                required
                            >
                            <div v-if="form.errors.title?.zh_TW" class="invalid-feedback">
                                {{ form.errors.title?.zh_TW }}
                            </div>
                        </div>
                    </div>

                    <!-- 描述欄位 -->
                    <div class="mb-4">
                        <label class="form-label">描述</label>
                        <textarea
                            v-model="form.description"
                            class="form-control"
                            :class="{'is-invalid': form.errors.description}"
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
                            @update:modelValue="() => validator.singleField('published_date')"
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
                            >
                            <label class="form-check-label" for="is_active">
                                啟用狀態
                            </label>
                        </div>
                    </div>

                    <!-- 列表圖片（桌機手機共用） -->
                    <div class="mb-4">
                        <label class="form-label">列表圖片（桌機手機共用） <span class="text-danger">*</span></label>
                        <div style="width: 50%;">
                            <Slim
                                ref="slimImage"
                                :label="'圖片拖移至此，建議尺寸 寬500px * 高285px'"
                                :width="500"
                                :height="285"
                                :ratio="'500:285'"
                                :initialImage="props.news?.img || ''"
                                @cleared="form.slimCleared = true"
                                :class="{ 'is-invalid': !!form.errors.slim }"
                            />
                        </div>
                        <div
                            v-show="form.errors.slim"
                            class="text-danger mt-2"
                            style="display: block !important; font-size: 0.875rem;"
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
                            v-show="form.errors.content?.zh_TW"
                            class="text-danger mt-2"
                            style="display: block !important; font-size: 0.875rem;"
                        >
                            {{ form.errors.content?.zh_TW || '' }}
                        </div>
                    </div>

                    <!-- 標籤欄位 -->
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
import { ref, inject, onBeforeUnmount } from 'vue'
import { useForm, Link } from '@inertiajs/vue3'
import Layout from "@/Shared/Admin/Layout.vue";
import BreadcrumbItem from "@/Shared/Admin/Partials/BreadcrumbItem.vue";
import CKEditor4 from "@/Plugin/CKEditor4.vue";
import DatePicker from "@/Plugin/DatePicker.vue";
import Slim from "@/Plugin/Slim.vue";
import EditableTags from "@/Plugin/EditableTags.vue";
import { FormValidator, useSubmitForm, getSlimValue, destroySlim } from '@/utils';

// 編輯模式時接收 props
const props = defineProps({
    news: {
        type: Object,
        default: null
    },
    categories: {
        type: Array,
        default: () => []
    }
})

// 引入 submitForm 方法
const { submitForm: performSubmit } = useSubmitForm();

// Slim 圖片參考
const slimImage = ref(null)
// 表單資料
const form = useForm({
    // 分類
    category_id: props.news?.category_id || '',

    // 多語系欄位
    title: {
        zh_TW: props.news?.title?.zh_TW || '',
        en: props.news?.title?.en || ''
    },
    content: {
        zh_TW: props.news?.content?.zh_TW || '',
        en: props.news?.content?.en || ''
    },

    // 描述
    description: props.news?.description || '',

    // 標籤
    tags: props.news?.tags || '',

    // 刊登日期
    published_date: props.news?.published_date || '',

    // 啟用狀態
    is_active: props.news?.is_active ?? true,

    // Slim 圖片資料
    slim: null,
    slimCleared: false,
});

// 定義驗證規則
const getRules = () => ({
    category_id: ['required'],
    title: {
        zh_TW: ['required', 'string', ['max', 255]],
    },
    content: {
        zh_TW: ['required'],
    },
    published_date: ['required'],
    slim: props.news?.id ? [] : ['required'], // 編輯時非必填，新增時必填
});

// 建立驗證器
const validator = new FormValidator(form, getRules);
const sweetAlert = inject('$sweetAlert');

// 提交表單
const submit = async () => {
    try {
        form.clearErrors();
        // 使用 slimUtils 獲取 Slim 值
        form.slim = getSlimValue(slimImage.value);

        // 設定提交參數
        form.confirm = false;
        const url = props.news?.id
            ? route('admin.news.update', props.news.id)
            : route('admin.news.store');
        const method = props.news?.id ? 'put' : 'post';

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

const back = () => {
    window.history.back();
};

// 在組件銷毀前清理 - 使用 slimUtils
onBeforeUnmount(async () => {
    await destroySlim(slimImage.value);
});
</script>

<script>
export default {
    layout: Layout,
};
</script>
