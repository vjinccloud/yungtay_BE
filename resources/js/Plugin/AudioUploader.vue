<!-- resources/js/Plugin/AudioUploader.vue -->
<template>
  <div class="audio-uploader">
    <input
      type="file"
      ref="filerInput"
      name="files[]"
      :accept="accept"
    />
  </div>
  <!-- 音訊預覽區 -->
  <div
    id="audioPreview"
    class="p-2 bg-dark text-center text-light rounded"
    style=""
    v-show="showPreview"
  >
    <!-- 音訊內容會動態插入這裡 -->
  </div>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount, inject, watch } from 'vue'
import $ from 'jquery'
window.$ = $
window.jQuery = $
import { loadCss, loadJs } from '@/utils/scriptLoader'

// Props
const props = defineProps({
  uploadUrl: { type: String, required: true },
  removeUrl: { type: String, default: '' },
  limit: { type: Number, default: 1 },
  extensions: { type: Array, default: () => ['mp3'] },
  maxSize: { type: Number, default: 1024 }, // MB (1GB)
  accept: { type: String, default: 'audio/*' },
  // 編輯模式相關 props
  initialAudioUrl: { type: String, default: '' },
  initialFileName: { type: String, default: '' },
  isEditing: { type: Boolean, default: false }
})

// Emits
const emit = defineEmits(['uploaded', 'removed', 'error'])
const sweetAlert = inject('$sweetAlert')

// 響應式變數
const filerInput = ref(null)
const showPreview = ref(false)
let filerInstance = null

// 顯示初始音訊
const showInitialAudio = (audioUrl) => {
  if (!audioUrl) return

  setTimeout(() => {
    $('#audioPreview')
      .empty()
      .append(`
        <div class="existing-audio-container">
          <p class="mb-2"><i class="fa fa-music me-2"></i><strong>目前音訊：</strong></p>
          <audio controls class="w-100" src="${audioUrl}"></audio>
          <p class="text-muted mt-2 small">
            ${props.initialFileName || '現有音訊檔案'}
          </p>
        </div>
      `)

    showPreview.value = true
  }, 100)
}

// 監聽初始音訊 URL 變化
watch(() => props.initialAudioUrl, (newUrl) => {
  if (newUrl && props.isEditing) {
    showInitialAudio(newUrl)
  }
}, { immediate: true })

// 初始化 jQuery Filer（流程對齊 VideoUploader）
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
                <div class="jFiler-item-thumb-icon" style="font-size: 48px; color: #007bff; padding: 20px;">
                  <i class="fa fa-music"></i>
                </div>
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
                <div class="jFiler-item-thumb-icon" style="font-size: 48px; color: #007bff; padding: 20px;">
                  <i class="fa fa-music"></i>
                </div>
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
      button: '選擇檔案',
      feedback: '選擇要上傳的檔案',
      feedback2: '檔案已選擇',
      drop: '將檔案拖曳到此處上傳',
      removeConfirmation: '確定要移除此檔案嗎？',
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
      // 明確告知後端這是「音訊」上傳，套用正確驗證與儲存目錄
      data: { upload_type: 'audio' },
      synchron: true,
      onProgress: function(el, file, progress) {
        const $bar = $('.bar')
        $bar.text(el + '%')
      },
      success: function(data, itemEl, listEl, boxEl, newInputEl, inputEl, id) {
        setTimeout(() => {
          const url = data.url

          // 清空並顯示新上傳的音訊
          $('#audioPreview')
            .empty()
            .append(`
              <div class="new-audio-container">
                <p class="mb-2"><i class="fa fa-music me-2"></i><strong>已上傳音訊：</strong></p>
                <audio controls class="w-100" src="${url}"></audio>
              </div>
            `)

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

          // 通知父元件（資料回傳與 VideoUploader 對齊）
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

        // 移除音訊處理
        $('#audioPreview').empty()

        // 如果是編輯模式且有初始音訊，恢復顯示初始音訊
        if (props.isEditing && props.initialAudioUrl) {
          showInitialAudio(props.initialAudioUrl)
        } else {
          showPreview.value = false
        }

        emit('removed')
      }, 0)
    }
  })
}

// 載入 jQuery Filer CSS 和 JS（對齊 VideoUploader）
async function loadFilerAssets() {
  await loadCss('/plugins/jquery.filer/css/jquery.filer.css')
  await loadJs('/plugins/jquery.filer/js/jquery.filer.min.js')
}

// 清理方法
function reset() {
  // 重置 jFiler 實例
  if (filerInstance) {
    const inst = $(filerInput.value).data('jFiler')
    inst?.reset()
  }

  // 清空音訊預覽區域
  $('#audioPreview').empty()
  showPreview.value = false

  // 清空 jFiler 上傳列表（重要！這裡會清除「上傳成功」的卡片）
  $('.jFiler-items-list').empty()
}

// 設定初始音訊（用於編輯模式）
function setInitialAudio(audioUrl, fileName = '') {
  if (audioUrl) {
    showInitialAudio(audioUrl)
  }
}

// 生命週期
onMounted(async () => {
  try {
    await loadFilerAssets()
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

// 暴露方法給父組件
defineExpose({
  reset,
  setInitialAudio
})
</script>

<style scoped>


#audioPreview audio {
  max-width: 100%;
  height: 40px;
}

.existing-audio-container,
.new-audio-container {
  padding: 10px;
  animation: fadeIn 0.3s ease-in;
}

.existing-audio-container p,
.new-audio-container p {
  margin: 0;
}

@keyframes fadeIn {
  from {
    opacity: 0;
  }
  to {
    opacity: 1;
  }
}

/* 簡潔的 jFiler 樣式覆寫 */
:deep(.jFiler-items-list) {
  margin-top: 10px;
}

:deep(.jFiler-item) {
  margin: 5px;
}
:deep(.jFiler-jProgressBar){
     height: 20px;
     width:145px !important;
}
:deep(.jFiler-item-container){
    width:100%;
    padding: 7px !important;
}

:deep(.jFiler-item-thumb-icon) {
  display: flex;
  align-items: center;
  justify-content: center;
  height: 100%;
  background-color: #f8f9fa;
  border-radius: 4px;
}
</style>
