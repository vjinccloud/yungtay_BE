<template>
    <section class="section-search-result-list">
        <div class="block-div block-01">
            <div class="block-outer">
                <form @submit.prevent="handleSubmit" class="search-keyword-title">
                    <i class="search" @click="handleSubmit" style="cursor: pointer"></i>
                    <input 
                        type="text" 
                        :value="keyword"
                        @input="updateKeyword"
                        @keyup.enter="handleSubmit"
                        :placeholder="texts.placeholder"
                    >
                    <i class="remove" @click="clearSearch"></i>
                </form>    
                <div class="tab-links-outer">
                    <div class="links">
                        <a href="#" 
                           :class="{ active: activeTab === 'all' }" 
                           @click.prevent="switchTab('all')">{{ texts.tabs.all }}</a>
                        <a href="#" 
                           :class="{ active: activeTab === 'article' }" 
                           @click.prevent="switchTab('article')">{{ texts.tabs.article }}</a>
                        <a href="#" 
                           :class="{ active: activeTab === 'drama' }" 
                           @click.prevent="switchTab('drama')">{{ texts.tabs.drama }}</a>
                        <a href="#" 
                           :class="{ active: activeTab === 'program' }" 
                           @click.prevent="switchTab('program')">{{ texts.tabs.program }}</a>
                        <a href="#" 
                           :class="{ active: activeTab === 'live' }" 
                           @click.prevent="switchTab('live')">{{ texts.tabs.live }}</a>
                        <a href="#"
                           :class="{ active: activeTab === 'radio' }"
                           @click.prevent="switchTab('radio')">{{ texts.tabs.radio }}</a>
                        <a href="#"
                           :class="{ active: activeTab === 'news' }"
                           @click.prevent="switchTab('news')">{{ texts.tabs.news }}</a>
                    </div>
                </div>
            </div>
        </div>                
        
        <div class="block-div block-02">
            <div class="block-outer">
                <!-- 有關鍵字且已執行搜尋才顯示搜尋結果 -->
                <div v-if="keyword.trim() && hasSearched">
                    <!-- 全部模式 -->
                    <div v-if="activeTab === 'all'" class="filter-list-result">
                        <SearchModule 
                            content-type="article"
                            :keyword="keyword"
                            :active-tab="activeTab"
                            :texts="texts" />
                        
                        <SearchModule 
                            content-type="drama"
                            :keyword="keyword"
                            :active-tab="activeTab"
                            :texts="texts" />
                        
                        <SearchModule 
                            content-type="program"
                            :keyword="keyword"
                            :active-tab="activeTab"
                            :texts="texts" />
                        
                        <SearchModule 
                            content-type="live"
                            :keyword="keyword"
                            :active-tab="activeTab"
                            :texts="texts" />
                        
                        <SearchModule
                            content-type="radio"
                            :keyword="keyword"
                            :active-tab="activeTab"
                            :texts="texts" />

                        <SearchModule
                            content-type="news"
                            :keyword="keyword"
                            :active-tab="activeTab"
                            :texts="texts" />
                    </div>
                    
                    <!-- 單一類別模式 -->
                    <div v-else class="single-type-result filter-list-result">
                        <SearchModule 
                            :content-type="activeTab"
                            :keyword="keyword"
                            :active-tab="activeTab"
                            :texts="texts" />
                    </div>
                </div>
                
                <!-- 沒有關鍵字時不顯示任何內容 -->
                <div v-else class="no-keyword-hint">
                    
                </div>
            </div>
        </div>                 
    </section>
</template>

<script setup>
import { ref, onMounted, watch, provide, nextTick } from 'vue'
import SearchModule from './modules/SearchModule.vue'

// Props 定義
const props = defineProps({
  texts: {
    type: Object,
    required: true
  }
})

// 響應式資料
const keyword = ref('')
const activeTab = ref('all')
const searchTrigger = ref(0)
const hasSearched = ref(false) // 追蹤是否已執行搜尋

// provide 搜尋觸發器給子組件
provide('searchTrigger', searchTrigger)

