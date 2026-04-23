<template>
  <div class="member-view-history">
    <!-- History Items -->
    <div v-if="history.length > 0" class="content" :class="[activeTab === 'radio' ? 'radio-list' : 'video-list', { 'fade-in': contentLoaded }]">
      <!-- 廣播使用橫向佈局 -->
      <div v-if="activeTab === 'radio'" v-for="item in history" :key="item.id" class="item">
        <a :href="item.url" target="_blank">
          <div class="img">
            <img :src="item.image" :alt="item.title" loading="lazy">
            <div class="more">
              <i class="play"></i>
            </div>
          </div>
          <div class="info">
            <div class="program">
              <h3>{{ item.title }}</h3>
              <p class="watch-time">{{ formatDate(item.viewed_at) }}</p>
            </div>
          </div>
        </a>
      </div>
      
      <!-- 其他類型使用原來的佈局結構 -->
      <div v-else v-for="item in history" :key="item.id" class="item">
        <div class="img">
          <a :href="item.url" target="_blank">
            <img :src="item.image" :alt="item.title" loading="lazy">
          </a>
          <div class="more">
            <a :href="item.url" target="_blank" v-if="item.content_type !== 'article'">
              <i class="play"></i>
              <span v-if="item.episode_info">{{ item.episode_info }}</span>
            </a>
          </div>
        </div>
        <div class="info">
          <h3>{{ item.title }}</h3>
          <p class="watch-time">{{ formatDate(item.viewed_at) }}</p>
        </div>
      </div>
      
      <!-- Pagination 放在內容區塊內部 -->
      <div v-if="pagination.last_page > 1" class="pagination-container">
        <Pagination 
          :current-page="pagination.current_page"
          :total-pages="pagination.last_page"
          @page-change="loadPage" />
      </div>
    </div>
    
    <!-- Empty State -->
    <div v-else class="no-data content" :class="{ 'fade-in': contentLoaded }">
      <p>{{ emptyMessage }}</p>
    </div>
    
  </div>
</template>

<script setup>
import { ref, computed, onMounted, inject, nextTick } from 'vue'
import axios from 'axios'
import Pagination from '../common/Pagination.vue'

// Props
const props = defineProps({
  initialType: {
    type: String,
    default: 'article' // 預設新聞
  },
  initialTimeRange: {
    type: String,
    default: 'all' // 預設所有時間
  },
  texts: {
    type: Object,
    default: () => ({
      no_history: '目前沒有觀看紀錄',
      live_now: '正在直播',
      loading: '載入中...',
      time: {
        just_now: '剛剛',
        minutes_ago: '分鐘前',
        hours_ago: '小時前',
        yesterday: '昨天',
        days_ago: '天前',
        weeks_ago: '週前',
        months_ago: '個月前'
      }
    })
  }
})

// Injections
const sweetAlert = inject('$sweetAlert')

// Reactive state
const activeTab = ref(props.initialType)
const activeTimeRange = ref(props.initialTimeRange)
const history = ref([])
const loading = ref(false)
const contentLoaded = ref(false)
const pagination = ref({
  current_page: 1,
  last_page: 1,
  total: 0,
  per_page: 16
})

// Computed
const emptyMessage = computed(() => props.texts.no_history)

// Methods
const loadHistory = async (contentType = null, timeRange = null, page = 1) => {
  const type = contentType || activeTab.value
  const range = timeRange || activeTimeRange.value
  
  loading.value = true
  contentLoaded.value = false
  
  try {
    const response = await axios.get('/member/views/history', {
      params: {
        content_type: type,
        time_range: range,
        page: page
      }
    })
    
    if (response.data.status) {
      const data = response.data.data || {}
      
      // Laravel Paginator 物件格式
      history.value = data.data || []
      pagination.value = {
        current_page: data.current_page || 1,
        last_page: data.last_page || 1,
        total: data.total || 0,
        per_page: data.per_page || 16
      }
      
      // 資料載入完成後，延遲淡入
      nextTick(() => {
        setTimeout(() => {
          contentLoaded.value = true
        }, 50)
      })
    } else {
      sweetAlert.showToast(response.data.msg || '載入失敗', 'error')
      history.value = []
      pagination.value = { current_page: 1, last_page: 1, total: 0, per_page: 16 }
      contentLoaded.value = true
    }
  } catch (error) {
    console.error('[MemberViewHistory] 載入觀看紀錄失敗:', error)
    sweetAlert.showToast('載入失敗，請稍後再試', 'error')
    history.value = []
    pagination.value = { current_page: 1, last_page: 1, total: 0, per_page: 16 }
    contentLoaded.value = true
  } finally {
    loading.value = false
  }
}

