<!-- Modules/IntroVideo/Vue/Form.vue -->
<!-- 片頭動畫設定 - 單一設定頁面 -->
<template>
    <div class="content">
        <BreadcrumbItem />

        <div class="block block-rounded">
            <div class="block-header block-header-default">
                <h3 class="block-title">片頭動畫設定</h3>
            </div>

            <div class="block-content block-content-full">
                <form @submit.prevent="submit" enctype="multipart/form-data">

                    <!-- ========== 影片上傳 ========== -->
                    <div class="mb-4">
                        <label class="form-label">
                            片頭動畫影片
                            <small class="text-muted">(僅支援 MP4 格式，最大 100MB)</small>
                            <span class="text-danger">*</span>
                        </label>

                        <!-- 現有影片預覽 -->
                        <div v-if="props.data?.video_url && !removeVideo" class="mb-3">
                            <div class="card" style="max-width: 500px;">
                                <div class="card-body p-2">
                                    <video 
                                        :src="props.data.video_url" 
                                        controls 
                                        class="w-100"
                                        style="max-height: 300px;"
                                    >
                                        您的瀏覽器不支援影片播放
                                    </video>
                                    <div class="mt-2 d-flex justify-content-between align-items-center">
                                        <div>
                                            <small class="text-muted">
                                                {{ props.data.video_original_name }}
                                                <span v-if="props.data.video_size_formatted">
                                                    ({{ props.data.video_size_formatted }})
                                                </span>
                                            </small>
                                        </div>
                                        <button 
                                            type="button" 
                                            class="btn btn-sm btn-danger"
                                            @click="handleRemoveVideo"
                                        >
                                            <i class="fa fa-trash me-1"></i> 移除影片
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 新影片預覽 -->
                        <div v-if="videoPreview" class="mb-3">
                            <div class="card" style="max-width: 500px;">
                                <div class="card-body p-2">
                                    <video 
                                        :src="videoPreview" 
                                        controls 
                                        class="w-100"
                                        style="max-height: 300px;"
                                    >
                                        您的瀏覽器不支援影片播放
                                    </video>
                                    <div class="mt-2 d-flex justify-content-between align-items-center">
                                        <div>
                                            <small class="text-muted">
                                                {{ selectedFile?.name }}
                                                <span v-if="selectedFile">
                                                    ({{ formatFileSize(selectedFile.size) }})
                                                </span>
                                            </small>
                                        </div>
                                        <button 
                                            type="button" 
                                            class="btn btn-sm btn-secondary"
                                            @click="clearSelectedFile"
                                        >
                                            <i class="fa fa-times me-1"></i> 取消選擇
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 上傳按鈕 -->
                        <div v-if="!props.data?.video_url || removeVideo || videoPreview === null">
                            <input
                                ref="fileInput"
                                type="file"
                                accept="video/mp4"
                                class="form-control"
                                :class="{ 'is-invalid': form.errors.video }"
                                @change="handleFileChange"
                                style="max-width: 500px;"
                            >
                            <div v-if="form.errors.video" class="invalid-feedback">
                                {{ form.errors.video }}
                            </div>
                            <small class="text-muted d-block mt-1">
                                請上傳 MP4 格式的影片，檔案大小不超過 100MB
                            </small>
                        </div>
                    </div>

                    <!-- ========== 啟用狀態 ========== -->
                    <div class="mb-4">
                        <label class="form-label">啟用狀態</label>
                        <div class="form-check form-switch">
                            <input
                                v-model="form.is_active"
                                type="checkbox"
                                class="form-check-input"
                                id="is_active"
                            >
                            <label class="form-check-label" for="is_active">
                                {{ form.is_active ? '啟用' : '停用' }}
                            </label>
                        </div>
                        <small class="text-muted">停用後，前台將不會顯示片頭動畫</small>
                    </div>

                    <!-- ========== 送出按鈕 ========== -->
                    <div class="mb-4">
                        <button 
                            type="submit" 
                            class="btn btn-primary"
                            :disabled="form.processing"
                        >
                            <i class="fa fa-save me-1"></i>
                            {{ form.processing ? '儲存中...' : '儲存設定' }}
                        </button>
                    </div>

                </form>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, inject } from 'vue';
import { useForm } from '@inertiajs/vue3';
import BreadcrumbItem from '@/Shared/Admin/Partials/BreadcrumbItem.vue';

const props = defineProps({
    data: Object,
    result: Object,
});

const sweetAlert = inject('$sweetAlert');

// 檔案相關
const fileInput = ref(null);
const selectedFile = ref(null);
const videoPreview = ref(null);
const removeVideo = ref(false);

// 表單
const form = useForm({
    video: null,
    is_active: props.data?.is_active ?? true,
    remove_video: false,
});

// 處理檔案選擇
const handleFileChange = (event) => {
    const file = event.target.files[0];
    
    if (file) {
        // 驗證檔案類型
        if (file.type !== 'video/mp4') {
            form.errors.video = '請上傳 MP4 格式的影片';
            sweetAlert.error({ msg: '請上傳 MP4 格式的影片' });
            if (fileInput.value) {
                fileInput.value.value = '';
            }
            return;
        }
        
        // 驗證檔案大小 (100MB)
        if (file.size > 100 * 1024 * 1024) {
            form.errors.video = '影片大小不能超過 100MB';
            sweetAlert.error({ msg: '上傳檔案不得超過100MB' });
            if (fileInput.value) {
                fileInput.value.value = '';
            }
            return;
        }
        
        selectedFile.value = file;
        form.video = file;
        form.errors.video = null;
        
        // 建立預覽 URL
        if (videoPreview.value) {
            URL.revokeObjectURL(videoPreview.value);
        }
        videoPreview.value = URL.createObjectURL(file);
        removeVideo.value = false;
    }
};

// 清除選擇的檔案
const clearSelectedFile = () => {
    selectedFile.value = null;
    form.video = null;
    
    if (videoPreview.value) {
        URL.revokeObjectURL(videoPreview.value);
        videoPreview.value = null;
    }
    
    if (fileInput.value) {
        fileInput.value.value = '';
    }
};

// 移除現有影片
const handleRemoveVideo = () => {
    removeVideo.value = true;
    form.remove_video = true;
    clearSelectedFile();
};

// 格式化檔案大小
const formatFileSize = (bytes) => {
    if (!bytes) return '';
    const units = ['B', 'KB', 'MB', 'GB'];
    let i = 0;
    while (bytes >= 1024 && i < units.length - 1) {
        bytes /= 1024;
        i++;
    }
    return bytes.toFixed(2) + ' ' + units[i];
};

// 送出表單
const submit = () => {
    form.post(route('admin.intro-video.update'), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            clearSelectedFile();
            removeVideo.value = false;
            form.remove_video = false;
        },
    });
};

// 清理資源
onUnmounted(() => {
    if (videoPreview.value) {
        URL.revokeObjectURL(videoPreview.value);
    }
});

// 初始化
onMounted(() => {
    form.is_active = props.data?.is_active ?? true;
});
</script>

<script>
import Layout from '@/Shared/Admin/Layout.vue';

export default {
    layout: Layout,
};
</script>

<style scoped>
video {
    background-color: #000;
}
</style>
