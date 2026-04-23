<!-- resources/js/InertiaPages/Admin/Shared/Content/Components/BaseBasicInfo.vue -->
<template>
    <div class="content-basic-info p-4" :data-content-type="contentType">

        <h4 class="content-heading">基本資訊</h4>
        <!-- 說明區塊 -->
        <div class="alert alert-info mb-4">
             <h6 class="alert-heading">
                <i class="fa fa-info-circle me-1"></i>
                基本設定說明
            </h6>
            <ul class="mb-0 small">
                <li>基本設定的最大季數會和集數管理季數連動</li>
                <li>狀態預設停用，等待設定完成和上傳完影片，在啟用讓前台顯示</li>
            </ul>
        </div>

        <!-- 標題（多語系） -->
        <div class="mb-3">
            <label class="form-label">{{ contentLabel }}標題 <span class="text-danger">*</span></label>
            <template v-for="(lang, langKey) in langs" :key="langKey">
                <div :class="langKey === 'zh_TW' ? 'mb-3' : ''">
                    <input
                        v-model="form.title[langKey]"
                        type="text"
                        class="form-control"
                        :class="{'is-invalid': form.errors.title?.[langKey]}"
                        :placeholder="lang.placeholder"
                        @blur="() => validateField(`title.${langKey}`)"
                        required
                    >
                    <div v-if="form.errors.title?.[langKey]" class="invalid-feedback">
                        {{ form.errors.title?.[langKey] }}
                    </div>
                </div>
            </template>
        </div>

        <!-- 分類選擇 -->
        <div class="row">
            <div class="col-md-6">
                <div class="mb-3">
                    <label class="form-label">主分類 <span class="text-danger">*</span></label>
                    <select
                        v-model="form.category_id"
                        class="form-control"
                        :class="{'is-invalid': form.errors.category_id}"
                        @change="onCategoryChange"
                        required
                    >
                        <option value="">請選擇主分類</option>
                        <option
                            v-for="category in categories"
                            :key="category.id"
                            :value="category.id"
                        >
                            {{ category.name_zh_tw }}
                        </option>
                    </select>
                    <div v-if="form.errors.category_id" class="invalid-feedback">
                        {{ form.errors.category_id }}
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="mb-3">
                    <label class="form-label">子分類 <span class="text-danger">*</span></label>
                    <select
                        v-model="form.subcategory_id"
                        class="form-control"
                        :class="{'is-invalid': form.errors.subcategory_id}"
                        :disabled="!form.category_id"
                        @change="onSubcategoryChange"
                        required
                    >
                        <option value="">請選擇子分類</option>
                        <option
                            v-for="subcategory in filteredSubcategories"
                            :key="subcategory.id"
                            :value="subcategory.id"
                        >
                            {{ subcategory.name_zh_tw }}
                        </option>
                    </select>
                    <div v-if="form.errors.subcategory_id" class="invalid-feedback">
                        {{ form.errors.subcategory_id }}
                    </div>
                </div>
            </div>
        </div>

        <!-- 季數和年份 -->
        <div class="row">
            <div class="col-md-6">
                <div class="mb-3">
                    <label class="form-label">季數 <span class="text-danger">*</span></label>
                    <select
                        v-model="form.season_number"
                        class="form-control"
                        :class="{'is-invalid': form.errors.season_number}"
                        @change="() => onSeasonChange(emit)"
                        required
                    >
                        <option
                            v-for="season in availableSeasons"
                            :key="season"
                            :value="season"
                        >
                             {{ season }} 季
                        </option>
                    </select>
                    <div v-if="form.errors.season_number" class="invalid-feedback">
                        {{ form.errors.season_number }}
                    </div>
                    <small class="form-text text-muted">
                        最低季數限制： {{ minAllowedSeason }} 季
                        <span v-if="hasVideoData">(因為已有影片)</span>
                    </small>
                </div>
            </div>
            <div class="col-md-6">
                <div class="mb-3">
                    <label class="form-label">發行年份 <span class="text-danger">*</span></label>
                    <input
                        v-model.number="form.release_year"
                        type="number"
                        class="form-control"
                        :class="{'is-invalid': form.errors.release_year}"
                        :min="1900"
                        :max="new Date().getFullYear() + 5"
                        @blur="() => validateField('release_year')"
                        required
                    >
                    <div v-if="form.errors.release_year" class="invalid-feedback">
                        {{ form.errors.release_year }}
                    </div>
                </div>
            </div>
        </div>

        <!-- 發佈日期 -->
        <div class="mb-3">
            <label class="form-label">發佈日期 <span class="text-danger">*</span></label>
            <DatePicker
                v-model="form.published_date"
                placeholder="選擇發佈日期"
                :has-error="!!form.errors.published_date"
                @update:modelValue="() => validateField('published_date')"
                required
            />
            <div v-if="form.errors.published_date" class="invalid-feedback">
                {{ form.errors.published_date }}
            </div>
        </div>

        <!-- 啟用狀態 -->
        <div class="mb-3">
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

        <!-- 圖片 -->
        <div class="mb-4">
            <h5 class="content-heading">{{ contentLabel }}圖片</h5>

            <!-- 使用 v-for 簡化圖片上傳區塊 -->
            <div v-for="img in imageConfigs.slice(0, 2)" :key="img.key" class="mb-3">
                <label class="form-label">{{ img.label }} <span class="text-danger">*</span></label>
                <div :style="`width: ${img.containerWidth};`">
                    <Slim
                        :ref="el => imageRefs[img.key] = el"
                        :label="img.slimLabel"
                        :width="img.width"
                        :height="img.height"
                        :ratio="`${img.width}:${img.height}`"
                        :initialImage="initialData?.[img.key] || ''"
                        @cleared="form[img.clearKey] = true"
                        :class="{ 'is-invalid': !!form.errors[img.key] }"
                    />
                </div>
                <div
                    v-show="form.errors[img.key]"
                    class="text-danger mt-2"
                    style="display: block !important; font-size: 0.875rem;"
                >
                    {{ form.errors[img.key] || '' }}
                </div>
            </div>
        </div>

        <!-- 影片Banner圖片上傳 -->
        <div class="row">
            <!-- 使用 v-for 簡化 Banner 圖片上傳區塊 -->
            <div v-for="img in imageConfigs.slice(2)" :key="img.key" class="mb-3">
                <label class="form-label">{{ img.label }} <span class="text-danger">*</span></label>
                <div :style="`width: ${img.containerWidth};`">
                    <Slim
                        :ref="el => imageRefs[img.key] = el"
                        :label="img.slimLabel"
                        :width="img.width"
                        :height="img.height"
                        :ratio="`${img.width}:${img.height}`"
                        :initialImage="initialData?.[img.key] || ''"
                        @cleared="form[img.clearKey] = true"
                        :class="{ 'is-invalid': !!form.errors[img.key] }"
                    />
                </div>
                <div
                    v-show="form.errors[img.key]"
                    class="text-danger mt-2"
                    style="display: block !important; font-size: 0.875rem;"
                >
                    {{ form.errors[img.key] || '' }}
                </div>
            </div>
        </div>

        <!-- 多語言文字區塊 - 直接寫死避免響應式問題 -->
        <!-- 影音簡介 -->
        <div class="mb-3">
            <label class="form-label">{{ contentLabel }}簡介（中文） <span class="text-danger">*</span></label>
            <textarea
                v-model="form.description.zh_TW"
                class="form-control"
                :class="{'is-invalid': form.errors.description?.zh_TW}"
                rows="4"
                placeholder="請輸入中文描述..."
                @blur="() => validateField('description.zh_TW')"
                required
            ></textarea>
            <div v-if="form.errors.description?.zh_TW" class="invalid-feedback">
                {{ form.errors.description?.zh_TW }}
            </div>
        </div>
        
        <div class="mb-3">
            <label class="form-label">{{ contentLabel }}簡介（英文） <span class="text-danger">*</span></label>
            <textarea
                v-model="form.description.en"
                class="form-control"
                :class="{'is-invalid': form.errors.description?.en}"
                rows="4"
                placeholder="Please enter English description..."
                @blur="() => validateField('description.en')"
                required
            ></textarea>
            <div v-if="form.errors.description?.en" class="invalid-feedback">
                {{ form.errors.description?.en }}
            </div>
        </div>

        <!-- 製作團隊 -->
        <div class="mb-3">
            <label class="form-label">製作團隊（中文） <span class="text-danger">*</span></label>
            <textarea
                v-model="form.crew.zh_TW"
                class="form-control"
                :class="{'is-invalid': form.errors.crew?.zh_TW}"
                rows="3"
                placeholder="請輸入製作團隊資訊..."
                @blur="() => validateField('crew.zh_TW')"
                required
            ></textarea>
            <div v-if="form.errors.crew?.zh_TW" class="invalid-feedback">
                {{ form.errors.crew?.zh_TW }}
            </div>
        </div>
        
        <div class="mb-3">
            <label class="form-label">製作團隊（英文） <span class="text-danger">*</span></label>
            <textarea
                v-model="form.crew.en"
                class="form-control"
                :class="{'is-invalid': form.errors.crew?.en}"
                rows="3"
                placeholder="Please enter crew information..."
                @blur="() => validateField('crew.en')"
                required
            ></textarea>
            <div v-if="form.errors.crew?.en" class="invalid-feedback">
                {{ form.errors.crew?.en }}
            </div>
        </div>

        <!-- 其他資訊 -->
        <div class="mb-3">
            <label class="form-label">其他資訊（中文） <span class="text-danger">*</span></label>
            <textarea
                v-model="form.other_info.zh_TW"
                class="form-control"
                :class="{'is-invalid': form.errors.other_info?.zh_TW}"
                rows="3"
                placeholder="請輸入其他相關資訊..."
                @blur="() => validateField('other_info.zh_TW')"
                required
            ></textarea>
            <div v-if="form.errors.other_info?.zh_TW" class="invalid-feedback">
                {{ form.errors.other_info?.zh_TW }}
            </div>
        </div>
        
        <div class="mb-3">
            <label class="form-label">其他資訊（英文） <span class="text-danger">*</span></label>
            <textarea
                v-model="form.other_info.en"
                class="form-control"
                :class="{'is-invalid': form.errors.other_info?.en}"
                rows="3"
                placeholder="Please enter other information..."
                @blur="() => validateField('other_info.en')"
                required
            ></textarea>
            <div v-if="form.errors.other_info?.en" class="invalid-feedback">
                {{ form.errors.other_info?.en }}
            </div>
        </div>

        <!-- 標籤 - 使用自訂處理 -->
        <template v-for="tag in tagFields" :key="tag.key">
            <div class="mb-3">
                <label class="form-label">{{ tag.label }} <span class="text-danger">*</span></label>
                <EditableTags
                    v-model="form.tags[tag.lang]"
                    :required="true"
                    :placeholder="tag.placeholder"
                    :empty-text="tag.emptyText"
                    :max-tags="10"
                    :suggestions="tag.suggestions"
                    @update="() => validateField(`tags.${tag.lang}`)"
                />
                <div v-if="form.errors.tags?.[tag.lang]" class="text-danger mt-1" style="font-size: 0.875rem;">
                    {{ form.errors.tags?.[tag.lang] }}
                </div>
            </div>
        </template>

        <!-- 提交按鈕區域 -->
        <div class="text-end mt-4 pt-3 border-top">
            <button
                type="button"
                class="btn btn-secondary me-2"
                @click="handleBack"
            >
                <i class="fa fa-arrow-left me-1"></i>
                回上一頁
            </button>
            <button
                type="button"
                class="btn btn-primary"
                :disabled="form?.processing"
                @click="handleSubmit"
            >
                <span v-if="form?.processing">
                    <i class="fa fa-spinner fa-spin me-1"></i>
                    處理中...
                </span>
                <span v-else>
                    <i class="fa fa-save me-1"></i>
                    {{ isEditing ? `更新${contentLabel}資料` : `儲存${contentLabel}資料` }}
                </span>
            </button>
        </div>
    </div>
