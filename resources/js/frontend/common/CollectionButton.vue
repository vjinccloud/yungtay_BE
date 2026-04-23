<template>
  <button 
    type="button"
    @click="toggleCollection"
    :disabled="loading"
    :class="['collection-btn', buttonClass, { 'collected': isCollected, 'loading': loading }]"
    :title="buttonTitle"
    v-show="isReady"
  >
    <img :src="iconSrc" :alt="buttonText">
    <span v-if="showText">{{ buttonText }}</span>
  </button>
</template>

<script setup>
import { ref, computed, onMounted, inject } from 'vue'
import axios from 'axios'

// Emits
const emit = defineEmits(['collection-changed'])

// Props
const props = defineProps({
  contentType: {
    type: String,
    required: true,
    validator: (value) => ['articles', 'drama', 'program', 'live', 'radio'].includes(value)
  },
  contentId: {
    type: [Number, String],
    required: true
  },
  // 是否顯示文字
  showText: {
    type: Boolean,
    default: true
  },
  // 自定義樣式類別
  buttonClass: {
    type: String,
    default: ''
  },
  // 翻譯文字
  texts: {
    type: Object,
    default: () => ({
      collect: '收藏',
      collected: '已收藏',
      uncollect: '取消收藏'
    })
  }
})

// 注入 sweetAlert
const sweetAlert = inject('$sweetAlert')

// 響應式狀態
const isCollected = ref(false)
const loading = ref(false)
const isReady = ref(false) // 新增：是否已載入狀態

// 計算屬性
const iconSrc = computed(() => {
  // 根據收藏狀態使用不同圖標
  if (isCollected.value) {
    return '/frontend/images/icon_favorite_blue.svg'
  } else {
    return '/frontend/images/icon_society_04_white.svg'
  }
})

const buttonText = computed(() => {
  if (loading.value) return '收藏中...'
  return isCollected.value ? props.texts.collected : props.texts.collect
})

const buttonTitle = computed(() => {
  return isCollected.value ? props.texts.uncollect : props.texts.collect
})

// 檢查收藏狀態
const checkCollectionStatus = async () => {
  try {
    const response = await axios.post('/member/collection/check-status', {
      content_type: props.contentType,
      content_ids: [parseInt(props.contentId)]
    })

    if (response.data.status && response.data.data) {
      isCollected.value = response.data.data[props.contentId] || false
    }
  } catch (error) {
    // 未登入或其他錯誤，預設為未收藏
    isCollected.value = false
    console.log('[CollectionButton] 檢查收藏狀態失敗:', error.message)
  } finally {
    // 無論成功或失敗，都標記為已載入狀態
    isReady.value = true
  }
}

// 切換收藏狀態
const toggleCollection = async () => {
  if (loading.value) return

  loading.value = true

  try {
    const endpoint = isCollected.value ? '/member/collection/remove' : '/member/collection/add'
    const response = await axios.post(endpoint, {
      content_type: props.contentType,
      content_id: parseInt(props.contentId)
    })

    if (response.data.status) {
      // 先更新狀態，再顯示訊息
      const newStatus = !isCollected.value
      isCollected.value = newStatus
      
      // 通知父組件狀態變化
      emit('collection-changed', {
        contentType: props.contentType,
        contentId: props.contentId,
        action: newStatus ? 'add' : 'remove',
        isCollected: newStatus
      })
      
      // 直接使用 API 回應的訊息
      sweetAlert.showToast(response.data.msg, 'success')
    } else {
      sweetAlert.showToast(response.data.msg || '操作失敗', 'error')
    }
  } catch (error) {
    if (error.response?.status === 401) {
      sweetAlert.showToast('請先登入', 'warning')
    } else {
      sweetAlert.showToast('操作失敗，請稍後再試', 'error')
    }
    console.error('[CollectionButton] 操作失敗:', error)
  } finally {
    loading.value = false
  }
}

// 生命週期
onMounted(() => {
  checkCollectionStatus()
})

// 暴露方法供父組件調用
defineExpose({
  checkStatus: checkCollectionStatus,
  toggle: toggleCollection
})
</script>

<style scoped>
.collection-btn {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 8px 16px;
  border: 1px solid #ddd;
  background: #fff;
  color: #666;
  border-radius: 4px;
  cursor: pointer;
  transition: all 0.3s ease;
  font-size: 14px;
  text-decoration: none;
}


.collection-btn.collected {
  background: #ff6b6b;
  border-color: #ff6b6b;
  color: #fff;
}


.collection-btn:disabled,
.collection-btn.loading {
  opacity: 0.6;
  cursor: not-allowed;
}

.collection-btn:focus-visible {
  outline: none;
}


.loading-spinner {
  animation: spin 1s linear infinite;
}

@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

/* 緊湊模式（僅顯示圖示） */
.collection-btn.compact {
  padding: 8px;
  min-width: 36px;
  justify-content: center;
}

.collection-btn.compact span {
  display: none;
}

/* 新聞頁面圖標按鈕樣式 */
.collection-btn.collection-icon-btn {
  background: transparent !important;
  border: none !important;
  padding: 0;
  width: 40px;
  height: 40px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  position: relative;
  box-shadow: none !important;
}

.collection-btn.collection-icon-btn:active,
.collection-btn.collection-icon-btn:focus,
.collection-btn.collection-icon-btn:hover {
  background: transparent !important;
  box-shadow: none !important;
  border: none !important;
}


.collection-btn.collection-icon-btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}
</style>