<template>
  <div class="member-collection-list">
    <!-- Collection Items -->
    <div v-if="collections.length > 0" class="content" :class="[activeTab === 'radio' ? 'radio-list' : 'video-list', { 'fade-in': contentLoaded }]">
      <!-- 廣播使用橫向佈局 -->
      <div v-if="activeTab === 'radio'" v-for="item in collections" :key="item.id" class="item">
        <a :href="item.url">
          <div class="img">
            <img :src="item.image" :alt="item.title" loading="lazy">
            <div class="more">
              <collection-button 
                :content-type="activeTab" 
                :content-id="item.id"
                :show-text="false"
                button-class="collection-icon-btn"
                :texts="collectionTexts"
                @collection-changed="onCollectionChanged">
              </collection-button>
            </div>
          </div>
          <div class="info">
            <div class="program">
              <h3>{{ item.title }}</h3>
              <p v-if="item.subtitle">{{ item.subtitle }}</p>
            </div>
          </div>
        </a>
      </div>
      
      <!-- 其他類型使用原來的佈局結構 -->
      <div v-else v-for="item in collections" :key="item.id" class="item">
        <div class="img">
          <a :href="item.url">
            <img :src="item.image" :alt="item.title" loading="lazy">
          </a>
          <div class="more">
            <collection-button 
              :content-type="activeTab" 
              :content-id="item.id"
              :show-text="false"
              button-class="collection-icon-btn"
              :texts="collectionTexts"
              @collection-changed="onCollectionChanged">
            </collection-button>
          </div>
        </div>
        <div class="info">
          <h3>{{ item.title }}</h3>
          <p v-if="item.subtitle && activeTab !== 'drama' && activeTab !== 'program'">{{ item.subtitle }}</p>
        </div>
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

// Props
const props = defineProps({
  initialType: {
    type: String,
    default: 'articles'
  },
  texts: {
    type: Object,
    default: () => ({
      no_collection: '尚未收藏任何內容',
      collect: '收藏',
      collected: '已收藏', 
      uncollect: '取消收藏'
    })
  }
})

// Injections
const sweetAlert = inject('$sweetAlert')

// Reactive state
const activeTab = ref(props.initialType)
const collections = ref([])
const loading = ref(false)
const contentLoaded = ref(false)

// Computed
const collectionTexts = computed(() => ({
  collect: props.texts.collect,
  collected: props.texts.collected,
  uncollect: props.texts.uncollect
}))

const emptyMessage = computed(() => props.texts.no_collection)

// Methods
const loadCollections = async (contentType = null) => {
  const type = contentType || activeTab.value
  loading.value = true
  contentLoaded.value = false
  
  try {
    const response = await axios.get('/member/collection/getData', {
      params: {
        content_type: type
      }
    })
    
    if (response.data.status) {
      collections.value = response.data.data || []
      
      // 資料載入完成後，延遲淡入
      nextTick(() => {
        setTimeout(() => {
          contentLoaded.value = true
        }, 50)
      })
    } else {
      sweetAlert.showToast(response.data.msg || '載入失敗', 'error')
      collections.value = []
      contentLoaded.value = true // 確保錯誤狀態也能顯示
    }
  } catch (error) {
    console.error('[MemberCollectionList] 載入收藏列表失敗:', error)
    sweetAlert.showToast('載入失敗，請稍後再試', 'error')
    collections.value = []
    contentLoaded.value = true // 確保錯誤狀態也能顯示
  } finally {
    loading.value = false
  }
}

const updateContentType = (contentType) => {
  // 開始淡出效果並立即清空內容
  contentLoaded.value = false
  collections.value = [] // 立即清空內容避免看到前一個tab的內容
  
  setTimeout(() => {
    activeTab.value = contentType
    loadCollections(contentType)
  }, 150) // 150ms 淡出時間
}

const onCollectionChanged = (data) => {
  // 取消收藏時直接從列表中移除該項目，避免重新載入造成閃爍
  if (data.action === 'remove') {
    const index = collections.value.findIndex(item => item.id === data.contentId)
    if (index !== -1) {
      collections.value.splice(index, 1)
    }
  }
  
  // 動態更新 Tab 計數
  updateTabCounts(data)
  
  // 如果當前 tab 沒有項目了，顯示空狀態
  if (collections.value.length === 0 && data.action === 'remove') {
    // 不需要額外處理，template 中的 v-else 會自動顯示空狀態
  }
}

const updateTabCounts = (data) => {
  const tabLinks = document.querySelectorAll('[data-type]')
  tabLinks.forEach(link => {
    const tabType = link.dataset.type
    if (tabType === data.contentType) {
      // Extract current count from tab text
      const currentText = link.textContent
      const match = currentText.match(/（(\d+)）/)
      if (match) {
        const currentCount = parseInt(match[1])
        const newCount = data.action === 'remove' ? currentCount - 1 : currentCount + 1
        const newText = currentText.replace(/（\d+）/, `（${Math.max(0, newCount)}）`)
        link.textContent = newText
      }
    }
  })
}

// Tab 切換事件監聽（參考廣播做法）
const bindTabEvents = () => {
  const tabLinks = document.querySelectorAll('[data-type]')
  tabLinks.forEach(link => {
    link.addEventListener('click', (e) => {
      e.preventDefault()
      
      // 切換樣式
      tabLinks.forEach(l => l.classList.remove('active'))
      link.classList.add('active')
      
      // 載入該類型的全部收藏
      const contentType = link.dataset.type
      updateContentType(contentType)
    })
  })
}

// Lifecycle
onMounted(async () => {
  // 綁定 Tab 切換事件
  await nextTick()
  bindTabEvents()
  
  // 初始化時設為未載入狀態
  contentLoaded.value = false
  
  // 載入初始資料
  await loadCollections(props.initialType)
})
</script>

<style scoped>
/* 使用設計版面的原生樣式，只保留必要的覆寫 */
.member-collection-list {
  width: 100%;
}

.loading-state {
  color: #666;
  font-size: 16px;
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

/* 收藏按鈕位置 - 統一在圖片內的左下角 */
:deep(.video-list .item .more),
:deep(.radio-list .item .more) {
  position: absolute !important;
  left: 15px !important;
  bottom: 15px !important;
  display: none !important;
  z-index: 2 !important;
}

/* hover 時顯示收藏按鈕 */
:deep(.video-list .item:hover .more),
:deep(.radio-list .item:hover .more) {
  display: flex !important;
}

/* 淡入淡出效果 */
.content {
  opacity: 0;
  transition: opacity 0.3s ease-in-out;
  min-height: 400px; /* 預設高度避免版面跳動 */
}

.content.fade-in {
  opacity: 1;
}


</style>