</template>

<script setup>
import { onBeforeUnmount, computed } from 'vue'
import DatePicker from "@/Plugin/DatePicker.vue"
import Slim from "@/Plugin/Slim.vue"
import EditableTags from "@/Plugin/EditableTags.vue"
import { useContentManagement } from '@/composables/content/useContentManagement'

// Props
const props = defineProps({
    contentType: {
        type: String,
        required: true,
        validator: (value) => ['drama', 'program'].includes(value)
    },
    categories: {
        type: Array,
        default: () => []
    },
    subcategories: {
        type: Array,
        default: () => []
    },
    initialData: {
        type: Object,
        default: null
    },
    videoSeasons: {
        type: Array,
        default: () => []
    },
    isEditing: {
        type: Boolean,
        default: false
    }
})

// Emits
const emit = defineEmits(['submit', 'back', 'season-change'])

// 使用 composable
const {
    // 配置
    currentConfig,
    langs,
    imageConfigs,
    tagFields,
    
    // 表單相關
    form,
    imageRefs,
    validator,
    
    // 計算屬性
    filteredSubcategories,
    minAllowedSeason,
    hasVideoData,
    availableSeasons,
    
    // 方法
    validateField,
    onCategoryChange,
    onSubcategoryChange,
    onSeasonChange,
    handleSubmit,
    getImageData,
    cleanupImages
} = useContentManagement(props.contentType, props)

// 計算內容標籤
const contentLabel = computed(() => {
    return props.contentType === 'drama' ? '影音' : '節目'
})

// 處理返回
const handleBack = () => {
    emit('back')
}

// 在組件銷毀前清理 Slim 組件
onBeforeUnmount(async () => {
    await cleanupImages()
})

// 暴露方法給父組件
defineExpose({
    form,
    validateField,
    handleSubmit,
    validator,
    getImageData,
})
</script>

<style scoped>
</style>