// 初始化：從 URL 讀取關鍵字
onMounted(() => {
    const urlParams = new URLSearchParams(window.location.search)
    keyword.value = urlParams.get('keyword') || ''
    
    // 有關鍵字時自動點擊 "全部" tab 觸發搜尋
    if (keyword.value && keyword.value.trim()) {
        hasSearched.value = true
        setTimeout(() => {
            switchTab('all')
        }, 100)
    }
})

// 表單送出處理
const handleSubmit = (e) => {
    e.preventDefault()
    if (!keyword.value.trim()) return
    
    // 標記已執行搜尋
    hasSearched.value = true
    
    // 更新 URL
    const url = new URL(window.location)
    url.searchParams.set('keyword', keyword.value.trim())
    window.history.replaceState({}, '', url)
    
    // 觸發搜尋
    searchTrigger.value++
}

// 監聽 URL 參數變化，同步更新 keyword
const syncKeywordFromUrl = () => {
    const urlParams = new URLSearchParams(window.location.search)
    const urlKeyword = urlParams.get('keyword') || ''
    if (keyword.value !== urlKeyword) {
        keyword.value = urlKeyword
    }
}

// 監聽瀏覽器前進後退
window.addEventListener('popstate', syncKeywordFromUrl)

// 更新關鍵字（僅更新值，不觸發搜尋）
const updateKeyword = (e) => {
    keyword.value = e.target.value
}

// 監聽關鍵字變化
watch(keyword, (newValue) => {
    // 如果關鍵字被清空，重置搜尋狀態
    if (!newValue.trim()) {
        hasSearched.value = false
    }
})

// Tab 切換
const switchTab = async (tab) => {
    activeTab.value = tab
    // 等待 DOM 更新後再觸發搜尋
    await nextTick()
    // 切換 Tab 時只有在已經搜尋過的情況下才觸發新搜尋
    if (hasSearched.value) {
        searchTrigger.value++
    }
}

// 清除搜尋
const clearSearch = () => {
    keyword.value = ''
    hasSearched.value = false
    // 更新 URL，移除 keyword 參數
    const url = new URL(window.location)
    url.searchParams.delete('keyword')
    window.history.replaceState({}, '', url)
}
</script>

<style scoped>
.loading-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 3rem;
    gap: 15px;
}

.loading-dots {
    display: flex;
    align-items: center;
    justify-content: center;
}

.loading-dots span {
    display: inline-block;
    width: 12px;
    height: 12px;
    margin: 0 5px;
    background-color: #2CC0E2;
    border-radius: 50%;
    animation: dotsAnimate 1.4s ease-in-out infinite both;
}

.loading-dots span:nth-child(1) {
    animation-delay: -0.32s;
}

.loading-dots span:nth-child(2) {
    animation-delay: -0.16s;
}

.loading-dots span:nth-child(3) {
    animation-delay: 0;
}

.loading-text {
    color: #cccccc;
    font-size: 1.1rem;
}

@keyframes dotsAnimate {
    0%, 80%, 100% {
        transform: scale(0);
        opacity: 0.5;
    }
    40% {
        transform: scale(1);
        opacity: 1;
    }
}

.no-results {
    text-align: left;
    padding: 2rem;
}

.no-keyword-hint {
    text-align: left;
    padding: 2rem;
    font-size: 1.1rem;
    color: #999;
}

.search-keyword-title .remove {
    cursor: pointer;
}

.search-keyword-title .remove:hover {
    opacity: 0.7;
}

/* 搜尋關鍵字輸入框樣式 */
.search-keyword-title input {
    background: transparent;
    border: none;
    outline: none;
    font-size: 18px; /* 增大字體 */
    color: inherit;
    flex: 1;
    margin: 0 10px;
}

.search-keyword-title input::placeholder {
    color: rgba(255, 255, 255, 0.6);
}

/* 搜尋結果內容靠左對齊 */
.filter-list-result,
.single-type-result {
    text-align: left;
}
</style>