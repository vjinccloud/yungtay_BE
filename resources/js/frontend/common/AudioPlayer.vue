<template>
    <div class="custom-audio-player" :class="{ 'is-playing': isPlaying }">
        <button class="play-btn" @click="togglePlay" :aria-label="isPlaying ? '暫停' : '播放'">
            <svg v-if="!isPlaying" class="icon-play" viewBox="0 0 24 24" fill="currentColor">
                <path d="M8 5v14l11-7z"/>
            </svg>
            <svg v-else class="icon-pause" viewBox="0 0 24 24" fill="currentColor">
                <path d="M6 19h4V5H6v14zm8-14v14h4V5h-4z"/>
            </svg>
        </button>
        <div
            class="progress-container"
            ref="progressContainer"
            @mousedown="startSeek"
            @touchstart.prevent="startSeek"
        >
            <div class="progress-bar">
                <div class="progress-fill" :style="{ width: progressPercent + '%' }"></div>
                <div class="progress-thumb" :style="{ left: progressPercent + '%' }"></div>
            </div>
        </div>
        <span class="time-display">{{ currentTimeFormatted }} / {{ durationFormatted }}</span>

        <!-- 音量控制：hover 顯示滑桿 -->
        <div class="volume-control">
            <button class="volume-btn" @click="toggleMute" :aria-label="isMuted ? '取消靜音' : '靜音'">
                <svg v-if="isMuted || volume === 0" class="icon-volume" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M16.5 12c0-1.77-1.02-3.29-2.5-4.03v2.21l2.45 2.45c.03-.2.05-.41.05-.63zm2.5 0c0 .94-.2 1.82-.54 2.64l1.51 1.51C20.63 14.91 21 13.5 21 12c0-4.28-2.99-7.86-7-8.77v2.06c2.89.86 5 3.54 5 6.71zM4.27 3L3 4.27 7.73 9H3v6h4l5 5v-6.73l4.25 4.25c-.67.52-1.42.93-2.25 1.18v2.06c1.38-.31 2.63-.95 3.69-1.81L19.73 21 21 19.73l-9-9L4.27 3zM12 4L9.91 6.09 12 8.18V4z"/>
                </svg>
                <svg v-else-if="volume < 0.5" class="icon-volume" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M18.5 12c0-1.77-1.02-3.29-2.5-4.03v8.05c1.48-.73 2.5-2.25 2.5-4.02zM5 9v6h4l5 5V4L9 9H5z"/>
                </svg>
                <svg v-else class="icon-volume" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M3 9v6h4l5 5V4L7 9H3zm13.5 3c0-1.77-1.02-3.29-2.5-4.03v8.05c1.48-.73 2.5-2.25 2.5-4.02zM14 3.23v2.06c2.89.86 5 3.54 5 6.71s-2.11 5.85-5 6.71v2.06c4.01-.91 7-4.49 7-8.77s-2.99-7.86-7-8.77z"/>
                </svg>
            </button>
            <div class="volume-slider-wrapper">
                <input
                    type="range"
                    class="volume-slider"
                    min="0"
                    max="1"
                    step="0.05"
                    :value="volume"
                    @input="setVolume"
                />
            </div>
        </div>

        <audio
            ref="audioEl"
            :src="src"
            @timeupdate="onTimeUpdate"
            @loadedmetadata="onLoadedMetadata"
            @ended="onEnded"
            @play="onPlay"
            @pause="onPause"
            preload="metadata"
        ></audio>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'

const props = defineProps({
    src: {
        type: String,
        required: true
    },
    episodeId: {
        type: [Number, String],
        default: null
    }
})

// Refs
const audioEl = ref(null)
const progressContainer = ref(null)
const isPlaying = ref(false)
const currentTime = ref(0)
const duration = ref(0)
const volume = ref(1)
const isMuted = ref(false)
const previousVolume = ref(1)
const isDragging = ref(false)

// Computed
const progressPercent = computed(() => {
    if (duration.value === 0) return 0
    return (currentTime.value / duration.value) * 100
})

const currentTimeFormatted = computed(() => {
    return formatTime(currentTime.value)
})

const durationFormatted = computed(() => {
    return formatTime(duration.value)
})

