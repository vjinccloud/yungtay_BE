// resources/js/composables/video/useVideoPlayer.js
import { ref, computed, nextTick } from 'vue'
import { Modal } from 'bootstrap'

export function useVideoPlayer(props, emit) {
    // Refs
    const modalRef = ref(null)
    const videoPlayer = ref(null)
    let modalInstance = null
    const playerKey = ref(0)
    const isOpen = ref(false)

    // 計算屬性
    const videoTitle = computed(() => {
        if (!props.video) return '影片播放器'
        
        // 直播只顯示標題，不顯示第幾集
        if (props.video.content_type === 'live') {
            return props.video.title || '直播'
        }
        
        return `第 ${props.video.seq} 集` + (props.video.title ? ` - ${props.video.title}` : '')
    })

    const isYouTubeVideo = computed(() => {
        return !!props.video?.youtube_url
    })

    const isUploadedVideo = computed(() => {
        return !!props.video?.video_url && props.video?.video_type !== 'youtube'
    })

    const isLiveContent = computed(() => {
        return props.video?.content_type === 'live'
    })

    const videoUrl = computed(() => {
        if (!props.video?.video_url) return ''
        
        if (props.video.video_url.startsWith('http')) {
            return props.video.video_url
        }
        
        return `/storage/${props.video.video_url}`
    })

    const youtubeEmbedUrl = computed(() => {
        if (!isOpen.value) return ''
        if (!props.video?.youtube_url) return ''
        
        const url = props.video.youtube_url
        const patterns = [
            /(?:youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/embed\/)([^&\n?#]+)/,
            /youtube\.com\/watch\?.*v=([^&\n?#]+)/
        ]
        
        for (const pattern of patterns) {
            const match = url.match(pattern)
            if (match) {
                return `https://www.youtube.com/embed/${match[1]}?autoplay=${props.autoplay ? 1 : 0}`
            }
        }
        
        return url
    })

    // 方法
    const initModal = () => {
        if (modalRef.value && !modalInstance) {
            modalInstance = new Modal(modalRef.value, {
                backdrop: 'static',
                keyboard: true
            })
            
            // 監聽 modal 關閉事件
            modalRef.value.addEventListener('hidden.bs.modal', () => {
                // 停止影片播放
                if (videoPlayer.value) {
                    videoPlayer.value.pause()
                    videoPlayer.value.currentTime = 0
                }
                // 重置 YouTube iframe，確保關閉後停止播放並下次重新載入
                const iframe = modalRef.value?.querySelector('iframe')
                if (iframe) {
                    try { iframe.src = 'about:blank' } catch {}
                }
                // 強制下次重新渲染播放器
                playerKey.value += 1
                isOpen.value = false
                emit('close')
            })
        }
    }

    const openModal = (video = null) => {
        if (modalInstance) {
            isOpen.value = true
            modalInstance.show()
            
            // 如果是本機影片，等 modal 顯示後再播放
            if (isUploadedVideo.value && props.autoplay) {
                nextTick(() => {
                    if (videoPlayer.value) {
                        videoPlayer.value.play()
                    }
                })
            }
            
            emit('play')
        }
    }

    const closeModal = () => {
        // 停止影片播放
        if (videoPlayer.value) {
            videoPlayer.value.pause()
            videoPlayer.value.currentTime = 0
        }
        // 重置 YouTube iframe，並推進 key 以便下次強制重建節點
        if (modalRef.value) {
            const iframe = modalRef.value.querySelector('iframe')
            if (iframe) {
                try { iframe.src = 'about:blank' } catch {}
            }
        }
        playerKey.value += 1
        
        if (modalInstance) {
            modalInstance.hide()
        }
        
        isOpen.value = false
        emit('close')
    }

    const disposeModal = () => {
        if (modalInstance) {
            modalInstance.dispose()
            modalInstance = null
        }
    }

    return {
        // Refs
        modalRef,
        videoPlayer,
        playerKey,
        isOpen,
        
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
    }
}