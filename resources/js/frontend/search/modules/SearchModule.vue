<!-- resources/js/frontend/search/modules/SearchModule.vue -->
<template>
  <div class="list-item" :class="{ 'single-mode': activeTab !== 'all' }">
    <!-- 標題區塊 -->
    <div class="block-title">
      <div class="sub-title">
        <h2>{{ getTypeTitle(contentType) }}</h2>
      </div>
      <div class="more"></div>
    </div>

    <!-- 載入狀態 -->
    <div v-if="loading" class="loading-state">
      <div class="loading-dots">
        <span></span>
        <span></span>
        <span></span>
      </div>
    </div>

    <!-- 空狀態 -->
    <div v-else-if="isEmpty" class="empty-state">
      <p>{{ texts.noResults }}</p>
    </div>

    <!-- 新聞模板 & 最新消息模板 -->
    <div v-else-if="contentType === 'article' || contentType === 'news'" class="news-list" :class="{ 'fade-in': contentLoaded }">
      <div 
        v-for="item in displayData" 
        :key="item.id" 
        class="item">
        <a :href="item.url || '#'">
          <div class="img">
            <img 
              :src="item.image && item.image.trim() 
                ? (item.image.startsWith('http') ? item.image : asset(item.image.replace(/^\//, '')))
                : asset('frontend/images/default.webp')" 
              :alt="item.title"
              @error="handleImageError"
              @load="handleImageLoad"
              loading="lazy">
          </div>
          <div class="info">
            <div class="datetime">{{ item.publish_date || '' }}</div>
            <div class="desc">
              <h3>{{ item.title }}</h3>
            </div>
          </div>
        </a>
      </div>
    </div>

    <!-- 廣播模板 -->
    <div v-else-if="contentType === 'radio'" class="radio-list" :class="{ 'fade-in': contentLoaded }">
      <div 
        v-for="item in displayData" 
        :key="item.id" 
        class="item">
        <a :href="item.url || '#'">
          <div class="img">
            <img 
              :src="item.image || asset('frontend/images/default.webp')" 
              :alt="item.title"
              @error="handleImageError"
              @load="handleImageLoad"
              loading="lazy">
          </div>
          <div class="info">
            <div class="program">
              <h3>{{ item.title }}</h3>
              <p v-if="item.media_name">{{ item.media_name }}</p>
              <p v-else-if="item.description">{{ item.description }}</p>
            </div>
          </div>
        </a>
      </div>
    </div>

    <!-- 影音/節目/直播模板 -->
    <div v-else class="video-list" :class="{ 'fade-in': contentLoaded }">
      <div 
        v-for="item in displayData" 
        :key="item.id" 
        class="item">
        <a :href="item.url || '#'">
          <div class="img">
            <img 
              :src="resolveImageUrl(getVideoThumbnail(item))" 
              :alt="item.title"
              @error="handleImageError"
              @load="handleImageLoad"
              loading="lazy">
          </div>
          <div class="info">
            <div class="program">
              <h3>{{ item.title }}</h3>
            </div>
          </div>
        </a>
      </div>
    </div>

    <!-- 分頁組件 - 使用穩定的分頁資訊避免重新渲染 -->
    <Pagination
      v-if="stablePaginationInfo && stablePaginationInfo.last_page > 1"
      :current-page="currentPage"
      :total-pages="stablePaginationInfo.last_page"
      @page-change="handlePageChange" />
  </div>
</template>

<script setup>
import { ref, computed, toRefs, watch, inject, nextTick } from 'vue'
import Pagination from '../../common/Pagination.vue'

// Props 定義
const props = defineProps({
  contentType: {
    type: String,
    required: true,
    validator: (value) => ['article', 'drama', 'program', 'live', 'radio', 'news'].includes(value)
  },
  keyword: {
    type: String,
    default: ''
  },
  activeTab: {
    type: String,
    default: 'all'
  },
  texts: {
    type: Object,
    required: true
  },
})

// 事件定義
const emit = defineEmits(['page-change'])

// 注入
const asset = inject('asset')
const $http = inject('$http')
const searchTrigger = inject('searchTrigger', ref(0))

// 將 props 轉換為響應式引用
const { keyword, activeTab } = toRefs(props)

// 本地狀態
const loading = ref(false)
const searchData = ref(null)
const currentPage = ref(1)
const contentLoaded = ref(false)

// 計算屬性
const displayData = computed(() => searchData.value?.data || [])
const paginationInfo = computed(() => searchData.value ? {
  current_page: searchData.value.current_page || 1,
  last_page: searchData.value.last_page || 1,
  total: searchData.value.total || 0
} : null)

// 穩定的分頁資訊，不會因搜尋而重置
const stablePaginationInfo = ref(null)
const isEmpty = computed(() => !loading.value && (!displayData.value || displayData.value.length === 0))
const hasData = computed(() => displayData.value && displayData.value.length > 0)

// 判斷是否需要搜尋
const shouldSearch = computed(() => {
  // 必須有關鍵字
  if (!keyword.value || !keyword.value.trim()) return false
  
  // 全部模式：每個模組都搜尋
  if (activeTab.value === 'all') return true
  
  // 單一模式：只有當前 tab 對應的模組才搜尋
  return activeTab.value === props.contentType
})

// 執行搜尋
const performSearch = async (page = 1) => {
  if (!shouldSearch.value) {
    searchData.value = null
    contentLoaded.value = false
    return
  }
  
  loading.value = true
  contentLoaded.value = false
  
  // 保存當前分頁資訊到穩定的變數
  if (paginationInfo.value) {
    stablePaginationInfo.value = { ...paginationInfo.value }
  }
  
  try {
    // 決定每頁數量
    const mode = activeTab.value === 'all' ? 'all' : 'single'
    const perPage = mode === 'single' 
      ? (props.contentType === 'radio' ? 20 : 16)
      : (props.contentType === 'radio' ? 10 : 8)
    
    // API 請求和最小載入時間
    const [response] = await Promise.all([
      $http.get(`/api/v1/search/${props.contentType}`, {
        params: {
          keyword: keyword.value.trim(),
          page: page,
          mode: mode,
          per_page: perPage
        }
      }),
      // 最小顯示時間 800ms
      new Promise(resolve => setTimeout(resolve, 800))
    ])
    
    if (response.data.status) {
      searchData.value = response.data.data
      // 移除這裡的 currentPage 設定，因為已在 handlePageChange 中立即更新
      
      // 更新穩定的分頁資訊
      stablePaginationInfo.value = {
        current_page: response.data.data.current_page || 1,
        last_page: response.data.data.last_page || 1,
        total: response.data.data.total || 0
      }
      
      // 資料載入完成後，延遲淡入
      nextTick(() => {
        setTimeout(() => {
          contentLoaded.value = true
        }, 50)
      })
    }
  } catch (error) {
    console.error(`${props.contentType} 搜尋失敗:`, error)
    searchData.value = null
  } finally {
    loading.value = false
  }
}

// 監聽搜尋觸發
watch(searchTrigger, (newValue) => {
  currentPage.value = 1
  performSearch(1)
}, { immediate: false })

// 監聽 activeTab 變化
watch(activeTab, () => {
  currentPage.value = 1
  performSearch(1)
})

// 移除自動初始搜尋，改為由 searchTrigger 控制

// 處理分頁
const handlePageChange = (page) => {
  // 立即更新當前頁碼（按鈕立即顯示 active）
  currentPage.value = page
  
  // 記住當前滾動位置
  const currentScrollY = window.scrollY
  
  performSearch(page)
  
  // 保持滾動位置
  setTimeout(() => {
    window.scrollTo(0, currentScrollY)
  }, 50)
}

// 取得類型標題
const getTypeTitle = (type) => {
  return props.texts.types[type] || type
}



// 取得影片縮圖
const getVideoThumbnail = (item) => {
  // 根據不同內容類型選擇適當的圖片欄位
  if (props.contentType === 'live') {
    return item.thumbnail || item.poster || item.featured_image
  } else {
    // drama/program 優先使用 poster_desktop
    return item.poster_desktop || item.poster || item.featured_image
  }
}

// 處理圖片 URL 解析
const resolveImageUrl = (imagePath) => {
  if (!imagePath) return null
  // 如果已經是完整 URL，直接返回
  if (imagePath.startsWith('http')) return imagePath
  // 移除開頭的斜線並使用 asset 函數
  return asset(imagePath.replace(/^\//, ''))
}

// 處理圖片載入錯誤
const handleImageError = (event) => {
  event.target.src = asset('frontend/images/default.webp')
  // 避免預設圖片也載入失敗導致無限循環
  event.target.onerror = null
}

// 處理圖片載入完成
const handleImageLoad = (event) => {
  // 圖片載入完成後，添加 loaded 類別讓圖片顯示
  event.target.classList.add('loaded')
}
</script>

<style scoped>
.loading-state {
  display: flex;
  align-items: center;
  justify-content: center;
  height: 200px;
  width: 100%;
}

.loading-dots {
  display: flex;
  align-items: center;
  justify-content: center;
}

.loading-dots span {
  display: inline-block;
  width: 8px;
  height: 8px;
  margin: 0 3px;
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
  font-size: 1rem;
  color: #cccccc;
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

.empty-state {
  text-align: left;
  padding: 5px;
  font-size: 1.2rem;
  color: #cccccc !important;
}

/* 確保圖片載入樣式與現有設計一致 */
.news-list .item .img img,
.radio-list .item .img img,
.video-list .item .img img {
  transition: opacity 0.3s ease, transform 0.5s ease;
  opacity: 0; /* 初始狀態為透明 */
}

/* 圖片載入完成後顯示 */
.news-list .item .img img.loaded,
.radio-list .item .img img.loaded,
.video-list .item .img img.loaded {
  opacity: 1;
}

/* 確保圖片容器有 overflow: hidden 讓 hover 效果正常 */
.news-list .item .img,
.radio-list .item .img,
.video-list .item .img {
  overflow: hidden;
}



/* 淡入淡出效果 - 只影響內容列表，不影響分頁 */
.news-list, .radio-list, .video-list {
  opacity: 0;
  transition: opacity 0.3s ease-in-out;
}

.news-list.fade-in, .radio-list.fade-in, .video-list.fade-in {
  opacity: 1;
}

/* 分頁組件保持固定顯示，不受淡入淡出影響 */
.pagination-div {
  opacity: 1 !important;
  transition: none !important;
}

/* 響應式設計 */
@media (max-width: 768px) {
  .loading-state,
  .empty-state {
    padding: 1.5rem;
    font-size: 14px;
  }
  
  .block-title .sub-title h2 {
    font-size: 18px;
  }
}
</style>