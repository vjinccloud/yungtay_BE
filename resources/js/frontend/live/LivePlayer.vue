<template>
    <div>
        <!-- 有直播資料時顯示 -->
        <template v-if="lives.length > 0">
            <!-- 標題顯示當前影片標題 -->
            <div class="block-title">
                <div class="sub-title">
                    <h2>{{ currentLive?.title || '直播頻道' }}</h2>
                </div>                         
            </div>
            
            <div class="live-feed-div">
            <div class="two-cols">
                <!-- 左側大播放區 -->
                <div class="col01">
                    <div class="video">
                        <div class="img" :style="`background-image: url(${currentLive?.thumbnail || '/frontend/images/live_feed_img_01.png'});`">
                            <iframe 
                                v-if="currentVideoId"
                                :key="currentVideoId + '-' + iframeKey"
                                id="live-iframe"
                                width="100%" 
                                height="100%" 
                                :src="iframeSrc"
                                frameborder="0" 
                                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" 
                                allowfullscreen
                                ref="iframeRef">
                            </iframe>
                            <button
                                v-if="showUnmute && currentVideoId"
                                class="unmute-overlay"
                                @click.prevent="enableSound"
                                aria-label="開啟聲音"
                            >
                                開聲音
                            </button>
                            <img v-else :src="currentLive?.thumbnail || '/frontend/images/live_feed_img_01.png'" :alt="currentLive?.title">
                        </div>    
                        <div class="info">
                        </div>
                    </div>                                   
                </div>
                
                <!-- 右側列表 -->
                <div v-if="sidebarLives.length > 0" class="col02">
                    <div class="video-list">
                        <div 
                            v-for="live in sidebarLives" 
                            :key="live.id"
                            class="item">
                            <a 
                                href="#"
                                @click.prevent="switchLive(live)">
                                <div class="img">
                                    <img 
                                        :src="live.thumbnail || '/frontend/images/live_feed_img_01.png'" 
                                        :alt="live.title">
                                </div>
                                <div class="info">
                                    <div class="desc">
                                        <h3>{{ live.title }}</h3>
                                    </div>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        </template>
        
        <!-- 沒有直播資料時顯示 -->
        <div v-else class="no-live-container" style="text-align: center; padding: 80px 20px;">
            <h3 style="color: rgb(255, 255, 255);">目前暫無直播內容</h3>
        </div>
    </div>
</template>

<script>
import { useViewRecorder } from '@/composables/frontend/useViewRecorder'

