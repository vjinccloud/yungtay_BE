<!-- resources/js/InertiaPages/Admin/Radios/Form.vue -->
<template>
    <div class="content">
        <BreadcrumbItem title="廣播管理" />

        <div class="block block-rounded">
            <div class="block-header block-header-default">
                <h3 class="block-title">
                    <Link class="btn btn-sm btn-alt-secondary" :href="route('admin.radios')">
                        <i class="fa fa-arrow-left me-1"></i>
                        返回列表
                    </Link>
                </h3>
            </div>

            <div class="block-content block-content-full">
                <!-- 選項卡導航 -->
                <ul class="nav nav-tabs nav-tabs-block" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button
                            class="nav-link"
                            :class="{ active: activeTab === 'basic' }"
                            @click="setActiveTab('basic')"
                            type="button"
                        >
                            <i class="fa fa-info-circle me-1"></i>
                            基本資訊
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button
                            class="nav-link"
                            :class="{ active: activeTab === 'episodes' }"
                            @click="setActiveTab('episodes')"
                            type="button"
                        >
                            <i class="fa fa-music me-1"></i>
                            集數管理
                        </button>
                    </li>
                </ul>

                <!-- 選項卡內容 -->
                <div class="tab-content">
                    <!-- 基本資訊選項卡 -->
                    <div
                        class="tab-pane p-4"
                        :class="{ active: activeTab === 'basic' }"
                    >
                        <form @submit.prevent="submit">
                            <!-- 說明區塊 -->
                            <div class="alert alert-info mb-4">
                                <h6 class="alert-heading">
                                    <i class="fa fa-info-circle me-1"></i>
                                    基本設定說明
                                </h6>
                                <ul class="mb-0 small">
                                    <li>基本設定的最大季數會和集數管理季數連動</li>
                                    <li>狀態預設停用，等待設定完成和上傳完集數，再啟用讓前台顯示</li>
                                </ul>
                            </div>

                            <!-- 標題區塊 -->
                            <div class="mb-4">
                                <div class="row">
                                    <div class="col-md-6">
                                        <label class="form-label">標題 (中文)<span class="text-danger"> *</span></label>
                                        <input
                                            type="text"
                                            class="form-control"
                                            v-model="form.title.zh_TW"
                                            @blur="validator.singleField('title.zh_TW')"
                                            placeholder="請輸入中文標題"
                                            :class="{ 'is-invalid': form.errors?.title?.zh_TW }"
                                        />
                                        <div v-if="form.errors?.title?.zh_TW" class="invalid-feedback">
                                            {{ form.errors.title.zh_TW }}
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">標題 (英文)<span class="text-danger"> *</span></label>
                                        <input
                                            type="text"
                                            class="form-control"
                                            v-model="form.title.en"
                                            @blur="validator.singleField('title.en')"
                                            placeholder="請輸入英文標題"
                                            :class="{ 'is-invalid': form.errors?.title?.en }"
                                        />
                                        <div v-if="form.errors?.title?.en" class="invalid-feedback">
                                            {{ form.errors.title.en }}
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- 媒體名稱區塊 -->
                            <div class="mb-4">
                                <div class="row">
                                    <div class="col-md-6">
                                        <label class="form-label">媒體名稱 (中文)<span class="text-danger"> *</span></label>
                                        <input
                                            type="text"
                                            class="form-control"
                                            v-model="form.media_name.zh_TW"
                                            @blur="validator.singleField('media_name.zh_TW')"
                                            placeholder="請輸入中文媒體名稱"
                                            :class="{ 'is-invalid': form.errors?.media_name?.zh_TW }"
                                        />
                                        <div v-if="form.errors?.media_name?.zh_TW" class="invalid-feedback">
                                            {{ form.errors.media_name.zh_TW }}
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">媒體名稱 (英文)<span class="text-danger"> *</span></label>
                                        <input
                                            type="text"
                                            class="form-control"
                                            v-model="form.media_name.en"
                                            @blur="validator.singleField('media_name.en')"
                                            placeholder="請輸入英文媒體名稱"
                                            :class="{ 'is-invalid': form.errors?.media_name?.en }"
                                        />
                                        <div v-if="form.errors?.media_name?.en" class="invalid-feedback">
                                            {{ form.errors.media_name.en }}
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- 分類選擇 -->
                            <div class="mb-4">
                                <div class="row">
                                    <div class="col-md-6">
                                        <label class="form-label">分類 <span class="text-danger">*</span></label>
                                        <select
                                            class="form-select"
                                            v-model="form.category_id"
                                            @blur="validator.singleField('category_id')"
                                            :class="{ 'is-invalid': form.errors?.category_id }"
                                        >
                                            <option value="">請選擇分類</option>
                                            <option
                                                v-for="category in categories"
                                                :key="category.id"
                                                :value="category.id"
                                            >
                                                {{ category.name?.zh_TW || category.name }}
                                            </option>
                                        </select>
                                        <div v-if="form.errors?.category_id" class="invalid-feedback">
                                            {{ form.errors.category_id }}
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">子分類</label>
                                        <select
                                            class="form-select"
                                            v-model="form.subcategory_id"
                                            :disabled="!filteredSubcategories.length"
                                        >
                                            <option value="">請選擇子分類</option>
                                            <option
                                                v-for="sub in filteredSubcategories"
                                                :key="sub.id"
                                                :value="sub.id"
                                            >
                                                {{ sub.name_zh_tw || sub.name }}
                                            </option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- 季數與年份 -->
                            <div class="mb-4">
                                <div class="row">
                                    <div class="col-md-6">
                                        <label class="form-label">季數 <span class="text-danger">*</span></label>
                                        <select
                                            class="form-select"
                                            v-model="form.season"
                                            :class="{ 'is-invalid': form.errors?.season }"
                                        >
                                            <option value="">請選擇季數</option>
                                            <option
                                                v-for="s in availableSeasons"
                                                :key="s"
                                                :value="s"
                                            >
                                                {{ s }} 季
                                            </option>
                                        </select>
                                        <div v-if="form.errors?.season" class="invalid-feedback">
                                            {{ form.errors.season }}
                                        </div>
                                        <small class="form-text text-muted">
                                            最低季數限制： {{ minAllowedSeason }} 季
                                            <span v-if="hasEpisodeData">(因為已有集數)</span>
                                        </small>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">年份 <span class="text-danger">*</span></label>
                                        <input
                                            type="number"
                                            class="form-control"
                                            v-model="form.year"
                                            placeholder="例如：2025"
                                            min="1900"
                                            max="2100"
                                            :class="{ 'is-invalid': form.errors?.year }"
                                        />
                                        <div v-if="form.errors?.year" class="invalid-feedback">
                                            {{ form.errors.year }}
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- 簡介區塊 -->
                            <div class="mb-4">
                                <div class="row">
                                    <div class="col-md-6">
                                        <label class="form-label">簡介 (中文)</label>
                                        <textarea
                                            class="form-control"
                                            v-model="form.description.zh_TW"
                                            placeholder="請輸入中文簡介"
                                            rows="3"
                                        ></textarea>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">簡介 (英文)</label>
                                        <textarea
                                            class="form-control"
                                            v-model="form.description.en"
                                            placeholder="請輸入英文簡介"
                                            rows="3"
                                        ></textarea>
                                    </div>
                                </div>
                            </div>

                            <!-- 發布日期 -->
                            <div class="mb-4">
                                <label class="form-label">發布日期 <span class="text-danger">*</span></label>
                                <DatePicker
                                    v-model="form.publish_date"
                                    :placeholder="'請選擇發布日期'"
                                    :has-error="!!form.errors?.publish_date"
                                />
                                <div v-if="form.errors?.publish_date" class="invalid-feedback d-block">
                                    {{ form.errors.publish_date }}
                                </div>
                            </div>

                            <!-- 狀態 -->
                            <div class="mb-4">
                                <label class="form-label">狀態</label>
                                <div class="form-check form-switch">
                                    <input
                                        class="form-check-input"
                                        type="checkbox"
                                        id="is_active"
                                        v-model="form.is_active"
                                    />
                                    <label class="form-check-label" for="is_active">
                                        {{ form.is_active ? '啟用' : '停用' }}
                                    </label>
                                </div>
                            </div>

                            <!-- 封面圖片 -->
                            <div class="mb-4">
                                <label class="form-label">封面圖片（建議尺寸 寬415px * 高415px） <span class="text-danger">*</span></label>
                                <div style="width: 50%;">
                                    <Slim
                                        ref="slimImage"
                                        :label="'圖片拖移至此，建議尺寸 寬415px * 高415px'"
                                        :width="415"
                                        :height="415"
                                        :ratio="'415:415'"
                                        :initialImage="props.radio?.img || ''"
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

                            <!-- Banner 圖片 -->
                            <div class="mb-4">
                                <h5 class="content-heading">Banner 圖片</h5>

                                <!-- Banner 桌機版 -->
                                <div class="mb-3">
                                    <label class="form-label">Banner 桌機版圖片（建議尺寸：1915px × 798px） <span class="text-danger">*</span></label>
                                    <div style="width: 70%;">
                                        <Slim
                                            ref="slimBannerDesktop"
                                            :label="'圖片拖移至此，建議尺寸 寬1915px × 高798px'"
                                            :width="1915"
                                            :height="798"
                                            :ratio="'1915:798'"
                                            :initialImage="props.radio?.banner_desktop || ''"
                                            @cleared="form.slimClearedBannerDesktop = true"
                                            :class="{ 'is-invalid': !!form.errors.banner_desktop }"
                                        />
                                    </div>
                                    <div
                                        v-show="form.errors.banner_desktop"
                                        class="text-danger mt-2"
                                        style="display: block !important; font-size: 0.875rem;"
                                    >
                                        {{ form.errors.banner_desktop || '' }}
                                    </div>
                                </div>

                                <!-- Banner 手機版 -->
                                <div class="mb-3">
                                    <label class="form-label">Banner 手機版圖片（建議尺寸：430px × 240px） <span class="text-danger">*</span></label>
                                    <div style="width: 40%;">
                                        <Slim
                                            ref="slimBannerMobile"
                                            :label="'圖片拖移至此，建議尺寸 寬430px × 高240px'"
                                            :width="430"
                                            :height="240"
                                            :ratio="'430:240'"
                                            :initialImage="props.radio?.banner_mobile || ''"
                                            @cleared="form.slimClearedBannerMobile = true"
                                            :class="{ 'is-invalid': !!form.errors.banner_mobile }"
                                        />
                                    </div>
                                    <div
                                        v-show="form.errors.banner_mobile"
                                        class="text-danger mt-2"
                                        style="display: block !important; font-size: 0.875rem;"
                                    >
                                        {{ form.errors.banner_mobile || '' }}
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
                                    <i class="fa fa-arrow-left me-1"></i>
                                    回上一頁
                                </button>
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

                    <!-- 集數管理選項卡 -->
                    <div
                        class="tab-pane p-4"
                        :class="{ active: activeTab === 'episodes' }"
                    >
                        <RadioEpisodeManager
                            :radio-id="props.radio?.id || null"
                            :max-seasons="parseInt(form.season) || 1"
                            @episode-season-changed="handleEpisodeSeasonChanged"
                        />
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, inject, computed } from 'vue'
import { useForm, Link } from '@inertiajs/vue3'
import Layout from "@/Shared/Admin/Layout.vue";
import BreadcrumbItem from "@/Shared/Admin/Partials/BreadcrumbItem.vue";
import Slim from '@/Plugin/Slim.vue'
import DatePicker from '@/Plugin/DatePicker.vue'
import RadioEpisodeManager from './Components/RadioEpisodeManager.vue'
import { FormValidator, useSubmitForm, getSlimValue } from '@/utils';

