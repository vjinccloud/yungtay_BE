<template>
  <div class="file-uploader-wrapper" :class="{ 'compact-mode': props.compactMode }">
    <!-- 顯示載入狀態 -->
    <div v-if="isLoading" class="loading-state">
      <div class="text-center p-3">
        <div class="spinner"></div>
        載入檔案上傳器中...
      </div>
    </div>

    <!-- 顯示錯誤狀態或簡潔模式 -->

    <!-- 正常的 fileuploader -->
    <div v-else class="fileuploader-container">
      <input
        :id="inputId"
        type="file"
        :name="name"
        class="files"
        :accept="accept"
        :multiple="multiple"
        ref="fileInput"

      />
    </div>

    <!-- 除錯資訊 (開發模式) -->
    <div v-if="showDebugInfo" class="debug-info mt-2">
      <small class="text-muted">
        jQuery: {{ jQueryLoaded ? '✅' : '❌' }} |
        FileUploader: {{ fileUploaderLoaded ? '✅' : '❌' }} |
        模式: {{ props.uploadMode }} |
        實例: {{ !!apiRef ? '✅' : '❌' }} |
        簡潔模式: {{ props.compactMode ? '✅' : '❌' }}
      </small>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, watch, nextTick, computed } from 'vue'
import { loadJs } from '@/utils/index.js'

// 新增 CDN Fallback 選項
const props = defineProps({
  // 基本屬性
  name: {
    type: String,
    default: 'files'
  },
  accept: {
    type: String,
    default: ''
  },
  multiple: {
    type: Boolean,
    default: false
  },
  // fileuploader 配置
  limit: {
    type: Number,
    default: 5
  },
  extensions: {
    type: String,
    default: 'jpg,jpeg,png,gif,pdf,doc,docx'
  },
  maxSize: {
    type: Number,
    default: 5 // MB
  },
  // 已上傳的檔案數據
  files: {
    type: Array,
    default: () => []
  },
  // 上傳模式：'ajax' 或 'form'
  uploadMode: {
    type: String,
    default: 'ajax',
    validator: (value) => ['ajax', 'form'].includes(value)
  },
  // AJAX 模式配置
  uploadUrl: {
    type: String,
    default: '/upload'
  },
  autoUpload: {
    type: Boolean,
    default: true
  },
  // 表單模式配置
  formAction: {
    type: String,
    default: ''
  },
  formMethod: {
    type: String,
    default: 'POST'
  },
  // 語言設定
  language: {
    type: String,
    default: 'zh-TW'
  },
  // 顯示除錯資訊
  debug: {
    type: Boolean,
    default: false
  },
  // 使用 CDN 作為備用方案
  useCdnFallback: {
    type: Boolean,
    default: true
  },
  // 簡潔模式 - 只顯示按鈕，不顯示大的拖拽區域
  compactMode: {
    type: Boolean,
    default: false
  },
  // 刪除圖片ID的回調函數
  onDeleteImgId: {
    type: Function,
    default: null
  },
})

const emit = defineEmits([
  'upload-start',
  'upload-success',
  'upload-error',
  'file-added',
  'file-removed',
  'files-changed',
  'form-ready',
])

// Refs
const fileInput = ref(null)
const fallbackInput = ref(null)
const fileUploaderInstance = ref(null) // 保留，但不再依賴它取檔
const apiRef = ref(null)               // ← 新增：真正的 FileUploader API
const inputId = ref(`fileuploader-${Math.random().toString(36).substr(2, 9)}`)

// 狀態管理
const isLoading = ref(true)
const hasError = ref(false)
const jQueryLoaded = ref(false)
const fileUploaderLoaded = ref(false)
const initAttempts = ref(0)
const maxInitAttempts = 50 // 5秒等待時間

// 計算屬性
const showDebugInfo = computed(() => props.debug )

