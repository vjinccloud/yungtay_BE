<template>
    <div class="radio-episode-list">
        <!-- 集數列表 -->
        <div class="episode-items">
            <div class="item" v-for="episode in filteredEpisodes" :key="episode.id">
                <div class="info">
                    <h3>
                        <b>{{ getEpisodeText(episode.episode_number) }}</b>
                        <span>{{ episode.duration_text || '' }}</span>
                    </h3>
                    <p v-if="episode.description">{{ episode.description }}</p>
                </div>
                <div class="audio" v-if="episode.audio_url">
                    <AudioPlayer
                        :src="episode.audio_url"
                        :episode-id="episode.id"
                    />
                </div>
                <div class="audio no-audio" v-else>
                    <span>{{ texts.noAudio }}</span>
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
import { ref, computed, onMounted, watch } from 'vue'
import AudioPlayer from '@/frontend/common/AudioPlayer.vue'

// Props
const props = defineProps({
    episodes: {
        type: Object,
        required: true
    },
    radioId: {
        type: Number,
        required: true
    },
    totalSeasons: {
        type: Number,
        default: 1
    },
    seasons: {
        type: Array,
        default: () => []
    },
    texts: {
        type: Object,
        default: () => ({
            episode: '第 :number 集',
            noEpisodes: '目前沒有集數',
            noAudio: '暫無音檔',
            audioNotSupported: '您的瀏覽器不支援音訊播放'
        })
    }
})

// Data
const selectedSeason = ref(1)

// Computed
const availableSeasons = computed(() => {
    // 從 props.seasons 或從 episodes 物件取得所有季數
    if (props.seasons && props.seasons.length > 0) {
        return props.seasons
    }
    return Object.keys(props.episodes).map(Number).sort((a, b) => a - b)
})

const filteredEpisodes = computed(() => {
    // 返回選定季數的集數，並依 episode_number 升序排列
    const episodes = props.episodes[selectedSeason.value] || []
    return [...episodes].sort((a, b) => a.episode_number - b.episode_number)
})

// Methods
const selectSeason = (season) => {
    selectedSeason.value = season
}

const getEpisodeText = (episodeNumber) => {
    // 使用翻譯變數格式化集數文字
    return props.texts.episode.replace(':number', episodeNumber)
}

// Lifecycle hooks
onMounted(() => {
    // 設定預設季數為第一個可用的季數
    if (availableSeasons.value.length > 0) {
        selectedSeason.value = availableSeasons.value[0]
    }

    // 綁定現有的季數選擇器事件（與 Blade 中的下拉選單互動）
    const seasonSelector = document.getElementById('seasonSelector')
    if (seasonSelector) {
        const subItems = seasonSelector.querySelectorAll('.sub-item')
        subItems.forEach((item, index) => {
            item.addEventListener('click', () => {
                const season = availableSeasons.value[index]
                if (season !== undefined) {
                    selectSeason(season)
                }
            })
        })
    }

    // 單一音檔播放控制：播放一個時暫停其他
    document.addEventListener('play', (e) => {
        if (e.target.tagName === 'AUDIO') {
            const audios = document.querySelectorAll('.radio-episode-list audio')
            audios.forEach(audio => {
                if (audio !== e.target) {
                    audio.pause()
                }
            })
        }
    }, true)
})

// 監聽 selectedSeason 變化，同步更新 UI
watch(selectedSeason, (newSeason) => {
    const seasonSelector = document.getElementById('seasonSelector')
    if (seasonSelector) {
        // 更新按鈕文字
        const button = seasonSelector.querySelector('button span b')
        if (button) {
            button.textContent = props.texts.seasonLabel
                ? props.texts.seasonLabel.replace(':number', newSeason)
                : `第${newSeason}季`
        }

        // 更新 radio 狀態
        const subItems = seasonSelector.querySelectorAll('.sub-item')
        subItems.forEach((subItem, index) => {
            const radio = subItem.querySelector('input[type="radio"]')
            if (radio) {
                radio.checked = availableSeasons.value[index] === newSeason
            }
        })
    }
})
</script>

<style scoped>
.radio-episode-list {
    width: 100%;
}

.no-episodes {
    text-align: center;
    padding: 60px 20px;
    color: #999;
}

.no-audio {
    display: flex;
    align-items: center;
    justify-content: center;
    color: #666;
    font-size: 14px;
}

/* 單一音檔播放控制提示 */
.episode-items .item {
    transition: background-color 0.3s ease;
}
</style>