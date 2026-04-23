<!-- resources/js/InertiaPages/Admin/Shared/Content/Components/BaseVideoPlayer.vue -->
<template>
    <div 
        class="modal fade" 
        id="videoPlayerModal" 
        tabindex="-1" 
        ref="modalRef"
        aria-labelledby="videoPlayerModalLabel" 
        aria-hidden="true"
    >
        <div class="modal-dialog modal-dialog-centered" :class="`modal-${props.size}`">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="videoPlayerModalLabel">
                        {{ videoTitle }}
                    </h5>
                    <button 
                        type="button" 
                        class="btn-close" 
                        @click="closeModal" 
                        aria-label="Close"
                    ></button>
                </div>
                <div class="modal-body p-0">
                    <!-- YouTube 影片 -->
                    <div v-if="isYouTubeVideo" class="ratio ratio-16x9">
                        <iframe 
                            :key="playerKey"
                            :src="youtubeEmbedUrl"
                            allowfullscreen
                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                        ></iframe>
                    </div>
                    
                    <!-- 本機上傳影片 -->
                    <div v-else-if="isUploadedVideo" class="video-container">
                        <video 
                            :key="playerKey"
                            ref="videoPlayer"
                            class="w-100"
                            controls
                            :autoplay="autoplay"
                        >
                            <source :src="videoUrl" type="video/mp4">
                            您的瀏覽器不支援 HTML5 影片播放。
                        </video>
                    </div>
                    
                    <!-- 無影片 -->
                    <div v-else class="text-center py-5">
                        <i class="fa fa-video-slash fa-3x text-muted mb-3"></i>
                        <p class="text-muted">無法載入影片</p>
                    </div>
                </div>
                
                <!-- 影片資訊 -->
                <div class="modal-footer" v-if="video">
                    <div class="w-100">
                        <div class="row">
                            <!-- 時長資訊 - 支援多種時長欄位格式 -->
                            <div class="col-md-12" v-if="video.duration_text?.zh_TW || video.duration_text_zh">
                                <small class="text-muted">
                                    <i class="fa fa-clock me-1"></i>
                                    時長：{{ video.duration_text?.zh_TW || video.duration_text_zh }}
                                </small>
                            </div>
                        </div>
                        <div class="mt-2" v-if="video.description?.zh_TW">
                            <small class="text-muted">
                                簡介：{{ video.description.zh_TW }}
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { nextTick, onMounted, onBeforeUnmount } from 'vue'
import { useVideoPlayer } from '@/composables/video/useVideoPlayer'

// Props
const props = defineProps({
    video: {
        type: Object,
        default: null
    },
    autoplay: {
        type: Boolean,
        default: false
    },
    size: {
        type: String,
        default: 'xl',
        validator: (value) => ['sm', 'lg', 'md', 'xl'].includes(value)
    }
})

// Emits
const emit = defineEmits(['close', 'play', 'pause'])

// 使用 composable
    const {
    // Refs
    modalRef,
    videoPlayer,
        playerKey,
    
    // 計算屬性
    videoTitle,
    isYouTubeVideo,
    isUploadedVideo,
    isLiveContent,
    videoUrl,
    youtubeEmbedUrl,
    
    // 方法
    initModal,
    openModal,
    closeModal,
    disposeModal
} = useVideoPlayer(props, emit)

// 生命週期
onMounted(() => {
    initModal()
})

onBeforeUnmount(() => {
    disposeModal()
})

// 暴露方法
defineExpose({
    openModal,
    closeModal
})
</script>

<style scoped>
.modal-lg {
    max-width: 800px;
}

.modal-md {
    max-width: 1000px;
}

.video-container {
    background-color: #000;
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 300px;
}

video {
    max-height: 60vh;
    width: 100%;
    height: auto;
}

.ratio-16x9 {
    background-color: #000;
}

.btn-close {
    z-index: 1050;
    position: relative;
}
</style>