// 動態載入 jQuery 和 FileUploader
const loadDependencies = async () => {
  try {
    // 檢查 jQuery 是否已載入
    if (!window.$ || typeof window.$ !== 'function') {
      try {
        await loadJs('/plugins/jquery/jquery.min.js')
      } catch (error) {
        if (props.useCdnFallback) {
          await loadJs('https://code.jquery.com/jquery-3.6.0.min.js')
        } else {
          throw error
        }
      }

      // 等待一下確保 jQuery 完全載入
      await new Promise(resolve => setTimeout(resolve, 100))
    }

    // 檢查 FileUploader 是否已載入
    if (!window.$ || !window.$.fn || !window.$.fn.fileuploader) {
      try {
        await loadJs('/plugins/fileuploader2.2/dist/jquery.fileuploader.min.js')
      } catch (error) {
        if (props.useCdnFallback) {
          await loadJs('https://cdn.jsdelivr.net/npm/jquery.fileuploader@2.2.0/dist/jquery.fileuploader.min.js')
        } else {
          throw error
        }
      }

      // 等待一下確保 FileUploader 完全載入
      await new Promise(resolve => setTimeout(resolve, 100))
    }

    return true
  } catch (error) {
    console.error('Failed to load dependencies:', error)
    return false
  }
}

// 檢查依賴是否載入
const checkDependencies = () => {
  jQueryLoaded.value = !!(window.$ && typeof window.$ === 'function')
  fileUploaderLoaded.value = !!(window.$ && window.$.fn && window.$.fn.fileuploader)

  return jQueryLoaded.value && fileUploaderLoaded.value
}



const hasInit = ref(false)

// 初始化 fileuploader
const initFileUploader = () => {


  if (!fileInput.value) {
    return false;
  }


  try {
    const config = {
      skipFileNameCheck: true,
      enableApi: true,
      addMore: props.limit > 1, // 單檔案時不需要 addMore
      limit: props.limit,
      fileMaxSize: props.maxSize,
      extensions: props.extensions.split(',').map(ext => ext.trim()),
      captions: {
        confirm: "確定",
        cancel: "取消",
        name: "檔名",
        type: "類型",
        size: "大小",
        dimensions: "寬高",
        duration: "Duration",
        crop: "裁切",
        rotate: "旋轉",
        sort: "排序",
        download: "下載",
        remove: "刪除",
        drop: "拖移",
        removeConfirmation: "確定要刪除檔案嗎?",
        button: "選擇檔案",
        feedback: "選擇檔案上傳",
        feedback2: "檔案已選取",
        errors: {
          filesLimit: "最多只允許上傳 ${limit} 個檔案",
          filesType: "只允許上傳 ${extensions} 檔案格式",
          filesSize: "${name} 檔案太大! 檔案上傳上限為 ${maxSize} MB",
          filesSizeAll: "您選擇的檔案過大! 檔案上傳上限為 ${maxSize} MB",
        },
      },
      thumbnails: {
        onItemShow: function (item, listEl, parentEl, newInputEl, inputEl) {

        },
        onImageLoaded: function(item, listEl, parentEl, newInputEl, inputEl) {
            // 不需要再 emit update:files，避免循環更新
        },

      },
      afterRender: (listEl, parentEl, newInputEl, inputEl) => {
        // 取代後的新 input（重點）
        const el = newInputEl?.[0] || inputEl?.[0]
        apiRef.value =
          (window.$?.fileuploader?.getInstance?.(el)) ||
          el?.FileUploader ||
          null
      },
      beforeRender: (parentEl, inputEl) => {
        // 渲染前的回調
      },
      onRemove: (item, listEl, parentEl, newInputEl, inputEl) => {
        // 僅回報變更，不回推 v-model，避免重建
        const api = apiRef.value || newInputEl?.[0]?.FileUploader || inputEl?.[0]?.FileUploader
        const names = (api?.getChoosedFiles?.() || []).map(i => i.name)
        emit('files-changed', names)
        return true;
      }
    }

    // 如果有預設檔案，加入配置
    if (props.files && props.files.length > 0) {
      config.files = props.files
    }

    // 根據上傳模式添加額外配置
    if (props.uploadMode === 'ajax' && props.uploadUrl) {
      config.upload = {
        url: props.uploadUrl,
        data: null,
        type: 'POST',
        enctype: 'multipart/form-data',
        start: props.autoUpload,
        synchron: true,
        beforeSend: function(item, listEl, parentEl, newInputEl, inputEl) {
          emit('upload-start', { item, element: listEl })
        },
        onSuccess: function(result, item) {
          const data = typeof result === 'string' ? JSON.parse(result) : result
          emit('upload-success', { data, item })
        },
        onError: function(item) {
          emit('upload-error', { item })
        },
        onProgress: function(data, item) {
          // 上傳進度處理
        }
      }
    }

    const $inp = window.$(fileInput.value)

    // 如果已经初始化过，就先销毁再重载
    if (hasInit.value) {
      // 優先使用 apiRef 進行銷毀
      if (apiRef.value) {
        apiRef.value.destroy();
      } else {
        const api = window.$.fileuploader.getInstance($inp[0])
        if (api) api.destroy();
      }

      nextTick(() => {
        // 重新初始化並更新實例（使用新鮮 jQuery 物件）
        fileUploaderInstance.value = window.$(fileInput.value).fileuploader(config)
        hasInit.value = true;
      });

    } else {
      // 第一次初始化
      fileUploaderInstance.value = $inp.fileuploader(config)
      hasInit.value = true;
    }


    return true

  } catch (error) {
    console.error('Error initializing fileuploader:', error)
    return false
  }
}