// 接收 props
const props = defineProps({
    radio: {
        type: Object,
        default: () => ({})
    },
    categories: {
        type: Array,
        default: () => []
    },
    subcategories: {
        type: Array,
        default: () => []
    },
    maxSeasonWithEpisodes: {
        type: Number,
        default: 0
    }
});

// 引入 submitForm 方法
const { submitForm: performSubmit } = useSubmitForm();
const sweetAlert = inject('$sweetAlert');

// Tab 控制
const activeTab = ref('basic')

const setActiveTab = (tab) => {
    activeTab.value = tab
}

// Computed
const isEditing = computed(() => !!props.radio?.id)

// 過濾出當前主分類的子分類
const filteredSubcategories = computed(() => {
    if (!form.category_id || !props.subcategories) return []
    return props.subcategories.filter(sub => sub.parent_id === parseInt(form.category_id))
})

// 本地響應式的最大集數季數（用於即時更新）
const localMaxSeasonWithEpisodes = ref(props.maxSeasonWithEpisodes || 0)

// 最低季數限制（根據集數管理已有的資料）
const minAllowedSeason = computed(() => {
    return Math.max(1, localMaxSeasonWithEpisodes.value)
})

// 是否有集數資料
const hasEpisodeData = computed(() => {
    return localMaxSeasonWithEpisodes.value > 0
})

