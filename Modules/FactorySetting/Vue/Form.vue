<!-- Modules/FactorySetting/Vue/Form.vue -->
<!-- 工廠設定 - 編輯頁面（中英文媒體檔案） -->
<template>
    <div class="content">
        <BreadcrumbItem />

        <form @submit.prevent="submit">
            <div class="block block-rounded">
                <div class="block-header block-header-default">
                    <h3 class="block-title">
                        編輯工廠：{{ item?.name?.zh_TW || '' }}
                    </h3>
                    <div class="block-options">
                        <span v-if="item?.region?.name?.zh_TW" class="badge bg-info me-2">
                            {{ item.region.name.zh_TW }}
                        </span>
                    </div>
                </div>

                <div class="block-content">
                    <!-- 基本資訊 -->
                    <h5 class="mb-3 border-bottom pb-2">
                        <i class="fa fa-info-circle me-2"></i>基本資訊
                    </h5>
                    
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label class="form-label">名稱（中文）<span class="text-danger">*</span></label>
                            <input type="text" v-model="form.name.zh_TW" class="form-control" required>
                            <div v-if="errors['name.zh_TW']" class="text-danger small mt-1">{{ errors['name.zh_TW'] }}</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">名稱（英文）<span class="text-danger">*</span></label>
                            <input type="text" v-model="form.name.en" class="form-control" required>
                            <div v-if="errors['name.en']" class="text-danger small mt-1">{{ errors['name.en'] }}</div>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label class="form-label">地址（中文）</label>
                            <input type="text" v-model="form.address.zh_TW" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">地址（英文）</label>
                            <input type="text" v-model="form.address.en" class="form-control">
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label class="form-label">國家名（中文）</label>
                            <input type="text" v-model="form.country_name.zh_TW" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">國家名（英文）</label>
                            <input type="text" v-model="form.country_name.en" class="form-control">
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-4">
                            <label class="form-label">成立日期</label>
                            <input type="month" v-model="form.established_date" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">排序</label>
                            <input type="number" v-model="form.sort" class="form-control" min="0">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">狀態</label>
                            <div class="form-check form-switch mt-2">
                                <input 
                                    type="checkbox" 
                                    class="form-check-input" 
                                    id="is_enabled"
                                    v-model="form.is_enabled"
                                >
                                <label class="form-check-label" for="is_enabled">
                                    {{ form.is_enabled ? '啟用' : '停用' }}
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- 主圖設定 -->
                    <h5 class="mb-3 border-bottom pb-2 mt-5">
                        <i class="fa fa-image me-2"></i>廠房背景圖設定
                    </h5>

                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label class="form-label">廠房背景圖設定（中文版）</label>
                            <Slim
                                ref="slimImageZh"
                                :ratio="'16:9'"
                                :width="1920"
                                :height="380"
                                :initialImage="props.item?.image_zh || ''"
                                @cleared="imageZhCleared = true"
                                class="slim-cropper-container"
                            />
                            <small class="text-muted">建議尺寸：1920 x 380</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">廠房背景圖設定（英文版）</label>
                            <Slim
                                ref="slimImageEn"
                                :ratio="'16:9'"
                                :width="1920"
                                :height="380"
                                :initialImage="props.item?.image_en || ''"
                                @cleared="imageEnCleared = true"
                                class="slim-cropper-container"
                            />
                            <small class="text-muted">建議尺寸：1920 x 380</small>
                        </div>
                    </div>

                    <!-- Logo 設定 -->
                    <h5 class="mb-3 border-bottom pb-2 mt-5">
                        <i class="fa fa-star me-2"></i>年產能資訊圖表設定
                    </h5>

                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label class="form-label">年產能資訊圖表設定（中文版）</label>
                            <Slim
                                ref="slimLogoZh"
                                :ratio="'1:1'"
                                :width="768"
                                :height="572"
                                :initialImage="props.item?.logo_zh || ''"
                                @cleared="logoZhCleared = true"
                                class="slim-cropper-container"
                            />
                            <small class="text-muted">建議尺寸：768 x 572</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">年產能資訊圖表設定（英文版）</label>
                            <Slim
                                ref="slimLogoEn"
                                :ratio="'1:1'"
                                :width="768"
                                :height="572"
                                :initialImage="props.item?.logo_en || ''"
                                @cleared="logoEnCleared = true"
                                class="slim-cropper-container"
                            />
                            <small class="text-muted">建議尺寸：768 x 572</small>
                        </div>
                    </div>

                    <!-- 多張圖片 -->
                    <h5 class="mb-3 border-bottom pb-2 mt-5">
                        <i class="fa fa-images me-2"></i>工廠圖片設定（最多5張）
                    </h5>

                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label class="form-label">工廠圖片設定（中文版）</label>
                            <MultiImageUploader
                                v-model="form.images_zh"
                                :limit="5"
                                :uploadUrl="route('admin.uploads.tmp.upload') + '?upload_type=image'"
                                emptyText="尚無中文版圖片"
                            />
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">工廠圖片設定（英文版）</label>
                            <MultiImageUploader
                                v-model="form.images_en"
                                :limit="5"
                                :uploadUrl="route('admin.uploads.tmp.upload') + '?upload_type=image'"
                                emptyText="尚無英文版圖片"
                            />
                        </div>
                    </div>

                    <!-- 影片設定 -->
                    <h5 class="mb-3 border-bottom pb-2 mt-5">
                        <i class="fa fa-video me-2"></i>訪廠影片
                    </h5>

                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label class="form-label">訪廠影片（中文版）</label>
                            <VideoUploader
                                ref="visitVideoUploaderZh"
                                :uploadUrl="route('admin.uploads.tmp.upload')"
                                :removeUrl="route('admin.uploads.tmp.remove')"
                                :limit="1"
                                :maxSize="500"
                                :isEditing="!!props.item?.visit_video_zh"
                                :initialVideoUrl="props.item?.visit_video_zh || ''"
                                :initialFileName="getFileName(props.item?.visit_video_zh)"
                                @uploaded="(data) => form.visit_video_zh = data.url"
                                @removed="() => form.visit_video_zh = ''"
                            />
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">訪廠影片（英文版）</label>
                            <VideoUploader
                                ref="visitVideoUploaderEn"
                                :uploadUrl="route('admin.uploads.tmp.upload')"
                                :removeUrl="route('admin.uploads.tmp.remove')"
                                :limit="1"
                                :maxSize="500"
                                :isEditing="!!props.item?.visit_video_en"
                                :initialVideoUrl="props.item?.visit_video_en || ''"
                                :initialFileName="getFileName(props.item?.visit_video_en)"
                                @uploaded="(data) => form.visit_video_en = data.url"
                                @removed="() => form.visit_video_en = ''"
                            />
                        </div>
                    </div>

                    <!-- 360 影片 -->
                    <h5 class="mb-3 border-bottom pb-2 mt-5">
                        <i class="fa fa-vr-cardboard me-2"></i>360 影片
                    </h5>

                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label class="form-label">360 影片（中文版）</label>
                            <VideoUploader
                                ref="video360UploaderZh"
                                :uploadUrl="route('admin.uploads.tmp.upload')"
                                :removeUrl="route('admin.uploads.tmp.remove')"
                                :limit="1"
                                :maxSize="500"
                                :isEditing="!!props.item?.video_360_zh"
                                :initialVideoUrl="props.item?.video_360_zh || ''"
                                :initialFileName="getFileName(props.item?.video_360_zh)"
                                @uploaded="(data) => form.video_360_zh = data.url"
                                @removed="() => form.video_360_zh = ''"
                            />
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">360 影片（英文版）</label>
                            <VideoUploader
                                ref="video360UploaderEn"
                                :uploadUrl="route('admin.uploads.tmp.upload')"
                                :removeUrl="route('admin.uploads.tmp.remove')"
                                :limit="1"
                                :maxSize="500"
                                :isEditing="!!props.item?.video_360_en"
                                :initialVideoUrl="props.item?.video_360_en || ''"
                                :initialFileName="getFileName(props.item?.video_360_en)"
                                @uploaded="(data) => form.video_360_en = data.url"
                                @removed="() => form.video_360_en = ''"
                            />
                        </div>
                    </div>
                </div>

                <div class="block-content block-content-full bg-body-light">
                    <div class="row">
                        <div class="col-12">
                            <Link 
                                :href="route('admin.factory-settings.index')" 
                                class="btn btn-alt-secondary me-2"
                            >
                                <i class="fa fa-arrow-left me-1"></i> 返回列表
                            </Link>
                            <button type="submit" class="btn btn-primary" :disabled="isSubmitting">
                                <i class="fa fa-save me-1"></i> 
                                {{ isSubmitting ? '儲存中...' : '儲存' }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</template>

<script setup>
import { ref, reactive, computed, inject } from 'vue'
import { Link, router, usePage } from '@inertiajs/vue3'
import Layout from "@/Shared/Admin/Layout.vue";
import BreadcrumbItem from "@/Shared/Admin/Partials/BreadcrumbItem.vue";
import Slim from "@/Plugin/Slim.vue";
import VideoUploader from "@/Plugin/VideoUploader.vue";
import MultiImageUploader from "@/Plugin/MultiImageUploader.vue";
import { getSlimValue } from '@/utils';

const props = defineProps({
    item: {
        type: Object,
        default: () => ({})
    }
});

const page = usePage();
const errors = computed(() => page.props.errors || {});
const isLoading = inject('isLoading');

const isSubmitting = ref(false);

// Slim 圖片參考
const slimImageZh = ref(null);
const slimImageEn = ref(null);
const slimLogoZh = ref(null);
const slimLogoEn = ref(null);
const imageZhCleared = ref(false);
const imageEnCleared = ref(false);
const logoZhCleared = ref(false);
const logoEnCleared = ref(false);

// 取得預設國家名稱（如果為空，則使用區域名稱）
const getDefaultCountryName = (locale) => {
    // 如果已有國家名稱，使用原有值
    if (props.item?.country_name?.[locale]) {
        return props.item.country_name[locale];
    }
    // 否則使用區域名稱作為預設值
    return props.item?.region?.name?.[locale] || '';
};

// 表單資料
const form = reactive({
    name: {
        zh_TW: props.item?.name?.zh_TW || '',
        en: props.item?.name?.en || '',
    },
    address: {
        zh_TW: props.item?.address?.zh_TW || '',
        en: props.item?.address?.en || '',
    },
    country_name: {
        zh_TW: getDefaultCountryName('zh_TW'),
        en: getDefaultCountryName('en'),
    },
    established_date: props.item?.established_date || '',
    // Slim 圖片資料
    slim_image_zh: null,
    slim_image_en: null,
    slim_logo_zh: null,
    slim_logo_en: null,
    image_zh_cleared: false,
    image_en_cleared: false,
    logo_zh_cleared: false,
    logo_en_cleared: false,
    // 多張圖片 - 使用淺拷貝避免直接修改 props
    images_zh: [...(props.item?.images_zh || [])],
    images_en: [...(props.item?.images_en || [])],
    // 影片
    visit_video_zh: props.item?.visit_video_zh || '',
    visit_video_en: props.item?.visit_video_en || '',
    video_360_zh: props.item?.video_360_zh || '',
    video_360_en: props.item?.video_360_en || '',
    // 其他
    sort: props.item?.sort || 0,
    is_enabled: props.item?.is_enabled ?? true,
});

// 從 URL 取得檔案名稱
const getFileName = (url) => {
    if (!url) return '';
    return url.split('/').pop() || '';
};

// 提交表單
const submit = () => {
    if (isSubmitting.value) return;
    
    isSubmitting.value = true;
    isLoading.value = true;

    // 取得 Slim 圖片資料
    form.slim_image_zh = getSlimValue(slimImageZh.value);
    form.slim_image_en = getSlimValue(slimImageEn.value);
    form.slim_logo_zh = getSlimValue(slimLogoZh.value);
    form.slim_logo_en = getSlimValue(slimLogoEn.value);
    form.image_zh_cleared = imageZhCleared.value;
    form.image_en_cleared = imageEnCleared.value;
    form.logo_zh_cleared = logoZhCleared.value;
    form.logo_en_cleared = logoEnCleared.value;

    // images_zh / images_en 已經在 form 裡面即時更新，不需要額外處理
    console.log('Submitting images_zh:', form.images_zh);
    console.log('Submitting images_en:', form.images_en);

    router.put(route('admin.factory-settings.update', props.item.id), form, {
        onFinish: () => {
            isSubmitting.value = false;
            isLoading.value = false;
        },
        onError: () => {
            isSubmitting.value = false;
            isLoading.value = false;
        }
    });
};

defineOptions({
    layout: Layout
});
</script>

<style scoped>
.slim-cropper-container {
    max-width: 400px;
}
</style>
