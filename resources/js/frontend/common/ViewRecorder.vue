<template>
  <!-- 無可見內容，純功能組件 -->
</template>

<script setup>
import { onMounted } from 'vue'
import { useViewRecorder } from '@/composables/frontend/useViewRecorder'

// Props
const props = defineProps({
  contentType: {
    type: String,
    required: true,
    validator: (value) => ['article', 'drama', 'program', 'live', 'radio'].includes(value)
  },
  contentId: {
    type: [Number, String],
    required: true
  },
  episodeId: {
    type: [Number, String],
    default: null
  },
  // 記錄延遲時間（毫秒），預設頁面載入後 2 秒記錄
  delay: {
    type: Number,
    default: 2000
  },
  // 是否自動記錄（預設 true）
  autoRecord: {
    type: Boolean,
    default: true
  }
})

// Composable
const { recordView } = useViewRecorder()

// 記錄觀看
const performRecord = async () => {
  try {
    const success = await recordView(
      props.contentType, 
      parseInt(props.contentId), 
      props.episodeId ? parseInt(props.episodeId) : null
    )
    
    if (success) {
      console.log(`[ViewRecorder] 記錄成功: ${props.contentType} ${props.contentId}`)
    }
  } catch (error) {
    console.error('[ViewRecorder] 記錄失敗:', error)
  }
}

// 生命週期
onMounted(() => {
  if (props.autoRecord) {
    // 延遲記錄，確保頁面完全載入且用戶有實際觀看意圖
    setTimeout(() => {
      performRecord()
    }, props.delay)
  }
})

// 暴露方法供父組件調用
defineExpose({
  recordView: performRecord
})
</script>

<style scoped>
/* 無樣式 */
</style>