// Methods
const formatTime = (seconds) => {
    const mins = Math.floor(seconds / 60)
    const secs = Math.floor(seconds % 60)
    return `${mins}:${secs.toString().padStart(2, '0')}`
}

const togglePlay = () => {
    if (!audioEl.value) return

    if (isPlaying.value) {
        audioEl.value.pause()
    } else {
        // 暫停其他播放器
        document.querySelectorAll('.custom-audio-player audio').forEach(audio => {
            if (audio !== audioEl.value) {
                audio.pause()
            }
        })
        audioEl.value.play()
    }
}

// 進度條拖曳功能
const getSeekPosition = (event) => {
    if (!progressContainer.value || duration.value === 0) return null

    const rect = progressContainer.value.getBoundingClientRect()
    let clientX

    // 支援滑鼠和觸控
    if (event.touches && event.touches.length > 0) {
        clientX = event.touches[0].clientX
    } else if (event.changedTouches && event.changedTouches.length > 0) {
        clientX = event.changedTouches[0].clientX
    } else {
        clientX = event.clientX
    }

    const clickX = Math.max(0, Math.min(clientX - rect.left, rect.width))
    const percent = clickX / rect.width
    return percent * duration.value
}

const updateSeekPosition = (event) => {
    const newTime = getSeekPosition(event)
    if (newTime === null) return

    // 立即更新 UI
    currentTime.value = newTime
}

const startSeek = (event) => {
    if (!audioEl.value || duration.value === 0) return

    isDragging.value = true
    updateSeekPosition(event)

    // 綁定全域事件
    document.addEventListener('mousemove', onSeekMove)
    document.addEventListener('mouseup', endSeek)
    document.addEventListener('touchmove', onSeekMove, { passive: false })
    document.addEventListener('touchend', endSeek)
}

const onSeekMove = (event) => {
    if (!isDragging.value) return
    event.preventDefault()
    updateSeekPosition(event)
}

const endSeek = (event) => {
    if (!isDragging.value) return

    isDragging.value = false

    // 設定實際播放位置
    const newTime = getSeekPosition(event)
    if (newTime !== null && audioEl.value) {
        audioEl.value.currentTime = newTime
    }

    // 移除全域事件
    document.removeEventListener('mousemove', onSeekMove)
    document.removeEventListener('mouseup', endSeek)
    document.removeEventListener('touchmove', onSeekMove)
    document.removeEventListener('touchend', endSeek)
}

const setVolume = (event) => {
    const newVolume = parseFloat(event.target.value)
    volume.value = newVolume
    if (audioEl.value) {
        audioEl.value.volume = newVolume
    }
    isMuted.value = newVolume === 0
}

const toggleMute = () => {
    if (!audioEl.value) return

    if (isMuted.value) {
        // 取消靜音，恢復之前的音量
        volume.value = previousVolume.value || 1
        audioEl.value.volume = volume.value
        audioEl.value.muted = false
        isMuted.value = false
    } else {
        // 靜音，記住當前音量
        previousVolume.value = volume.value > 0 ? volume.value : 1
        volume.value = 0
        audioEl.value.volume = 0
        audioEl.value.muted = true
        isMuted.value = true
    }
}

const onTimeUpdate = () => {
    if (audioEl.value) {
        currentTime.value = audioEl.value.currentTime
    }
}

const onLoadedMetadata = () => {
    if (audioEl.value) {
        duration.value = audioEl.value.duration
    }
}

const onEnded = () => {
    isPlaying.value = false
    currentTime.value = 0
}

const onPlay = () => {
    isPlaying.value = true
}

const onPause = () => {
    isPlaying.value = false
}

// 全域播放控制：播放一個時暫停其他
const handleGlobalPlay = (e) => {
    if (e.target.tagName === 'AUDIO' && e.target !== audioEl.value) {
        if (audioEl.value && !audioEl.value.paused) {
            audioEl.value.pause()
        }
    }
}

onMounted(() => {
    document.addEventListener('play', handleGlobalPlay, true)
})

onBeforeUnmount(() => {
    document.removeEventListener('play', handleGlobalPlay, true)
})
</script>

