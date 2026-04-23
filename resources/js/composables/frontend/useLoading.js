import { ref } from 'vue'

// 全局 loading 狀態
const isLoading = ref(false)
const loadingText = ref('')
const loadingCount = ref(0)

export function useLoading() {
  
  // 顯示 loading
  const showLoading = (text = '') => {
    loadingCount.value++
    isLoading.value = true
    if (text) {
      loadingText.value = text
    }
  }
  
  // 隱藏 loading
  const hideLoading = () => {
    loadingCount.value = Math.max(0, loadingCount.value - 1)
    if (loadingCount.value === 0) {
      isLoading.value = false
      loadingText.value = ''
    }
  }
  
  // 強制隱藏（清除所有計數）
  const forceHideLoading = () => {
    loadingCount.value = 0
    isLoading.value = false
    loadingText.value = ''
  }
  
  // 包裝 Promise，自動顯示/隱藏 loading
  const withLoading = async (promise, text = '') => {
    showLoading(text)
    try {
      const result = await promise
      return result
    } finally {
      hideLoading()
    }
  }
  
  // Axios 攔截器整合
  const setupAxiosInterceptors = (axios) => {
    // 請求攔截器
    axios.interceptors.request.use(
      config => {
        // 可以根據 config 判斷是否顯示 loading
        if (config.showLoading !== false) {
          showLoading(config.loadingText)
        }
        return config
      },
      error => {
        hideLoading()
        return Promise.reject(error)
      }
    )
    
    // 響應攔截器
    axios.interceptors.response.use(
      response => {
        hideLoading()
        return response
      },
      error => {
        hideLoading()
        return Promise.reject(error)
      }
    )
  }
  
  return {
    isLoading,
    loadingText,
    showLoading,
    hideLoading,
    forceHideLoading,
    withLoading,
    setupAxiosInterceptors
  }
}