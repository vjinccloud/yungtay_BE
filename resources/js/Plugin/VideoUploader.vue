<template>
  <div class="video-uploader" >
    <input
      type="file"
      ref="filerInput"
      name="files[]"
      :accept="accept"
    />
  </div>
  <!-- 影片預覽區 -->
  <div
    :id="previewId"
    ref="videoPreviewEl"
    class="mt-4 p-2 bg-dark text-center"
    style="min-height:200px;"
    v-show="showPreview"
  >
    <!-- 影片內容會動態插入這裡 -->
  </div>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount, inject, watch, computed } from 'vue'
import $ from 'jquery'
window.$ = $
window.jQuery = $
import { loadCss, loadJs } from '@/utils/scriptLoader'

// 生成唯一 ID
const uniqueId = Math.random().toString(36).substring(2, 9)
const previewId = computed(() => `videoPreview_${uniqueId}`)

// Props
const props = defineProps({
  uploadUrl: { type: String, required: true },
  removeUrl: { type: String, default: '' },
  limit: { type: Number, default: 1 },
  extensions: { type: Array, default: () => ['mp4'] },
  maxSize: { type: Number, default: 100 }, // MB
  accept: { type: String, default: 'video/*' },
  // 編輯模式相關 props
  initialVideoUrl: { type: String, default: '' },
  initialFileName: { type: String, default: '' },
  isEditing: { type: Boolean, default: false }
})

// Emits
const emit = defineEmits(['uploaded', 'removed', 'error'])
const sweetAlert = inject('$sweetAlert')

// 響應式變數
const filerInput = ref(null)
const videoPreviewEl = ref(null)
const showPreview = ref(false)
let filerInstance = null


// 清除現有影片
const clearExistingVideo = () => {
  if (videoPreviewEl.value) {
    $(videoPreviewEl.value).empty()
  }
  showPreview.value = false
  emit('removed')
}

// 顯示初始影片
const showInitialVideo = (videoUrl) => {
  if (!videoUrl) return

  setTimeout(() => {
    // 使用 ref 元素而不是全局 ID 選擇器
    if (videoPreviewEl.value) {
      const $container = $(videoPreviewEl.value)
      $container
        .empty()
        .append(`
          <div class="existing-video-container position-relative">
            <p class="text-light mb-2">目前影片：</p>
            <video controls width="100%" src="${videoUrl}"></video>
            <p class="text-muted mt-2 small">
              ${props.initialFileName || '現有影片檔案'}
            </p>
            <button type="button" class="btn btn-danger btn-sm delete-video-btn" style="position: absolute; top: 10px; right: 10px;">
              <i class="fa fa-trash me-1"></i>刪除影片
            </button>
          </div>
        `)
      
      // 綁定刪除按鈕事件
      $container.find('.delete-video-btn').on('click', function() {
        clearExistingVideo()
      })
    }

    showPreview.value = true
  }, 100)
}

// 監聽初始影片 URL 變化
watch(() => props.initialVideoUrl, (newUrl) => {
  if (newUrl && props.isEditing) {
    showInitialVideo(newUrl)
  }
}, { immediate: true })

// 時間格式化
function formatSeconds(value) {
  let secondTime = parseInt(value)
  let minuteTime = 0
  let hourTime = 0

  if (secondTime > 60) {
    minuteTime = parseInt(secondTime / 60)
    secondTime = parseInt(secondTime % 60)
    if (minuteTime > 60) {
      hourTime = parseInt(minuteTime / 60)
      minuteTime = parseInt(minuteTime % 60)
    }
  }
  if (secondTime < 10) secondTime = '0' + secondTime
  if (minuteTime < 10) minuteTime = '0' + minuteTime
  if (hourTime < 10) hourTime = '0' + hourTime

  const sec = secondTime
  const min = minuteTime
  const hr = hourTime
  return hr > 0 ? `${hr}:${min}:${sec}` : `${min}:${sec}`
}

function getVideoTime(id) {
  const video = document.getElementById(id)
  if (video) return formatSeconds(video.duration)
  return false
}