<style scoped>
.custom-audio-player {
    display: flex;
    align-items: center;
    gap: 12px;
    background: linear-gradient(135deg, #4a5568 0%, #2d3748 100%);
    border-radius: 50px;
    padding: 10px 16px;
    min-width: 200px;
    max-width: 100%;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
}

.play-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    min-width: 32px;
    border: none;
    background: transparent;
    color: #fff;
    cursor: pointer;
    padding: 0;
    transition: transform 0.15s ease;
}

.play-btn:hover {
    transform: scale(1.1);
}

.play-btn:active {
    transform: scale(0.95);
}

.icon-play,
.icon-pause {
    width: 20px;
    height: 20px;
}

.progress-container {
    flex: 1;
    cursor: pointer;
    padding: 8px 0;
}

.progress-bar {
    position: relative;
    height: 4px;
    background: rgba(255, 255, 255, 0.3);
    border-radius: 2px;
}

.progress-fill {
    height: 100%;
    background: #fff;
    border-radius: 2px;
    transition: width 0.05s linear;
}

/* 拖曳把手 */
.progress-thumb {
    position: absolute;
    top: 50%;
    width: 12px;
    height: 12px;
    background: #fff;
    border-radius: 50%;
    transform: translate(-50%, -50%);
    opacity: 0;
    transition: opacity 0.15s ease;
    pointer-events: none;
}

.progress-container:hover .progress-thumb,
.custom-audio-player.is-dragging .progress-thumb {
    opacity: 1;
}

.time-display {
    color: #fff;
    font-size: 13px;
    font-family: 'SF Mono', 'Monaco', 'Consolas', monospace;
    min-width: 40px;
    text-align: right;
    opacity: 0.9;
}

/* 音量控制 */
.volume-control {
    display: flex;
    align-items: center;
}

.volume-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 24px;
    height: 24px;
    min-width: 24px;
    border: none;
    background: transparent;
    color: #fff;
    cursor: pointer;
    padding: 0;
    transition: opacity 0.15s ease;
}

.volume-btn:hover {
    opacity: 0.8;
}

.icon-volume {
    width: 16px;
    height: 16px;
}

/* 音量滑桿容器 - hover 展開 */
.volume-slider-wrapper {
    width: 0;
    overflow: hidden;
    transition: width 0.2s ease, margin 0.2s ease;
    display: flex;
    align-items: center;
}

.volume-control:hover .volume-slider-wrapper {
    width: 50px;
    margin-left: 4px;
}

/* 水平音量滑桿 */
.volume-slider {
    -webkit-appearance: none;
    appearance: none;
    width: 50px;
    height: 4px;
    background: rgba(255, 255, 255, 0.3);
    border-radius: 2px;
    outline: none;
    cursor: pointer;
}

.volume-slider::-webkit-slider-thumb {
    -webkit-appearance: none;
    appearance: none;
    width: 12px;
    height: 12px;
    background: #fff;
    border-radius: 50%;
    cursor: pointer;
    transition: transform 0.15s ease;
}

.volume-slider::-webkit-slider-thumb:hover {
    transform: scale(1.2);
}

.volume-slider::-moz-range-thumb {
    width: 12px;
    height: 12px;
    background: #fff;
    border-radius: 50%;
    cursor: pointer;
    border: none;
}

/* 播放中狀態 */
.custom-audio-player.is-playing {
    background: linear-gradient(135deg, #5a6578 0%, #3d4758 100%);
}

/* 隱藏原生 audio */
audio {
    display: none;
}

/* RWD */
@media (max-width: 768px) {
    .custom-audio-player {
        padding: 8px 12px;
        gap: 10px;
    }

    .play-btn {
        width: 28px;
        height: 28px;
        min-width: 28px;
    }

    .icon-play,
    .icon-pause {
        width: 18px;
        height: 18px;
    }

    .time-display {
        font-size: 12px;
    }

    .volume-btn {
        width: 24px;
        height: 24px;
        min-width: 24px;
    }

    .icon-volume {
        width: 16px;
        height: 16px;
    }

    /* 手機版隱藏音量滑桿（保留靜音按鈕） */
    .volume-slider-wrapper {
        display: none !important;
    }
}
</style>