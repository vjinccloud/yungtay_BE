<template>
  <!-- Notifications -->
  <div class="dropdown d-inline-block">
    <button
      type="button"
      class="btn btn-sm btn-alt-secondary"
      id="page-header-notifications"
      @click="toggleDropdown"
      :class="{'show': dropdownVisible}"
    >
      <i class="fa fa-bell"></i>
      <span v-if="unreadCount > 0" class="text-primary">&bull;</span>
    </button>

    <div
      class="dropdown-menu dropdown-menu-lg dropdown-menu-end p-0"
      :class="{'show': dropdownVisible}"
      style="position: absolute; inset: 0px 0px auto auto; margin: 0px; transform: translate(0px, 33px);"
      id="page-header-notifications-menu"
    >
      <!-- 頭部 -->
      <div class="px-3 py-3 bg-body-light border-bottom">
        <div class="text-center">
          <h5 class="fs-sm text-uppercase text-muted fw-bold mb-0">
            通知中心
            <span v-if="unreadCount > 0" class="badge bg-danger ms-2">{{ unreadCount }}</span>
          </h5>
        </div>
        <div v-if="unreadCount > 0" class="text-center mt-2">
          <button
            @click="markAllAsRead"
            class="btn btn-sm btn-alt-primary"
            :disabled="markingAllAsRead"
          >
            <i class="fa fa-check-double me-1"></i>
            {{ markingAllAsRead ? '處理中...' : '全部已讀' }}
          </button>
        </div>
      </div>

      <!-- 通知列表 -->
      <div class="notification-list" style="max-height: 400px; overflow-y: auto;">
        <ul v-if="notifications.length > 0" class="nav-items my-2 fs-sm">
          <li v-for="notification in notifications" :key="notification.id">
            <Link
              :href="notification.type === 'customer_service' ? route('admin.customer-services') : (notification.data?.action_url || route('admin.dashboard'))"
              class="text-dark d-flex py-2 notification-item text-decoration-none"
              :class="{ 'bg-light': !notification.is_read }"
              @click="handleNotificationClick(notification, $event)"
            >
              <div class="flex-shrink-0 me-2 ms-3">
                <i :class="notification.icon_class"></i>
              </div>
              <div class="flex-grow-1 pe-2">
                <p class="fw-medium mb-1" :class="{ 'fw-bold': !notification.is_read }">
                  {{ notification.title }}
                </p>
                <p class="text-muted mb-1 small">{{ notification.message }}</p>
                <div class="text-muted small">{{ notification.formatted_time }}</div>
              </div>
              <div v-if="!notification.is_read" class="flex-shrink-0 me-2">
                <span class="badge bg-primary">&bull;</span>
              </div>
            </Link>
          </li>
        </ul>

        <!-- 空狀態 -->
        <div v-else class="empty-state text-center py-5">
          <div class="empty-icon mb-3">
            <i class="fa fa-bell-slash fa-3x text-muted opacity-50"></i>
          </div>
          <h6 class="text-muted mb-2">目前沒有通知</h6>
          <p class="text-muted small mb-0">當有新的客服訊息時，會在這裡顯示</p>
        </div>
      </div>

    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, nextTick } from 'vue'
import { router, Link } from '@inertiajs/vue3'

// Props
const props = defineProps({
  auth: {
    type: Object,
    required: true
  }
})

// 響應式資料
const notifications = ref([])
const unreadCount = ref(0)
const loading = ref(false)
const markingAllAsRead = ref(false)
const dropdownVisible = ref(false)
const pollingTimer = ref(null)

// 載入通知列表
const loadNotifications = async () => {
  if (loading.value) return

  loading.value = true
  try {
    const response = await window.axios.get('/admin/notifications')
    notifications.value = response.data.notifications || []
    unreadCount.value = response.data.unread_count || 0
  } catch (error) {
    console.error('載入通知失敗:', error)
  } finally {
    loading.value = false
  }
}