// 初始化 jQuery Filer
function initializeFiler() {
  const $input = $(filerInput.value)
  filerInstance = $input.filer({
    limit: props.limit,
    maxSize: props.maxSize,
    extensions: props.extensions,
    changeInput: true,
    showThumbs: true,
    theme: 'default',
    templates: {
      box: '<ul class="jFiler-items-list jFiler-items-grid"></ul>',
      item: `
        <li class="jFiler-item">
          <div class="jFiler-item-container">
            <div class="jFiler-item-inner">
              <div class="jFiler-item-thumb">
                <div class="jFiler-item-status"></div>
                <div class="jFiler-item-thumb-overlay">
                  <div class="jFiler-item-info">
                    <div style="display:table-cell;vertical-align: middle; padding: 5px;">
                      <span class="jFiler-item-title" style="display: block; word-break: break-word; font-size:11px;">
                        <b title="{{fi-name}}">{{fi-name}}</b>
                      </span>
                      <span class="jFiler-item-others" style="font-size:9px;">{{fi-size2}}</span>
                    </div>
                  </div>
                </div>
                {{fi-image}}
              </div>
              <div class="jFiler-item-assets jFiler-row">
                  <ul class="list-inline pull-left">
                      <li>{{fi-progressBar}}</li>
                  </ul>
                  <ul class="list-inline pull-right">
                      <li>
                          <a class="icon-jfi-trash jFiler-item-trash-action" style="color:#dc3545;font-size:18px;"></a>
                      </li>
                  </ul>
              </div>
            </div>
          </div>
        </li>`,
      itemAppend: `
        <li class="jFiler-item">
          <div class="jFiler-item-container">
            <div class="jFiler-item-inner">
              <div class="jFiler-item-thumb">
                <div class="jFiler-item-status"></div>
                <div class="jFiler-item-thumb-overlay">
                  <div class="jFiler-item-info">
                    <div style="display:table-cell;vertical-align: middle; padding: 5px;">
                      <span class="jFiler-item-title" style="display:block; font-size:11px;">
                        <b title="{{fi-name}}">{{fi-name}}</b>
                      </span>
                      <span class="jFiler-item-others" style="font-size:9px;">{{fi-size2}}</span>
                    </div>
                  </div>
                </div>
                {{fi-image}}
              </div>
              <div class="jFiler-item-assets jFiler-row">
                <ul class="list-inline pull-left">
                  <li><span class="jFiler-item-others">{{fi-icon}}</span></li>
                </ul>
                <ul class="list-inline pull-right">
                  <li>
                    <a class="icon-jfi-trash jFiler-item-trash-action" style="color:#dc3545;font-size:18px;">
                      <i class="fa fa-trash"></i>
                    </a>
                  </li>
                </ul>
              </div>
            </div>
          </div>
        </li>`,
      progressBar: '<div class="bar" style="width:0%;line-height:24px;text-align:center;">0%</div>',
      _selectors: {
        list: '.jFiler-items-list',
        item: '.jFiler-item',
        progressBar: '.bar',
        remove: '.jFiler-item-trash-action'
      },
    },
    captions: {
      button: "選擇檔案",
      feedback: "選擇要上傳的檔案",
      feedback2: "檔案已選擇",
      drop: "將檔案拖曳到此處上傳",
      removeConfirmation: "確定要移除此檔案嗎？",
      errors: {
        filesLimit: `最多只能上傳 ${props.limit} 個檔案`,
        filesType: `僅支援 ${props.extensions.join(',').toUpperCase()} 格式`,
        filesSize: `檔案太大！請小於 {{fi-maxSize}} MB`,
        filesSizeAll: `總大小超過限制！請小於 {{fi-maxSize}} MB`
      }
    },
    uploadFile: {
      url: props.uploadUrl,
      type: 'POST',
      enctype: 'multipart/form-data',
      synchron: true,
      onProgress: function(el, file, progress) {
        const $bar = $('.bar')
        $bar.text(el + '%')
      },
      success: function(data, itemEl, listEl, boxEl, newInputEl, inputEl, id) {
        setTimeout(() => {
          const url = data.url

          // 清空並顯示新上傳的影片
          if (videoPreviewEl.value) {
            $(videoPreviewEl.value)
              .empty()
              .append(`
                <div class="new-video-container">
                  <p class="text-light mb-2">新上傳影片：</p>
                  <video controls width="100%" src="${url}"></video>
                </div>
              `)
          }

          showPreview.value = true

          // 成功提示
          itemEl.find('.bar').fadeTo('slow', 0.3, () => {
            $('<div class="jFiler-item-others text-success">'
                + '<i class="icon-jfi-check-circle"></i> 上傳成功'
                + '</div>')
                .hide()
                .appendTo(itemEl.find('.jFiler-item-assets').parent())
                .fadeIn('slow')
          })

          // 通知父元件
          emit('uploaded', data)
        }, 0)
      },
      error: function(el, item, response) {
        setTimeout(() => {
          const parent = el.find('.jFiler-item-assets').parent()
          el.find('.bar').fadeOut('slow', () => {
            $('<div class="jFiler-item-others text-error"><i class="icon-jfi-minus-circle"></i> 上傳失敗</div>')
              .hide().appendTo(parent).fadeIn('slow')
          })
          emit('error', response)
        }, 0)
      }
    },
    onRemove: function(itemEl, file, id, listEl, boxEl, newInputEl, inputEl) {
      setTimeout(() => {
        const filerKit = inputEl.prop('jFiler')
        const name = filerKit?.files_list[id]?.name || ''
        if (props.removeUrl && name) {
          $.post(props.removeUrl, { file: name })
        }

        // 移除影片處理
        if (videoPreviewEl.value) {
          $(videoPreviewEl.value).empty()
        }

        // 如果是編輯模式且有初始影片，恢復顯示初始影片
        if (props.isEditing && props.initialVideoUrl) {
          showInitialVideo(props.initialVideoUrl)
        } else {
          showPreview.value = false
        }

        emit('removed')
      }, 0)
    }
  })
}

onMounted(async () => {
  try {
    await loadCss('/plugins/jquery.filer/css/jquery.filer.css')
    await loadJs('/plugins/jquery.filer/js/jquery.filer.min.js')
    await new Promise(r => setTimeout(r, 50))
  } catch (e) {
    console.warn('載入 Filer 失敗，忽略擴充錯誤:', e.message)
    return
  }
  if (filerInput.value) initializeFiler()
})

onBeforeUnmount(() => {
  if (filerInstance) {
    const inst = $(filerInput.value).data('jFiler')
    inst?.reset()
  }
})

// 重置方法
function reset() {
  if (filerInstance) {
    const inst = $(filerInput.value).data('jFiler')
    inst?.reset()
  }
  if (videoPreviewEl.value) {
    $(videoPreviewEl.value).empty()
  }
  showPreview.value = false
}

// 設定初始影片
function setInitialVideo(videoUrl, fileName = '') {
  if (videoUrl) {
    showInitialVideo(videoUrl)
  }
}

defineExpose({
  reset,
  setInitialVideo
})
</script>

<style scoped>
.video-uploader {
  /* 預設樣式 */
}

.existing-video-container,
.new-video-container {
  padding: 10px;
}

.existing-video-container p,
.new-video-container p {
  margin: 0;
}
</style>
