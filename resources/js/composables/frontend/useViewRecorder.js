import { ref, inject } from 'vue'

/**
 * 觀看記錄 Composable
 * 負責記錄用戶（會員/訪客）的觀看數據
 */
export function useViewRecorder() {
  const $http = inject('$http')
  
  const isRecording = ref(false)
  const recordedViews = ref(new Set()) // 記錄已觀看的內容，避免重複記錄

  /**
   * 記錄觀看（自動判斷會員/訪客）
   * @param {string} contentType - 內容類型 (article, drama, program, live, radio)
   * @param {number} contentId - 內容ID
   * @param {number|null} episodeId - 集數ID（可選）
   * @param {object} options - 額外選項
   * @returns {Promise<boolean>} 成功返回 true
   */
  const recordView = async (contentType, contentId, episodeId = null, options = {}) => {
    // 防止重複記錄同一內容
    const viewKey = `${contentType}-${contentId}-${episodeId || 0}`
    if (recordedViews.value.has(viewKey)) {
      return true
    }

    if (isRecording.value) {
      return false
    }

    try {
      isRecording.value = true
      
      const data = {
        content_type: contentType,
        content_id: contentId
      }
      
      // 如果有 episodeId，加入請求數據
      if (episodeId) {
        data.episode_id = episodeId
      }

      // 檢查是否為登入會員
      const authStatusMeta = document.querySelector('meta[name="auth-status"]')
      const isLoggedIn = authStatusMeta && authStatusMeta.content === 'authenticated'
      
      let response
      if (isLoggedIn) {
        // 會員觀看記錄 - 使用 Web 路由
        try {
          response = await $http.post('/member/views/record', data)
        } catch (memberError) {
          // 如果是認證錯誤（401 未認證、419 CSRF 過期）或 Email 未驗證錯誤，降級為訪客記錄
          // 檢查多種情況：
          // 1. 401 未認證
          // 2. 419 CSRF 過期
          // 3. 403 或其他狀態碼且訊息包含「驗證」或「Email」
          const errorStatus = memberError.response?.status
          const errorMessage = memberError.response?.data?.message || memberError.response?.data?.msg || ''
          
          if (errorStatus === 401 || 
              errorStatus === 419 || 
              (errorMessage.includes('驗證') && errorMessage.includes('Email')) ||
              (memberError.response?.data?.redirect && memberError.response.data.redirect.includes('email-verification'))) {
            // 降級為訪客記錄
            response = await $http.post('/api/v1/views/record-guest', data)
          } else {
            // 其他錯誤則拋出
            throw memberError
          }
        }
      } else {
        // 訪客觀看記錄 - 使用 API 路由
        response = await $http.post('/api/v1/views/record-guest', data)
      }

      // 檢查回應格式（Web 路由直接回傳，API 可能有包裝）
      const result = response.data?.status !== undefined ? response.data : response.data?.result || response.data
      
      if (result.status) {
        // 記錄成功，加入已記錄清單
        recordedViews.value.add(viewKey)
        
        // 觸發自定義事件（供其他組件監聽）
        if (typeof window !== 'undefined') {
          window.dispatchEvent(new CustomEvent('viewRecorded', {
            detail: { contentType, contentId, episodeId, result }
          }))
        }
        
        return true
      } else {
        return false
      }

    } catch (error) {
      // 靜默處理錯誤，不影響用戶體驗
      
      // 保留重要的生產環境錯誤記錄
      if (error.response?.status >= 500) {
        console.error('觀看記錄系統錯誤:', error.response?.status)
      }
      
      // 如果是防刷限制或已記錄過，不視為錯誤
      if (error.response?.status === 422) {
        const errorMsg = error.response.data?.msg || error.response.data?.message || '記錄失敗'
        if (errorMsg.includes('已記錄') || errorMsg.includes('頻繁')) {
          recordedViews.value.add(viewKey) // 標記為已記錄，避免再次嘗試
          return true
        }
      }
      
      return false
    } finally {
      isRecording.value = false
    }
  }

  /**
   * 記錄新聞觀看
   * @param {number} articleId - 新聞ID
   * @returns {Promise<boolean>}
   */
  const recordArticleView = async (articleId) => {
    return await recordView('article', articleId)
  }

  /**
   * 記錄影音觀看
   * @param {number} dramaId - 影音ID
   * @param {number|null} episodeId - 集數ID
   * @returns {Promise<boolean>}
   */
  const recordDramaView = async (dramaId, episodeId = null) => {
    return await recordView('drama', dramaId, episodeId)
  }

  /**
   * 記錄節目觀看
   * @param {number} programId - 節目ID
   * @param {number|null} episodeId - 集數ID
   * @returns {Promise<boolean>}
   */
  const recordProgramView = async (programId, episodeId = null) => {
    return await recordView('program', programId, episodeId)
  }

  /**
   * 記錄直播觀看
   * @param {number} liveId - 直播ID
   * @returns {Promise<boolean>}
   */
  const recordLiveView = async (liveId) => {
    return await recordView('live', liveId)
  }

  /**
   * 記錄廣播觀看
   * @param {number} radioId - 廣播ID
   * @returns {Promise<boolean>}
   */
  const recordRadioView = async (radioId) => {
    return await recordView('radio', radioId)
  }

  /**
   * 批次記錄觀看（用於列表頁面）
   * @param {Array} items - 要記錄的項目列表 [{contentType, contentId, episodeId}]
   * @param {number} delay - 記錄間隔（毫秒），避免頻繁請求
   * @returns {Promise<number>} 成功記錄的數量
   */
  const recordBatchViews = async (items, delay = 1000) => {
    let successCount = 0
    
    for (let i = 0; i < items.length; i++) {
      const item = items[i]
      const success = await recordView(item.contentType, item.contentId, item.episodeId)
      if (success) successCount++
      
      // 延遲執行，避免頻繁請求
      if (i < items.length - 1) {
        await new Promise(resolve => setTimeout(resolve, delay))
      }
    }
    
    return successCount
  }

  /**
   * 重置記錄狀態（用於測試或特殊情況）
   */
  const resetRecordedViews = () => {
    recordedViews.value.clear()
  }

  /**
   * 檢查是否已記錄過某內容
   * @param {string} contentType 
   * @param {number} contentId 
   * @param {number|null} episodeId 
   * @returns {boolean}
   */
  const hasRecorded = (contentType, contentId, episodeId = null) => {
    const viewKey = `${contentType}-${contentId}-${episodeId || 0}`
    return recordedViews.value.has(viewKey)
  }

  return {
    // 狀態
    isRecording,
    recordedViews,
    
    // 通用方法
    recordView,
    hasRecorded,
    resetRecordedViews,
    
    // 專用方法
    recordArticleView,
    recordDramaView,
    recordProgramView,
    recordLiveView,
    recordRadioView,
    
    // 批次處理
    recordBatchViews
  }
}