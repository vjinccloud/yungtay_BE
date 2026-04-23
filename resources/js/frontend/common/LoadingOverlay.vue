<template>
  <transition name="fade">
    <div v-if="isVisible" class="loading" :class="{ 'fade-out': isFadingOut }">
      <div class="dots">
        <span></span>
        <span></span>
        <span></span>
      </div>
    </div>
  </transition>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'

// Props
const props = defineProps({
  // 是否顯示
  modelValue: {
    type: Boolean,
    default: false
  },
  // 最小顯示時間（避免閃爍）
  minDuration: {
    type: Number,
    default: 300
  },
  // 自動隱藏時間（0 = 不自動隱藏）
  autoHide: {
    type: Number,
    default: 0
  }
})

// Emits
const emit = defineEmits(['update:modelValue'])

// 內部狀態
const isVisible = ref(false)
const isFadingOut = ref(false)
const showStartTime = ref(null)
let autoHideTimer = null

// 計算屬性
const loading = computed({
  get: () => props.modelValue,
  set: (value) => emit('update:modelValue', value)
})

// 顯示 loading
const show = () => {
  isVisible.value = true
  isFadingOut.value = false
  showStartTime.value = Date.now()
  
  // 自動隱藏
  if (props.autoHide > 0) {
    clearTimeout(autoHideTimer)
    autoHideTimer = setTimeout(() => {
      hide()
    }, props.autoHide)
  }
}

// 隱藏 loading
const hide = () => {
  const elapsed = showStartTime.value ? Date.now() - showStartTime.value : 0
  const remainingTime = Math.max(0, props.minDuration - elapsed)
  
  // 確保最小顯示時間
  setTimeout(() => {
    isFadingOut.value = true
    setTimeout(() => {
      isVisible.value = false
      isFadingOut.value = false
      loading.value = false
    }, 300) // 淡出動畫時間
  }, remainingTime)
}

// 監聽 props 變化
import { watch } from 'vue'
watch(() => props.modelValue, (newVal) => {
  if (newVal) {
    show()
  } else {
    hide()
  }
}, { immediate: true })

// 清理
onUnmounted(() => {
  clearTimeout(autoHideTimer)
})

// 暴露方法給父組件
defineExpose({
  show,
  hide
})
</script>

<style scoped>
.loading {
  display: flex;
  align-items: center;
  justify-content: center;
  position: fixed;
  left: 0;
  top: 0;
  width: 100%;
  height: 100%;
  background-color: rgba(17, 19, 25, 0.95);
  z-index: 9999;
  transition: opacity 0.3s ease;
}

.loading.fade-out {
  opacity: 0;
}

.loading .dots {
  display: flex;
  align-items: center;
  justify-content: center;
}

.loading .dots span {
  display: inline-block;
  width: 12px;
  height: 12px;
  margin: 0 5px;
  background-color: #2CC0E2;
  border-radius: 50%;
  animation: dotsAnimate 1.4s ease-in-out infinite both;
}

.loading .dots span:nth-child(1) {
  animation-delay: -0.32s;
}

.loading .dots span:nth-child(2) {
  animation-delay: -0.16s;
}

.loading .dots span:nth-child(3) {
  animation-delay: 0;
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

/* Vue transition */
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>