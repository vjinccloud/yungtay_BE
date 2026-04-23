<!-- resources/js/components/EpisodeList.vue -->
<template>
    <div>
        <!-- 集數列表 -->
        <div class="episode-items">
            <div class="item" v-for="episode in filteredEpisodes" :key="episode.id">
                <div class="img">
                    <a :href="getEpisodeUrl(episode.id)">
                        <img
                            :src="episode.thumbnail || defaultThumbnail"
                            :alt="getEpisodeText(episode.seq)"
                            @error="handleImageError">
                        <i></i>
                    </a>
                    <h3><b>{{ getEpisodeText(episode.seq) }}</b> <span>{{ episode.duration }}</span></h3><!--手機版-->
                </div>
                <div class="info">
                    <h3><b class="b-override">{{ getEpisodeText(episode.seq) }}</b><span>{{ episode.duration }}</span></h3><!--電腦版-->
                    <p v-html="nl2br(episode.title)" :title="episode.title"></p>
                </div>
            </div>
        </div>

        <!-- 無集數提示 -->
        <div v-if="filteredEpisodes.length === 0" class="no-episodes">
            <p>{{ texts.noEpisodes }}</p>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'

// Props
const props = defineProps({
    episodes: {
        type: Object,
        required: true
    },
    contentId: {
        type: Number,
        required: true
    },
    contentType: {
        type: String,
        default: 'drama'
    },
    totalSeasons: {
        type: Number,
        default: 1
    },
    // 保留向後相容
    dramaId: {
        type: Number,
        required: false
    },
    // 翻譯變數
    texts: {
        type: Object,
        required: true
    }
})

// Data
const selectedSeason = ref(1) // 預設選擇第一季
const isDropdownOpen = ref(false)
const defaultThumbnail = '/frontend/images/default_video.png'

// Computed
const seasons = computed(() => {
    // 從 episodes 物件取得所有季數
    return Object.keys(props.episodes).map(Number).sort((a, b) => a - b)
})

const selectedSeasonText = computed(() => {
    // 使用按鈕現有的文字，或預設為第X季
    const button = document.querySelector('#seasonSelector button span b')
    if (button) {
        return button.textContent
    }
    return `第${numberToChinese(selectedSeason.value)}季`
})

const filteredEpisodes = computed(() => {
    // 返回選定季數的集數
    return props.episodes[selectedSeason.value] || []
})

// Methods
const toggleDropdown = () => {
    isDropdownOpen.value = !isDropdownOpen.value
}

const selectSeason = (season) => {
    selectedSeason.value = season
    isDropdownOpen.value = false
    const seasonSelector = document.getElementById('seasonSelector')
    if (seasonSelector) {
        seasonSelector.querySelector('.dropdown-select')?.classList.remove('active')
    }
}

const getEpisodeUrl = (episodeId) => {
    // 使用正確的 URL 格式，支援影音和節目
    const id = props.contentId || props.dramaId // 向後相容
    const type = props.contentType || 'drama'
    return `/${type}/${id}/video/${episodeId}`
}

const getEpisodeText = (episodeNumber) => {
    // 使用翻譯變數格式化集數文字
    return props.texts.episode.replace(':number', episodeNumber)
}

const nl2br = (text) => {
    // 將換行符號轉換為 <br> 標籤
    if (!text) return ''

    // 處理各種換行符號：\r\n, \n, \r
    return text
        .replace(/\r\n/g, '<br>')  // Windows 換行
        .replace(/\n/g, '<br>')     // Unix 換行
        .replace(/\r/g, '<br>')     // 舊 Mac 換行
}

const numberToChinese = (num) => {
    const chinese = ['零', '一', '二', '三', '四', '五', '六', '七', '八', '九', '十']
    if (num <= 10) return chinese[num]
    if (num < 20) return '十' + chinese[num - 10]
    return num.toString()
}

const handleImageError = (event) => {
    // 當圖片載入失敗時，使用預設圖片
    event.target.src = defaultThumbnail
}

// Lifecycle hooks
onMounted(() => {
    // 設定預設季數為第一個可用的季數
    if (seasons.value.length > 0) {
        selectedSeason.value = seasons.value[0]
    }

    // 綁定現有的季數選擇器事件
    const seasonSelector = document.getElementById('seasonSelector')
    if (seasonSelector) {
        // 綁定選項點擊事件
        const subItems = seasonSelector.querySelectorAll('.sub-item')
        subItems.forEach((item, index) => {
            item.addEventListener('click', () => {
                // 直接從季數列表取得對應的季數
                const season = seasons.value[index]
                if (season) {
                    selectedSeason.value = season
                }

                // 更新按鈕文字
                const button = seasonSelector.querySelector('button span b')
                if (button) {
                    button.textContent = selectedSeasonText.value
                }

                // 更新 radio 狀態
                subItems.forEach((subItem, subIndex) => {
                    const radio = subItem.querySelector('input[type="radio"]')
                    if (radio) {
                        radio.checked = subIndex === index
                    }
                })
            })
        })
    }
})

onBeforeUnmount(() => {
    // 清理事件監聽
    const seasonSelector = document.getElementById('seasonSelector')
    if (seasonSelector) {
        seasonSelector.innerHTML = ''
    }
})
</script>

<style scoped>
/* 避免與全域樣式衝突，使用更具體的選擇器 */
[data-episode-list] .no-episodes {
    text-align: center;
    padding: 60px 20px;
    color: #666;
}

[data-episode-list] .episode-items {
    transition: opacity 0.3s ease;
}

/* 解決英文集數文字重疊問題 - 只處理集數部分 */
.section-video-list .episode-list-div .item .info h3 b.b-override {
    width: auto;
    min-width: 60px !important;
    max-width: 150px !important;
    margin-right: 15px !important; /* 增加與時間的間距 */
}

.section-video-list .episode-list-div .item .info h3 span {
    width: auto !important; /* 覆蓋 main.css 的 calc(100% - 80px) */
    flex: 1 !important;
}

/* 調整集數標題顯示行數從 3 行改為 5 行 */
.section-video-list .episode-list-div .item .info p {
    -webkit-line-clamp: 5 !important; /* 覆蓋 main.css 的 3 行設定 */
}
</style>