const updateContentType = (contentType) => {
  // 開始淡出效果並立即清空內容
  contentLoaded.value = false
  history.value = []
  
  setTimeout(() => {
    activeTab.value = contentType
    // 切換內容類型時重置到第一頁
    loadHistory(contentType, activeTimeRange.value, 1)
  }, 150) // 150ms 淡出時間
}

const updateTimeRange = (timeRange) => {
  console.log('[DEBUG] updateTimeRange 被呼叫，時間範圍:', timeRange)
  // 開始淡出效果並立即清空內容
  contentLoaded.value = false
  history.value = []
  
  setTimeout(() => {
    activeTimeRange.value = timeRange
    console.log('[DEBUG] 呼叫 loadHistory，類型:', activeTab.value, '時間範圍:', timeRange)
    // 切換時間範圍時重置到第一頁
    loadHistory(activeTab.value, timeRange, 1)
  }, 150)
}

const loadPage = (page) => {
  if (page < 1 || page > pagination.value.last_page) return
  loadHistory(activeTab.value, activeTimeRange.value, page)
}


const formatDate = (dateString) => {
  if (!dateString) return ''
  
  const date = new Date(dateString)
  const now = new Date()
  const diffTime = Math.abs(now - date)
  
  // 計算分鐘、小時、天數差異
  const diffMinutes = Math.floor(diffTime / (1000 * 60))
  const diffHours = Math.floor(diffTime / (1000 * 60 * 60))
  const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24))
  
  // 取得翻譯文字
  const timeTexts = props.texts.time || {}
  
  if (diffMinutes < 1) {
    return timeTexts.just_now || '剛剛'
  } else if (diffMinutes < 60) {
    return `${diffMinutes} ${timeTexts.minutes_ago || '分鐘前'}`
  } else if (diffHours < 24) {
    return `${diffHours} ${timeTexts.hours_ago || '小時前'}`
  } else if (diffDays === 1) {
    return timeTexts.yesterday || '昨天'
  } else if (diffDays <= 7) {
    return `${diffDays} ${timeTexts.days_ago || '天前'}`
  } else if (diffDays <= 30) {
    const weeks = Math.floor(diffDays / 7)
    return `${weeks} ${timeTexts.weeks_ago || '週前'}`
  } else if (diffDays <= 365) {
    const months = Math.floor(diffDays / 30)
    return `${months} ${timeTexts.months_ago || '個月前'}`
  } else {
    return date.toLocaleDateString('zh-TW', { year: 'numeric', month: 'long', day: 'numeric' })
  }
}


// Tab 切換事件監聽（參考收藏做法）
const bindTabEvents = () => {
  const tabLinks = document.querySelectorAll('[data-type]')
  tabLinks.forEach(link => {
    link.addEventListener('click', (e) => {
      e.preventDefault()
      
      // 切換樣式
      tabLinks.forEach(l => l.classList.remove('active'))
      link.classList.add('active')
      
      // 載入該類型的觀看紀錄
      const contentType = link.dataset.type
      updateContentType(contentType)
    })
  })
}