// 主要初始化邏輯 - 修改為動態載入版本
const checkAndInit = async () => {
  initAttempts.value++

  // 如果依賴已經載入，直接初始化
  if (checkDependencies()) {
    isLoading.value = false
    hasError.value = false

    nextTick(() => {
      const success = initFileUploader()
      if (!success) {
        hasError.value = true
      }
    })
    return
  }

  // 如果是第一次嘗試，先動態載入依賴
  if (initAttempts.value === 1) {
    const loadSuccess = await loadDependencies()

    if (loadSuccess && checkDependencies()) {
      isLoading.value = false
      hasError.value = false

      nextTick(() => {
        const success = initFileUploader()
        if (!success) {
          hasError.value = true
        }
      })
      return
    }
  }


}

// 公開方法
const getFiles = () => {
  console.log('🔍 getFiles called');
  
  // 優先使用 apiRef，備用 fileUploaderInstance
  let api = apiRef.value;
  
  if (!api && fileUploaderInstance.value && fileUploaderInstance.value[0]) {
    api = fileUploaderInstance.value[0].FileUploader;
    console.log('📎 Using fallback API');
  }
  
  if (!api) {
    console.log('❌ No API available');
    return [];
  }

  try {
    const items = api.getChoosedFiles() || [];
    console.log('getFiles - raw items:', items);

    // 處理檔案，包含編輯後的 blob
    const files = items.map(item => {
      // 如果有編輯器且有 blob，使用編輯後的
      if (item.editor && item.editor.blob) {
        // 將 blob 轉為 File，加上預設 type
        const blob = item.editor.blob
        return new File([blob], item.name || 'edited-file', {
          type: blob.type || 'image/png'
        });
      }
      // 否則使用原始檔案
      return item.file;
    }).filter(f => f instanceof File);
    
    console.log('getFiles - processed files:', files);
    return files;
  } catch (error) {
    console.error('Error getting files:', error);
    return [];
  }
};


const uploadFiles = () => {
  if (props.uploadMode === 'ajax' && apiRef.value) {
    try {
      apiRef.value.upload()
    } catch (error) {
      console.error('Error uploading files:', error)
    }
  }
}

const clearFiles = () => {
  if (apiRef.value) {
    try {
      apiRef.value.reset()
    } catch (error) {
      console.error('Error clearing files:', error)
    }
  } else if (fallbackInput.value) {
    fallbackInput.value.value = ''
  }
}

