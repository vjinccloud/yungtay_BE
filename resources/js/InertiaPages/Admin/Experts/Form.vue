<!-- resources/js/InertiaPages/Admin/Experts/Form.vue -->
<template>
    <div class="content">
        <BreadcrumbItem />

        <div class="block block-rounded">
            <div class="block-header block-header-default">
                <h3 class="block-title">
                    <Link class="btn btn-sm btn-alt-secondary" :href="route('admin.experts')">
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
                                {{ category.name_zh }}
                            </option>
                        </select>
                        <div v-if="form.errors.category_id" class="invalid-feedback">
                            {{ form.errors.category_id }}
                        </div>
                    </div>

                    <!-- 專家領域（標籤輸入） -->
                    <div class="mb-4">
                        <label class="form-label">專家領域</label>
                        <EditableTags
                            v-model="form.tags"
                            :placeholder="'輸入領域標籤後按 Enter'"
                            :emptyText="'點擊添加領域標籤'"
                            :separator="','"
                            :trimValue="true"
                        />
                        <div v-if="form.errors.tags" class="text-danger mt-1" style="font-size: 0.875rem;">
                            {{ form.errors.tags }}
                        </div>
                    </div>

                    <!-- 姓名欄位 -->
                    <div class="mb-4">
                        <label class="form-label">姓名 <span class="text-danger">*</span></label>
                        <input
                            v-model="form.name.zh_TW"
                            type="text"
                            class="form-control"
                            :class="{'is-invalid': form.errors['name.zh_TW']}"
                            placeholder="請輸入專家姓名"
                            @blur="validator.singleField('name.zh_TW')"
                            required
                        >
                        <div v-if="form.errors['name.zh_TW']" class="invalid-feedback">
                            {{ form.errors['name.zh_TW'] }}
                        </div>
                    </div>

                    <!-- 職稱欄位 -->
                    <div class="mb-4">
                        <label class="form-label">職稱</label>
                        <input
                            v-model="form.title.zh_TW"
                            type="text"
                            class="form-control"
                            :class="{'is-invalid': form.errors['title.zh_TW']}"
                            placeholder="請輸入職稱"
                        >
                        <div v-if="form.errors['title.zh_TW']" class="invalid-feedback">
                            {{ form.errors['title.zh_TW'] }}
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

                    <!-- 首席專家 -->
                    <div class="mb-4">
                        <div class="form-check form-switch">
                            <input
                                v-model="form.is_featured"
                                class="form-check-input"
                                type="checkbox"
                                id="is_featured"
                            >
                            <label class="form-check-label" for="is_featured">
                                首席專家
                            </label>
                        </div>
                        <small class="text-muted">勾選後將顯示在生命故事頁面的首席專家區塊</small>
                    </div>

                    <!-- 專家照片 -->
                    <div class="mb-4">
                        <label class="form-label">專家照片 <span class="text-danger">*</span></label>
                        <div style="width: 300px;">
                            <Slim
                                ref="slimImage"
                                :label="'圖片拖移至此，建議尺寸 300px * 300px'"
                                :width="300"
                                :height="300"
                                :ratio="'1:1'"
                                :initialImage="props.expert?.img || ''"
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

                    <!-- 簡介 -->
                    <div class="mb-4">
                        <label class="form-label">簡介</label>
                        <textarea
                            v-model="form.bio.zh_TW"
                            class="form-control"
                            :class="{'is-invalid': form.errors['bio.zh_TW']}"
                            placeholder="請輸入專家簡介"
                            rows="4"
                        ></textarea>
                        <div v-if="form.errors['bio.zh_TW']" class="invalid-feedback">
                            {{ form.errors['bio.zh_TW'] }}
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
import Slim from "@/Plugin/Slim.vue";
import EditableTags from "@/Plugin/EditableTags.vue";
import { FormValidator, useSubmitForm, getSlimValue, destroySlim } from '@/utils';

// 編輯模式時接收 props
const props = defineProps({
    expert: {
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
    category_id: props.expert?.category_id || '',

    // 領域標籤
    tags: props.expert?.tags || '',

    // 多語系欄位
    name: {
        zh_TW: props.expert?.name?.zh_TW || '',
    },
    title: {
        zh_TW: props.expert?.title?.zh_TW || '',
    },
    bio: {
        zh_TW: props.expert?.bio?.zh_TW || '',
    },

    // 啟用狀態
    is_active: props.expert?.is_active ?? true,

    // 首席專家
    is_featured: props.expert?.is_featured ?? false,

    // Slim 圖片資料
    slim: null,
    slimCleared: false,
});

// 定義驗證規則
const getRules = () => ({
    category_id: ['required'],
    'name.zh_TW': ['required', 'string', ['max', 255]],
    slim: props.expert?.id ? [] : ['required'], // 編輯時非必填，新增時必填
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
        const url = props.expert?.id
            ? route('admin.experts.update', props.expert.id)
            : route('admin.experts.store');
        const method = props.expert?.id ? 'put' : 'post';

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
