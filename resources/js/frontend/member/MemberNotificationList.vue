<template>
  <div class="personal-notice-list">
    <!-- Loading State -->
    <div v-if="loading" class="loading-state">
      <!-- 載入中不顯示文字 -->
    </div>

    <!-- Notifications List -->
    <template v-else-if="notifications.length > 0">
      <div
        v-for="(notification, index) in notifications"
        :key="notification.id"
        class="item"
        :class="{
          'active': expandedNotificationId === notification.id
        }"
        @click="toggleNotification(notification)"
      >
        <div class="item-title">
          <div class="datetime">{{ formatDate(notification.created_at) }}</div>
          <div class="notice">
            {{ getNotificationTitle(notification) }}
            <span class="unread-dot" v-if="!notification.is_read"></span>
          </div>
        </div>
        <div v-if="expandedNotificationId === notification.id" class="item-info">
          <p>{{ getNotificationMessage(notification) }}</p>
        </div>
      </div>

      <!-- Pagination -->
      <div v-if="totalPages > 1" class="pagination-wrapper">
        <pagination
          :current-page="currentPage"
          :total-pages="totalPages"
          @page-change="handlePageChange">
        </pagination>
      </div>
    </template>

    <!-- Empty State -->
    <div v-else-if="!loading && notifications.length === 0" class="no-data">
      <p>{{ texts.no_notifications || '目前沒有通知' }}</p>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, inject } from 'vue'
import axios from 'axios'
import Pagination from '@/frontend/common/Pagination.vue'

// Props
const props = defineProps({
  texts: {
    type: Object,
    default: () => ({
      no_notifications: '目前沒有通知'
    })
  }
})

// Injections
const sweetAlert = inject('$sweetAlert')

// Reactive state
const notifications = ref([])
const currentPage = ref(1)
const totalPages = ref(1)
const loading = ref(true)
const expandedNotificationId = ref(null)

// Methods
const loadNotifications = async (page = 1) => {
  loading.value = true

  try {
    const response = await axios.get('/member/notifications', {
      params: {
        page: page,
        ajax: 1 // 標記為 AJAX 請求
      }
    })

    if (response.data.status) {
      const paginatedData = response.data.data // 這裡直接是分頁物件
      notifications.value = paginatedData.data || []
      currentPage.value = paginatedData.current_page || 1
      totalPages.value = paginatedData.last_page || 1
    } else {
      sweetAlert.showToast(response.data.msg || '載入失敗', 'error')
      notifications.value = []
    }
  } catch (error) {
    console.error('[MemberNotificationList] 載入通知失敗:', error)
    sweetAlert.showToast('載入失敗，請稍後再試', 'error')
    notifications.value = []
  } finally {
    loading.value = false
  }
}

const toggleNotification = async (notification) => {
  // 如果點擊的是已經展開的通知，則收合
  if (expandedNotificationId.value === notification.id) {
    expandedNotificationId.value = null
    return
  }

  // 展開點擊的通知
  expandedNotificationId.value = notification.id

  // 如果是未讀通知，自動標記為已讀
  if (!notification.is_read) {
    await markAsRead(notification.id)
    // 更新本地狀態
    notification.is_read = true
  }
}

const markAsRead = async (notificationId) => {
  try {
    const response = await axios.put(`/member/notifications/${notificationId}/read`)

    if (!response.data.status) {
      console.error('標記已讀失敗:', response.data.msg)
    }
  } catch (error) {
    console.error('[MemberNotificationList] 標記已讀失敗:', error)
  }
}

const handlePageChange = (page) => {
  currentPage.value = page
  expandedNotificationId.value = null // 切換頁面時收合所有通知
  loadNotifications(page)
}

// Utility functions
const formatDate = (dateString) => {
  if (!dateString) return ''
  const date = new Date(dateString)
  return date.toLocaleDateString('zh-TW').replace(/\//g, '-')
}

// Helper methods - 後端已處理語系，直接顯示
const getNotificationTitle = (notification) => {
  return notification.title || ''
}

const getNotificationMessage = (notification) => {
  return notification.message || ''
}

// Lifecycle
onMounted(async () => {
  // 載入通知
  await loadNotifications()
})
</script>

<style scoped>
/* 使用設計版面的原生樣式 main.css */
/* personal-notice-list 的樣式已在 main.css 定義 */

/* Loading 狀態樣式 */
.loading-state {
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: 400px;
}

/* 空狀態樣式 - 與客服記錄保持一致 */
.no-data {
  text-align: center;
  padding: 60px 20px;
  color: #6c757d;
  min-height: 400px;
  display: flex;
  justify-content: center;
  align-items: center;
}

.no-data p {
  font-size: 18px;
  margin: 0;
  color: #fff;
}

/* 分頁容器 */
.pagination-wrapper {
  margin-top: 30px;
  display: flex;
  justify-content: center;
}

/* 未讀小紅點 */
.unread-dot {
  display: inline-block;
  width: 8px;
  height: 8px;
  background-color: #dc3545;
  border-radius: 50%;
  margin-left: 8px;
  vertical-align: middle;
}
</style>