// 時間篩選事件監聽 - 完全由 Vue 接管，不依賴 jQuery
const bindTimeFilterEvents = () => {
  // 監聽整個下拉選項的點擊事件
  const subItems = document.querySelectorAll('.dropdown-select .sub-item')
  
  subItems.forEach((item) => {
    item.addEventListener('click', (e) => {
      e.preventDefault()
      e.stopPropagation()
      
      const input = item.querySelector('input[data-time-range]')
      const span = item.querySelector('span')
      
      if (input && span) {
        // 更新顯示文字
        const button = document.querySelector('.dropdown-select button span b')
        if (button) {
          button.textContent = span.textContent
        }
        
        // 設定 radio 為選中
        document.querySelectorAll('input[name="filterDatetime"]').forEach(r => r.checked = false)
        input.checked = true
        
        // 隱藏下拉選單
        const subMenu = item.closest('.sub-menu')
        if (subMenu) {
          subMenu.style.display = 'none'
        }
        
        // 直接呼叫 Vue 的更新方法
        const timeRange = input.dataset.timeRange
        updateTimeRange(timeRange)
      }
    })
  })
  
  // 保留下拉選單的開關功能
  const dropdownButton = document.querySelector('.dropdown-select button')
  if (dropdownButton) {
    dropdownButton.addEventListener('click', (e) => {
      e.preventDefault()
      e.stopPropagation()
      const subMenu = document.querySelector('.dropdown-select .sub-menu')
      if (subMenu) {
        const isHidden = subMenu.style.display === 'none' || !subMenu.style.display
        subMenu.style.display = isHidden ? 'block' : 'none'
      }
    })
  }
  
  // 點擊其他地方關閉下拉選單
  document.addEventListener('click', (e) => {
    if (!e.target.closest('.dropdown-select')) {
      const subMenu = document.querySelector('.dropdown-select .sub-menu')
      if (subMenu) {
        subMenu.style.display = 'none'
      }
    }
  })
}

// Lifecycle
onMounted(async () => {
  // 初始化時設為未載入狀態
  contentLoaded.value = false
  
  // 載入初始資料（預設新聞類型）
  await loadHistory(props.initialType, props.initialTimeRange)
  
  // 資料載入完成後再綁定事件
  await nextTick()
  
  // 延遲綁定事件，確保 DOM 已渲染完成
  setTimeout(() => {
    bindTabEvents()
    bindTimeFilterEvents()
  }, 100)
})
</script>

<style scoped>
/* 使用設計版面的原生樣式，只保留必要的覆寫 */
.member-view-history {
  width: 100%;
  display: flex;
  flex-direction: column;
  min-height: 500px; /* 配合父容器的 min-height */
}

/* 無資料狀態樣式（參考廣播頁面） */
.no-data {
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: 400px;
  text-align: center;
  color: #fff;
}

.no-data p {
  font-size: 18px;
  margin: 0;
}

/* 觀看時間樣式 */
.watch-time {
  font-size: 14px;
  color: #999;
  margin-top: 5px;
}

/* 淡入淡出效果 */
.content {
  opacity: 0;
  transition: opacity 0.3s ease-in-out;
  flex: 1; /* 讓內容區佔據剩餘空間 */
  min-height: 200px; /* 減少最小高度 */
}

.content.fade-in {
  opacity: 1;
}

/* 分頁組件容器樣式 */
.pagination-container {
  margin-top: 30px;
  display: flex;
  justify-content: center;
}

/* 淡入效果 */
.fade-in {
  opacity: 1;
  transition: opacity 0.3s ease-in-out;
}

/* 確保 item 有相對定位 */
:deep(.video-list .item),
:deep(.radio-list .item) {
  position: relative !important;
}

/* 確保圖片容器有相對定位 */
:deep(.video-list .item .img),
:deep(.radio-list .item .img) {
  position: relative !important;
}

/* 集數資訊位置 - 統一在圖片中央 */
:deep(.video-list .item .more),
:deep(.radio-list .item .more) {
  position: absolute !important;
  top: 0 !important;
  left: 0 !important;
  right: 0 !important;
  bottom: 0 !important;
  display: none !important;
  align-items: center !important;
  justify-content: center !important;
  background: rgba(0, 0, 0, 0.7) !important;
  z-index: 2 !important;
}

/* 只有當 .more 內有 a 標籤時才顯示遮罩 */
:deep(.video-list .item:hover .more:has(a)),
:deep(.radio-list .item:hover .more:has(a)) {
  display: flex !important;
}

/* 集數文字樣式 */
:deep(.video-list .item .more a),
:deep(.radio-list .item .more a) {
  color: white !important;
  text-decoration: none !important;
  display: flex !important;
  flex-direction: column !important;
  align-items: center !important;
  gap: 5px !important;
}

:deep(.video-list .item .more span),
:deep(.radio-list .item .more span) {
  padding: 4px 12px !important;
  border-radius: 4px !important;
  font-size: 14px !important;
  white-space: nowrap !important;
}

/* 響應式處理 */
@media (max-width: 768px) {
  .pagination {
    flex-wrap: wrap;
    gap: 5px;
  }
  
  .pagination a {
    padding: 6px 10px;
    font-size: 14px;
  }
}
</style>