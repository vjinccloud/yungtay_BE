<!-- resources/js/InertiaPages/Admin/Article/Form.vue -->
<template>
    <div class="content">
        <BreadcrumbItem />

        <div class="block block-rounded">
            <div class="block-header block-header-default">
                <h3 class="block-title">
                    <Link class="btn btn-sm btn-alt-secondary" :href="route('admin.articles')">
                        <i class="fa fa-arrow-left me-1"></i>
                        返回列表
                    </Link>
                </h3>
            </div>

            <div class="block-content block-content-full">

                <form @submit.prevent="submit">
                    <!-- 新聞分類 -->
                    <div class="mb-4">
                        <label class="form-label">新聞分類 <span class="text-danger">*</span></label>
                        <select
                            v-model="form.category_id"
                            class="form-select"
                            :class="{'is-invalid': form.errors.category_id}"
                            @blur="validator.singleField('category_id')"
                            required
                        >
                            <option value="">請選擇分類</option>
                            <option
                                v-for="category in categories"
                                :key="category.id"
                                :value="category.id"
                            >
                                {{ category.name?.zh_TW || category.name_zh_tw || category.name }}
                            </option>
                        </select>
                        <div v-if="form.errors.category_id" class="invalid-feedback">
                            {{ form.errors.category_id }}
                        </div>
                    </div>

                    <!-- 標題欄位（支援多語系） -->
                    <div class="mb-4">
                        <div class="mb-3">
                            <label class="form-label">標題（中文） <span class="text-danger">*</span></label>
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
                        <div class="mb-3">
                            <label class="form-label">標題（英文） <span class="text-danger">*</span></label>
                            <input
                                v-model="form.title.en"
                                type="text"
                                class="form-control"
                                :class="{'is-invalid': form.errors.title?.en}"
                                placeholder="English Title"
                                @blur="validator.singleField('title.en')"
                                required
                            >
                            <div v-if="form.errors.title?.en" class="invalid-feedback">
                                {{ form.errors.title?.en }}
                            </div>
                        </div>
                    </div>

                    <!-- 上架日期 -->
                    <div class="mb-4">
                        <label class="form-label">上架日期 <span class="text-danger">*</span></label>
                        <DatePicker
                            v-model="form.publish_date"
                            placeholder="選擇上架日期"
                            :has-error="!!form.errors.publish_date"
                            :class="{ 'is-invalid': form.errors.publish_date }"
                            @update:modelValue="() => validator.singleField('publish_date')"
                        />
                        <div v-if="form.errors.publish_date" class="invalid-feedback">
                            {{ form.errors.publish_date }}
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

                    <!-- 列表圖片 -->
                    <div class="mb-4">
                         <label class="form-label">封面圖片（桌機手機共用，建議比例 16:9）</label>
                        <div style="width: 50%;">
                            <Slim
                                ref="slimImage"
                                :width="0"
                                :height="0"
                                :label="'圖片拖移至此，建議比例 16:9'"
                                :ratio="'16:9'"
                                :initialImage="props.article?.img || ''"
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

                    <!-- 作者欄位（中英文分一半） -->
                    <div class="mb-4">
                        <div class="row">
                            <div class="col-md-6">
                                <label class="form-label">作者（中文） <span class="text-danger">*</span></label>
                                <input
                                    v-model="form.author.zh_TW"
                                    type="text"
                                    class="form-control"
                                    :class="{'is-invalid': form.errors.author?.zh_TW}"
                                    placeholder="中文作者"
                                    @blur="validator.singleField('author.zh_TW')"
                                    required
                                >
                                <div v-if="form.errors.author?.zh_TW" class="invalid-feedback">
                                    {{ form.errors.author?.zh_TW }}
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">作者（英文） <span class="text-danger">*</span></label>
                                <input
                                    v-model="form.author.en"
                                    type="text"
                                    class="form-control"
                                    :class="{'is-invalid': form.errors.author?.en}"
                                    placeholder="English Author"
                                    @blur="validator.singleField('author.en')"
                                    required
                                >
                                <div v-if="form.errors.author?.en" class="invalid-feedback">
                                    {{ form.errors.author?.en }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 地點欄位（中英文分一半） -->
                    <div class="mb-4">
                        <div class="row">
                            <div class="col-md-6">
                                <label class="form-label">地點（中文）</label>
                                <input
                                    v-model="form.location.zh_TW"
                                    type="text"
                                    class="form-control"
                                    :class="{'is-invalid': form.errors.location?.zh_TW}"
                                    placeholder="中文地點"
                                    @blur="validator.singleField('location.zh_TW')"
                                >
                                <div v-if="form.errors.location?.zh_TW" class="invalid-feedback">
                                    {{ form.errors.location?.zh_TW }}
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">地點（英文）</label>
                                <input
                                    v-model="form.location.en"
                                    type="text"
                                    class="form-control"
                                    :class="{'is-invalid': form.errors.location?.en}"
                                    placeholder="English Location"
                                    @blur="validator.singleField('location.en')"
                                >
                                <div v-if="form.errors.location?.en" class="invalid-feedback">
                                    {{ form.errors.location?.en }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 內容編輯器（支援多語系） -->
                    <div class="mb-4">
                        <label class="form-label">內容 <span class="text-danger">*</span></label>

                        <!-- 語系切換標籤 -->
                        <ul class="nav nav-tabs nav-tabs-block" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button
                                    class="nav-link active"
                                    id="content-zh-tab"
                                    data-bs-toggle="tab"
                                    data-bs-target="#content-zh"
                                    type="button"
                                    role="tab"
                                >
                                    <i class="fa fa-language me-1"></i>
                                    中文
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button
                                    class="nav-link"
                                    id="content-en-tab"
                                    data-bs-toggle="tab"
                                    data-bs-target="#content-en"
                                    type="button"
                                    role="tab"
                                >
                                    <i class="fa fa-globe me-1"></i>
                                    English
                                </button>
                            </li>
                        </ul>

                        <!-- 內容編輯區 -->
                        <div class="tab-content">
                            <div class="tab-pane fade show active" id="content-zh" role="tabpanel">
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
                            <div class="tab-pane fade" id="content-en" role="tabpanel">
                                <CKEditor4
                                    v-model="form.content.en"
                                    name="content_en_us"
                                />
                                <div
                                    v-show="form.errors.content?.en"
                                    class="text-danger mt-2"
                                    style="display: block !important; font-size: 0.875rem;"
                                >
                                    {{ form.errors.content?.en }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 標籤欄位（使用 EditableTags 組件） -->
                    <div class="mb-4">
                        <div class="mb-3">
                            <label class="form-label">標籤（中文） <span class="text-danger">*</span></label>
                            <EditableTags
                                v-model="form.tags.zh_TW"
                                :placeholder="'輸入中文標籤後按 Enter'"
                                :emptyText="'點擊添加中文標籤'"
                                :separator="','"
                                :trimValue="true"
                                :required="true"
                                @update="() => validator.singleField('tags.zh_TW')"
                            />
                            <div v-if="form.errors.tags?.zh_TW" class="text-danger mt-1" style="font-size: 0.875rem;">
                                {{ form.errors.tags?.zh_TW }}
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">標籤（英文） <span class="text-danger">*</span></label>
                            <EditableTags
                                v-model="form.tags.en"
                                :placeholder="'Enter English tags and press Enter'"
                                :emptyText="'Click to add English tags'"
                                :separator="','"
                                :trimValue="true"
                                :required="true"
                                @update="() => validator.singleField('tags.en')"
                            />
                            <div v-if="form.errors.tags?.en" class="text-danger mt-1" style="font-size: 0.875rem;">
                                {{ form.errors.tags?.en }}
                            </div>
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
    article: {
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
    // 基本資料
    category_id: props.article?.category_id || '',
    publish_date: props.article?.publish_date || new Date().toISOString().split('T')[0],
    is_active: props.article?.is_active ?? true,

    // 多語系欄位
    title: {
        zh_TW: props.article?.title?.zh_TW || '',
        en: props.article?.title?.en || ''
    },
    author: {
        zh_TW: props.article?.author?.zh_TW || '',
        en: props.article?.author?.en || ''
    },
    location: {
        zh_TW: props.article?.location?.zh_TW || '',
        en: props.article?.location?.en || ''
    },
    content: {
        zh_TW: props.article?.content?.zh_TW || '',
        en: props.article?.content?.en || ''
    },
    tags: {
        zh_TW: props.article?.tags?.zh_TW || '',
        en: props.article?.tags?.en || ''
    },

    // Slim 圖片資料
    slim: null,
    slimCleared: false,
});

// 定義驗證規則
const getRules = () => ({
    category_id: ['required', 'integer'],
    publish_date: ['required'],
    title: {
        zh_TW: ['required', 'string', ['max', 255]],
        en: ['required', 'string', ['max', 255]],
    },
    author: {
        zh_TW: ['required', 'string', ['max', 255]],
        en: ['required', 'string', ['max', 255]],
    },
    location: {
        zh_TW: ['string', ['max', 255]],
        en: ['string', ['max', 255]],
    },
    content: {
        zh_TW: ['required'],
        en: ['required'],
    },
    tags: {
        zh_TW: ['required', 'string'],
        en: ['required', 'string'],
    },
    slim: [], // 封面圖片改為非必填
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
        const url = props.article?.id
            ? route('admin.articles.update', props.article.id)
            : route('admin.articles.store');
        const method = props.article?.id ? 'put' : 'post';

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
