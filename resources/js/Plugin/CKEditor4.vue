<!-- CKEditor4.vue -->
<template>
    <textarea
      :id="editorId"
      v-model="localValue"
      :name="name"
    ></textarea>
  </template>

  <script setup>
  import { ref, onMounted, onBeforeUnmount, watch } from 'vue'
  import { loadJs } from '@/utils/scriptLoader.js'

  const props = defineProps({
    modelValue: String,
    name: {
      type: String,
      default: 'content'
    }
  })

  const emit = defineEmits(['update:modelValue'])

  const editorId = ref(`editor-${Date.now()}-${Math.random().toString(36).substr(2, 9)}`)
  const localValue = ref(props.modelValue || '')
  const editor = ref(null)

  // 監聽外部值變化
  watch(() => props.modelValue, (newValue) => {
    if (editor.value && newValue !== editor.value.getData()) {
      editor.value.setData(newValue || '')
    }
  })

  onMounted(async () => {
    try {
      // 確保 jQuery 已載入（CKEditor 4 需要 jQuery）
      if (!window.jQuery && !window.$) {
        console.warn('jQuery 未載入，從 CDN 載入 jQuery')
        await loadJs('https://code.jquery.com/jquery-3.6.0.min.js')
        // 確保 jQuery 全域可用
        window.$ = window.jQuery = window.jQuery || $
      }

      // 載入 CKEditor
      if (!window.CKEDITOR) {
        await loadJs('/plugins/ckeditor/ckeditor.js')

        // 等待 CKEDITOR 完全載入
        await new Promise((resolve) => {
          if (window.CKEDITOR && window.CKEDITOR.status === 'loaded') {
            resolve()
          } else if (window.CKEDITOR) {
            window.CKEDITOR.on('loaded', resolve)
          } else {
            // 如果 CKEDITOR 物件還沒建立，等待一下
            setTimeout(resolve, 100)
          }
        })

        // 載入 CKFinder（可選）
        try {
          await loadJs('/plugins/ckfinder/ckfinder.js')
          if (window.CKFinder && window.CKFinder.setupCKEditor) {
            window.CKFinder.setupCKEditor()
          }
        } catch (ckfinderError) {
          console.warn('CKFinder 載入失敗，但 CKEditor 仍可正常使用:', ckfinderError)
        }
      }

      // 使用更簡單、更安全的初始化方式
      const config = {
        startupMode: 'wysiwyg',
        allowedContent: true,
        autoUpdateElement: false,
        // 使用全域配置，不覆蓋工具列（避免衝突）
        customConfig: '/plugins/ckeditor/config.js'
      }

      // 延遲初始化，確保 DOM 和依賴都準備就緒
      setTimeout(() => {
        try {
          editor.value = window.CKEDITOR.replace(editorId.value, config)

          // 設定初始值和事件監聽
          editor.value.on('instanceReady', () => {
            if (props.modelValue) {
              editor.value.setData(props.modelValue)
            }

            // 監聽內容變化
            editor.value.on('change', () => {
              const data = editor.value.getData()
              emit('update:modelValue', data)
            })

            // 監聽按鍵事件（即時同步）
            editor.value.on('key', () => {
              setTimeout(() => {
                const data = editor.value.getData()
                emit('update:modelValue', data)
              }, 100)
            })
          })

        } catch (initError) {
          console.error('CKEditor 初始化錯誤:', initError)
          // 降級處理：使用最基本的配置
          try {
            editor.value = window.CKEDITOR.replace(editorId.value, {
              toolbar: 'Basic',
              startupMode: 'wysiwyg'
            })

            editor.value.on('instanceReady', () => {
              if (props.modelValue) {
                editor.value.setData(props.modelValue)
              }
              editor.value.on('change', () => {
                const data = editor.value.getData()
                emit('update:modelValue', data)
              })
            })
          } catch (fallbackError) {
            console.error('CKEditor 降級初始化也失敗:', fallbackError)
          }
        }
      }, 300)

    } catch (error) {
      console.error('CKEditor 初始化失敗:', error)
    }
  })

  onBeforeUnmount(() => {
    if (editor.value) {
      editor.value.destroy()
    }
  })
  </script>

  <style>
  /* 修復 CKEditor 原始碼模式的文字顏色問題 */
  textarea.cke_source {
    color: #000 !important;
  }
  </style>