const removeFile = (index) => {
  const api = apiRef.value
  if (!api?.getChoosedFiles) return
  const items = api.getChoosedFiles()
  const item = items?.[index]
  if (!item) return
  try { api.remove(item) } catch (e) { console.error('Error removing file:', e) }
}

const getUploadMode = () => props.uploadMode
const isFormMode = () => props.uploadMode === 'form'
const isAjaxMode = () => props.uploadMode === 'ajax'


watch(() => props.uploadMode, () => {
  nextTick(() => {
    // 優先使用 apiRef 進行銷毀
    if (apiRef.value) {
      try {
        apiRef.value.destroy();
      } catch (e) {
        console.warn('Error destroying via apiRef:', e);
      }
    } else if (fileUploaderInstance.value) {
      try {
        fileUploaderInstance.value.fileuploader('destroy')
      } catch (e) {
        console.warn('Error destroying fileuploader:', e)
      }
    }
    // 清空舊引用，避免「殭屍實例」
    apiRef.value = null
    fileUploaderInstance.value = null
    hasInit.value = false
    initFileUploader()
  })
})

// 暴露方法
defineExpose({
  getFiles,
  uploadFiles,
  clearFiles,
  removeFile,
  getUploadMode,
  isFormMode,
  isAjaxMode,
  getInstance: () => fileUploaderInstance.value
})

// 生命週期
onMounted(() => {
  checkAndInit();
})

onUnmounted(() => {
  // 優先使用 apiRef 進行銷毀
  if (apiRef.value) {
    try {
      apiRef.value.destroy();
    } catch (e) {
      console.warn('Error destroying via apiRef:', e);
    }
  } else if (fileUploaderInstance.value) {
    try {
      fileUploaderInstance.value.fileuploader('destroy')
    } catch (e) {
      console.warn('Error destroying fileuploader:', e)
    }
  }
  // 清空引用
  apiRef.value = null
  fileUploaderInstance.value = null
})
</script>

<style scoped>
/* 組件特定樣式 */
.file-uploader-wrapper {
  width: 100%;
  min-height: 60px;
}

.loading-state {
  border: 2px dashed #dee2e6;
  border-radius: 0.375rem;
  background-color: #f8f9fa;
}

.spinner {
  width: 20px;
  height: 20px;
  border: 2px solid #f3f3f3;
  border-top: 2px solid #3498db;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  display: inline-block;
  margin-right: 8px;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

.compact-upload {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-wrap: wrap;
}

.error-state {
  margin-bottom: 10px;
}

.fileuploader-container {
  width: 100%;
}

.debug-info {
  padding: 5px;
  background-color: #f8f9fa;
  border-radius: 3px;
  font-family: monospace;
}

/* fileuploader 2.2 原生樣式優化 */
:deep(.fileuploader) {
  font-family: inherit;
}

:deep(.fileuploader-items-list) {
  border-radius: 8px;
  overflow: hidden;
}

:deep(.fileuploader-item) {
  border-bottom: 1px solid #f1f5f9;
}

:deep(.fileuploader-item:last-child) {
  border-bottom: none;
}

:deep(.fileuploader-item:hover) {
  background: #f8fafc;
}

:deep(.columns) {
  display: flex;
  align-items: center;
  padding: 12px 16px;
  gap: 12px;
}

:deep(.column-thumbnail) {
  width: 40px;
  height: 40px;
  flex-shrink: 0;
}

:deep(.column-title) {
  flex: 1;
}

:deep(.file-info div) {
  font-size: 14px;
  font-weight: 500;
  color: #1e293b;
  margin-bottom: 2px;
}

:deep(.file-info span) {
  font-size: 12px;
  color: #64748b;
}

:deep(.column-actions) {
  flex-shrink: 0;
}

:deep(.fileuploader-action-remove) {
  width: 24px;
  height: 24px;
  background: #fee2e2;
  color: #dc2626;
  border: none;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.2s ease;
}

:deep(.fileuploader-action-remove:hover) {
  background: #fecaca;
  transform: scale(1.1);
}


</style>