// 當集數管理新增/刪除集數時，更新季數同步
const handleEpisodeSeasonChanged = (newMaxSeason) => {
    localMaxSeasonWithEpisodes.value = newMaxSeason

    // 如果新增的集數季數 > 當前基本設定的季數，自動同步
    if (newMaxSeason > parseInt(form.season)) {
        form.season = String(newMaxSeason)
    }
}

// 可選季數列表（從最低限制開始到 7 季）
const availableSeasons = computed(() => {
    const options = []
    const min = minAllowedSeason.value
    for (let i = min; i <= 7; i++) {
        options.push(i)
    }
    return options
})


// 表單資料
const form = useForm({
    title: {
        zh_TW: props.radio?.title?.zh_TW || '',
        en: props.radio?.title?.en || ''
    },
    description: {
        zh_TW: props.radio?.description?.zh_TW || '',
        en: props.radio?.description?.en || ''
    },
    media_name: {
        zh_TW: props.radio?.media_name?.zh_TW || '',
        en: props.radio?.media_name?.en || ''
    },
    category_id: props.radio?.category_id || '',
    subcategory_id: props.radio?.subcategory_id || '',
    year: props.radio?.year || new Date().getFullYear(),
    season: props.radio?.season || '1',
    slim: null,
    slimCleared: false,
    banner_desktop: null,
    banner_mobile: null,
    slimClearedBannerDesktop: false,
    slimClearedBannerMobile: false,
    publish_date: props.radio?.publish_date || new Date().toISOString().split('T')[0],
    is_active: props.radio?.is_active ?? false
});

