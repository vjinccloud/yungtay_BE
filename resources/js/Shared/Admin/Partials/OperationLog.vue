<template>
  <div class="operation-log">
    <!-- 標題區域 -->
    <div class="log-header">
      <h3>操作紀錄</h3>
    </div>

    <!-- 紀錄列表 -->
    <div class="log-content">
      <div class="log-timeline">
        <div
          v-for="(record, index) in displayRecords"
          :key="index"
          class="log-item"
        >
          <div class="timeline-dot"></div>
          <div class="log-details">
            <div class="log-id">{{ record.id }}</div>
            <div class="log-action">{{ record.action }}</div>
          </div>
        </div>
      </div>
    </div>

    <!-- 底部連結 -->
    <div class="log-footer" @click="viewAllRecords">
      <a href="javascript:;" class="view-all-link" >全部紀錄</a>
    </div>
  </div>
</template>

<script setup>
import { computed, ref, onMounted, onUnmounted,watch } from 'vue'
import { usePage, router } from '@inertiajs/vue3'

const page = usePage()
const logs = ref(page.props.logs || [])

// 重新獲取操作紀錄
const refreshLogs = async () => {
  try {
    // 重新訪問當前頁面以獲取最新的 props
    router.reload({ only: ['logs'] })
  } catch (error) {
    console.error('刷新操作紀錄失敗:', error)
  }
}

// 監聽操作完成事件
const handleOperationUpdate = () => {
  console.log('偵測到操作更新，正在刷新紀錄...')
  setTimeout(() => {
    refreshLogs()
  }, 500) // 延遲500ms確保後端已處理完成
}

onMounted(() => {
  // 監聽自定義事件
  window.addEventListener('operationLogUpdated', handleOperationUpdate)
})

onUnmounted(() => {
  window.removeEventListener('operationLogUpdated', handleOperationUpdate)
})

// 取得前10筆紀錄
const displayRecords = computed(() => {
  // 確保使用最新的 logs 值
  const currentLogs = logs.value || page.props.logs || []

  if (!currentLogs || currentLogs.length === 0) {
    return []
  }

  return currentLogs.slice(0, 10).map(log => ({
    id: `${log.user_name}-${log.created_at}`,
    action: log.message
  }))
})

const viewAllRecords = (event) => {
  event.preventDefault();
  router.get(route('admin.operation-logs'));
  const toggleButton = document.querySelector('[data-toggle="layout"][data-action="side_overlay_toggle"]');
  if (window.Codebase && window.Codebase.layout) {
    window.Codebase.layout('side_overlay_close');
  }
};

// 監聽 page props 變化
watch(() => page.props.logs, (newLogs) => {
  if (newLogs) {
    logs.value = newLogs
  }
}, { immediate: true })
</script>