export default {
    name: 'LivePlayer',
    
    props: {
        initialLives: {
            type: Array,
            default: () => []
        },
        initialCurrentId: {
            type: Number,
            default: null
        }
    },
    
    setup() {
        // 使用觀看記錄 composable
        const { recordLiveView } = useViewRecorder()
        
        return {
            recordLiveView
        }
    },
    
    data() {
        return {
            lives: [],
            currentId: null,
            iframeKey: 0,
            isMuted: true,
            showUnmute: true
        };
    },
    
    computed: {
        // 當前播放的直播
        currentLive() {
            return this.lives.find(live => live.id === this.currentId);
        },
        
        // 從 YouTube URL 提取影片 ID
        currentVideoId() {
            if (!this.currentLive?.youtube_url) return null;
            
            const match = this.currentLive.youtube_url.match(
                /(?:youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/embed\/)([^&\n?#]+)/
            );
            return match ? match[1] : null;
        },
        // Iframe 來源（依照是否靜音）
        iframeSrc() {
            if (!this.currentVideoId) return '';
            const params = new URLSearchParams({
                autoplay: '1',
                mute: this.isMuted ? '1' : '0',
                controls: '1',
                playsinline: '1',
                enablejsapi: '1'
            });
            return `https://www.youtube.com/embed/${this.currentVideoId}?${params.toString()}`;
        },
        
        // 右側列表（排除當前播放的）
        sidebarLives() {
            return this.lives
                .filter(live => live.id !== this.currentId);
        }
    },
    
    mounted() {
        // 初始化資料
        this.lives = this.initialLives;
        this.currentId = this.initialCurrentId;

        // 若未指定當前播放，預設為第一個直播
        if (!this.currentId && this.lives.length > 0) {
            this.currentId = this.lives[0].id;
        }
        // 初次載入先靜音自動播放，顯示開聲音提示
        this.isMuted = true;
        this.showUnmute = true;
        
        // 監聽瀏覽器返回按鈕
        window.addEventListener('popstate', this.handlePopState);

        // 確保初次進入就觸發 iframe 載入
        this.bumpIframeKey();
    },
    
    beforeUnmount() {
        window.removeEventListener('popstate', this.handlePopState);
    },
    
    methods: {
        bumpIframeKey() {
            // 強制重建 iframe，並確保 pointer-events 有效
            this.iframeKey++;
            this.$nextTick(() => {
                const iframe = this.$refs.iframeRef;
                if (iframe) {
                    iframe.style.pointerEvents = 'auto';
                }
            });
        },
        enableSound() {
            // 使用者互動後，允許開聲音並重建 iframe
            this.isMuted = false;
            this.showUnmute = false;
            this.bumpIframeKey();
        },
        // 切換直播
        async switchLive(live) {
            this.currentId = live.id;
            
            // 更新 URL（不重新載入頁面）
            const newUrl = `/live/${live.id}`;
            history.pushState({ id: live.id }, '', newUrl);

            // 重新載入 iframe 以啟動播放
            this.isMuted = true; // 切換時依策略先靜音自動播放
            this.showUnmute = true;
            this.bumpIframeKey();
            
            // 記錄新的直播觀看歷史
            // 延遲 2 秒確保用戶有實際觀看意圖
            setTimeout(async () => {
                try {
                    const success = await this.recordLiveView(live.id);
                    if (success) {
                        console.log(`[LivePlayer] 已記錄直播觀看: ${live.title} (ID: ${live.id})`);
                    }
                } catch (error) {
                    // 靜默處理錯誤，不影響用戶體驗
                    console.error('[LivePlayer] 記錄觀看失敗:', error);
                }
            }, 2000);
        },
        
        // 處理瀏覽器返回
        handlePopState(event) {
            let newId = null;
            if (event.state?.id) {
                newId = event.state.id;
                this.currentId = event.state.id;
            } else {
                // 沒有 state，使用第一個直播
                if (this.lives.length > 0) {
                    newId = this.lives[0].id;
                    this.currentId = this.lives[0].id;
                }
            }
            this.bumpIframeKey();
            
            // 記錄瀏覽器返回後的直播觀看
            if (newId) {
                setTimeout(async () => {
                    try {
                        const success = await this.recordLiveView(newId);
                        if (success) {
                            console.log(`[LivePlayer] 已記錄直播觀看（瀏覽器導航）: ID ${newId}`);
                        }
                    } catch (error) {
                        console.error('[LivePlayer] 記錄觀看失敗:', error);
                    }
                }, 2000);
            }
        }
    }
};
</script>

<style scoped>
/* 保留 iframe 必要的樣式 */
.live-feed-div .col01 .video .img {
    position: relative;
}

.live-feed-div .col01 .video .img iframe {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: 2147483647 !important; /* 最大層級，確保可點擊 */
    pointer-events: auto !important; /* 允許點擊控制 */
}

/* 若主題樣式對 .img 加了 :before/:after 疊加層，避免遮擋 iframe 點擊 */
.live-feed-div .col01 .video .img::before,
.live-feed-div .col01 .video .img::after {
    pointer-events: none !important;
}

/* 保證容器成為定位上下文 */
.live-feed-div .col01 .video .img {
    position: relative;
    z-index: 0;
}

/* 其他資訊區避免攔截點擊 */
.live-feed-div .col01 .video .info {
    position: relative;
    z-index: 1;
    pointer-events: none;
}

/* 開聲音覆蓋按鈕 */
.unmute-overlay {
    position: absolute;
    top: 12px;
    left: 12px;
    z-index: 2147483647;
    background: rgba(0,0,0,0.6);
    color: #fff;
    border: 1px solid rgba(255,255,255,0.5);
    padding: 8px 12px;
    border-radius: 6px;
    cursor: pointer;
}

/* 移除滑鼠移入的放大/過渡效果（避免影響點擊） */
.live-feed-div .col01 .video .img,
.live-feed-div .col01 .video .img img,
.live-feed-div .col01 .video .img iframe {
    transition: none !important;
    transform: none !important;
}
.live-feed-div .col01 .video .img:hover,
.live-feed-div .col01 .video .img:hover img,
.live-feed-div .col01 .video .img:hover iframe {
    transform: none !important;
}
</style>