// 定義驗證規則
const getRules = () => {
    return {
        'title.zh_TW': ['required', 'string', ['max', 255]],
        'title.en': ['required', 'string', ['max', 255]],
        'media_name.zh_TW': ['required', 'string', ['max', 255]],
        'media_name.en': ['required', 'string', ['max', 255]],
        'category_id': ['required'],
        'year': ['required'],
        'season': ['required'],
        'publish_date': ['required']
    };
};

// 建立驗證器
const validator = new FormValidator(form, getRules);

// Refs
const slimImage = ref(null)
const slimBannerDesktop = ref(null)
const slimBannerMobile = ref(null)

// 提交表單
const submit = async () => {
    try {
        form.clearErrors();

        // 處理 Slim 圖片
        form.slim = getSlimValue(slimImage.value);
        form.banner_desktop = getSlimValue(slimBannerDesktop.value);
        form.banner_mobile = getSlimValue(slimBannerMobile.value);

        // 設定提交參數
        const url = isEditing.value
            ? route('admin.radios.update', props.radio.id)
            : route('admin.radios.store');
        const method = isEditing.value ? 'put' : 'post';

        // 驗證表單
        const hasErrors = await validator.hasErrors();
        if (!hasErrors) {
            performSubmit({ form, url, method });
        } else {
            sweetAlert?.error({
                msg: '提交失敗，請檢查是否有欄位錯誤！'
            });
        }
    } catch (error) {
        console.error('提交表單時發生錯誤:', error);
        sweetAlert?.error({
            msg: '系統錯誤，請稍後再試！'
        });
    }
};

// 返回上一頁
const back = () => {
    window.history.back();
};
</script>

<script>
export default {
    layout: Layout,
};
</script>

<style scoped>
/* Tab 樣式 */
.nav-tabs-block {
    background-color: #f5f5f5;
    border-bottom: 2px solid #dee2e6;
}

.nav-tabs-block .nav-link {
    color: #495057;
    border: none;
    border-radius: 0;
    padding: 12px 20px;
}

.nav-tabs-block .nav-link:hover {
    background-color: #e9ecef;
}

.nav-tabs-block .nav-link.active {
    background-color: #fff;
    color: #0d6efd;
    border-bottom: 2px solid #0d6efd;
    margin-bottom: -2px;
}

.nav-tabs-block .nav-link:disabled {
    color: #adb5bd;
    cursor: not-allowed;
}

.tab-content > .tab-pane {
    display: none;
}

.tab-content > .tab-pane.active {
    display: block;
}
</style>