// 標記單個通知為已讀
const markAsRead = async (notificationId) => {
  try {
    await window.axios.patch(`/admin/notifications/${notificationId}/read`)

    // 更新本地資料
    const notification = notifications.value.find(n => n.id === notificationId)
    if (notification && !notification.is_read) {
      notification.is_read = true
      unreadCount.value = Math.max(0, unreadCount.value - 1)
    }
  } catch (error) {
    console.error('標記已讀失敗:', error)
  }
}

// 標記全部為已讀
const markAllAsRead = async () => {
  if (markingAllAsRead.value || unreadCount.value === 0) return

  markingAllAsRead.value = true
  try {
    await window.axios.patch('/admin/notifications/mark-all-read')

    // 更新本地資料
    notifications.value.forEach(notification => {
      notification.is_read = true
    })
    unreadCount.value = 0
  } catch (error) {
    console.error('標記全部已讀失敗:', error)
  } finally {
    markingAllAsRead.value = false
  }
}

// 處理通知點擊事件
const handleNotificationClick = async (notification, event) => {
  // 如果未讀，先標記為已讀
  if (!notification.is_read) {
    await markAsRead(notification.id)
  }

  // 關閉下拉選單
  dropdownVisible.value = false
}


// Dropdown 控制
const toggleDropdown = () => {
  dropdownVisible.value = !dropdownVisible.value
  // 如果打開 dropdown 並且還沒載入過通知，則載入通知
  if (dropdownVisible.value && notifications.value.length === 0) {
    loadNotifications()
  }
}

const handleClickOutside = (event) => {
  const dropdownMenu = document.getElementById('page-header-notifications-menu')
  const dropdownButton = document.getElementById('page-header-notifications')

  // 確保點擊的是下拉菜單以外的區域
  if (dropdownMenu && !dropdownMenu.contains(event.target) && !dropdownButton.contains(event.target)) {
    dropdownVisible.value = false
  }
}

// 檢查未讀數量並更新通知列表
const checkUnreadCount = async () => {
  try {
    const response = await window.axios.get('/admin/notifications/unread-count')
    const newUnreadCount = response.data.count || 0

    // 如果未讀數量有變化，且下拉選單已經載入過通知，則重新載入通知列表
    if (newUnreadCount !== unreadCount.value && notifications.value.length > 0) {
      await loadNotifications()
    }

    unreadCount.value = newUnreadCount
  } catch (error) {
    console.error('載入未讀數量失敗:', error)
  }
}

// 開始輪詢
const startPolling = () => {
  // 清除現有的定時器
  if (pollingTimer.value) {
    clearInterval(pollingTimer.value)
  }

  // 每30秒檢查一次未讀數量
  pollingTimer.value = setInterval(() => {
    checkUnreadCount()
  }, 30000) // 30秒
}

// 停止輪詢
const stopPolling = () => {
  if (pollingTimer.value) {
    clearInterval(pollingTimer.value)
    pollingTimer.value = null
  }
}

// 組件掛載時載入未讀數量並開始輪詢
onMounted(async () => {
  // 初始載入
  await checkUnreadCount()

  // 開始輪詢
  startPolling()

  // 綁定點擊外部關閉事件
  document.addEventListener('click', handleClickOutside)
})

// 組件卸載時移除事件監聽並停止輪詢
onUnmounted(() => {
  // 停止輪詢
  stopPolling()

  // 移除點擊外部事件監聽
  document.removeEventListener('click', handleClickOutside)
})
</script>

<style scoped>

.notification-list::-webkit-scrollbar {
  width: 6px;
}

.notification-list::-webkit-scrollbar-track {
  background: #f1f1f1;
}

.notification-list::-webkit-scrollbar-thumb {
  background: #c1c1c1;
  border-radius: 3px;
}

.notification-list::-webkit-scrollbar-thumb:hover {
  background: #a8a8a8;
}
</style>