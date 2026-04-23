<!-- resources/js/InertiaPages/Admin/Shared/Content/Components/BaseVideoForm.vue -->
<template>
    <div
      class="modal fade"
      tabindex="-1"
      role="dialog"
      aria-modal="true"
      data-bs-backdrop="static"
      data-bs-keyboard="false"
      ref="modalForm"
    >
    <div class="modal-dialog modal-lg" role="document">
      <div class="modal-content">
        <div class="block block-rounded shadow-none mb-0">
          <!-- Modal Header -->
          <div class="block-header block-header-default">
            <h3 class="block-title">{{ isEditing ? '編輯集數' : '新增集數' }}</h3>
            <div class="block-options">
              <button type="button" class="btn-block-option" @click="closeModal">
                <i class="fa fa-times"></i>
              </button>
            </div>
          </div>

          <!-- Modal Body -->
          <div class="block-content fs-sm">
            <form @submit.prevent="onSubmit">
              <!-- 季數 (唯讀) -->
              <div class="mb-4">
                <select class="form-select text-center" v-model="form.season" disabled>
                  <option :value="form.season">第 {{ form.season }} 季</option>
                </select>
              </div>

              <!-- 上傳方式切換 -->
              <div class="mb-4 d-flex align-items-center">
                <label class="form-label me-3 mb-0">來源：</label>
                <div class="form-check me-3">
                  <input
                    class="form-check-input"
                    type="radio"
                    id="type-youtube"
                    value="youtube"
                    v-model="uploadType"
                  />
                  <label class="form-check-label" for="type-youtube">YouTube URL</label>
                </div>
                <div class="form-check">
                  <input
                    class="form-check-input"
                    type="radio"
                    id="type-upload"
                    value="upload"
                    v-model="uploadType"
                  />
                  <label class="form-check-label" for="type-upload">本機上傳</label>
                </div>
              </div>

              <!-- YouTube 輸入 -->
              <div v-if="uploadType === 'youtube'" class="mb-4">
                <label class="form-label">
                  YouTube 影片 URL
                  <span class="text-danger" v-if="uploadType === 'youtube'">*</span>
                </label>
                <input
                  type="text"
                  class="form-control"
                  v-model="form.youtube_url"
                  placeholder="https://www.youtube.com/watch?v=..."
                  :class="{'is-invalid': form.errors.youtube_url}"
                />
                <div v-if="form.errors.youtube_url" class="invalid-feedback">
                  {{ form.errors.youtube_url }}
                </div>
              </div>

              <!-- 本機上傳 -->
              <div v-else class="mb-4">
                <!-- 使用 slot 讓各模組可以使用自己的上傳組件 -->
                <slot
                  name="video-uploader"
                  :form="form"
                  :isEditing="isEditing"
                  :handleUploadSuccess="handleFileUploadSuccess"
                  :handleRemove="handleFileRemove"
                >
                  <!-- 預設的上傳組件 -->
                  <VideoUploader
                    ref="videoUploaderRef"
                    :upload-url="route('admin.uploads.tmp.upload')"
                    :remove-url="route('admin.uploads.tmp.remove')"
                    :limit="1"
                    :extensions="['mp4']"
                    :max-size="500"
                    :initial-video-url="currentVideoUrl"
                    :initial-file-name="currentFileName"
                    :is-editing="isEditing"
                    @uploaded="handleFileUploadSuccess"
                    @removed="handleFileRemove"
                    @error="handleError"
                  />
                </slot>
                <div v-if="form.errors.video_file" class="invalid-feedback d-block">
                  {{ form.errors.video_file }}
                </div>
              </div>

              <!-- 時長與簡介 -->
              <div class="row mb-4">
                <div class="col-md-6">
                  <label class="form-label">單集時長 (中文)<span class="text-danger">*</span></label>
                  <input
                    type="text"
                    class="form-control"
                    v-model="form.duration_text.zh_TW"
                    placeholder="格式 ex. 66分鐘"
                    :class="{'is-invalid': form.errors?.duration_text?.zh_TW}"
                  />
                  <div v-if="form.errors?.duration_text?.zh_TW" class="invalid-feedback">
                    {{ form.errors.duration_text?.zh_TW }}
                  </div>
                </div>
                <div class="col-md-6">
                  <label class="form-label">單集時長 (English)<span class="text-danger">*</span></label>
                  <input
                    type="text"
                    class="form-control"
                    v-model="form.duration_text.en"
                    placeholder="e.g. 66 minutes"
                    :class="{'is-invalid': form.errors?.duration_text?.en}"
                  />
                  <div v-if="form.errors?.duration_text?.en" class="invalid-feedback">
                    {{ form.errors.duration_text?.en }}
                  </div>
                </div>
              </div>

              <div class="row mb-4">
                <div class="col-md-6">
                  <label class="form-label">單集簡介 (中文)<span class="text-danger">*</span></label>
                  <textarea
                    class="form-control"
                    rows="3"
                    v-model="form.description.zh_TW"
                    :class="{'is-invalid': form.errors?.description?.zh_TW}"
                  ></textarea>
                  <div v-if="form.errors?.description?.zh_TW" class="invalid-feedback">
                    {{ form.errors.description?.zh_TW }}
                  </div>
                </div>
                <div class="col-md-6">
                  <label class="form-label">單集簡介 (English)<span class="text-danger">*</span></label>
                  <textarea
                    class="form-control"
                    rows="3"
                    v-model="form.description.en"
                    :class="{'is-invalid': form.errors?.description?.en}"
                  ></textarea>
                  <div v-if="form.errors?.description?.en" class="invalid-feedback">
                    {{ form.errors.description?.en }}
                  </div>
                </div>
              </div>

              <!-- 額外欄位 slot -->
              <slot name="extra-fields" :form="form"></slot>
            </form>
          </div>

          <!-- Modal Footer -->
          <div class="block-content block-content-full block-content-sm text-end border-top">
            <button type="button" class="btn btn-alt-secondary me-2" @click="closeModal">
              關閉
            </button>
            <button type="button" class="btn btn-primary" :disabled="form.processing" @click="onSubmit">
              <span v-if="form.processing">
                <i class="fa fa-spinner fa-spin me-1"></i>處理中...
              </span>
              <span v-else>
                <i class="fa fa-save me-1"></i>儲存
              </span>
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, nextTick, onMounted, watch, inject, onBeforeUnmount } from 'vue'
import { Modal } from 'bootstrap'
import { useVideo } from '@/composables/video'
import VideoUploader from '@/Plugin/VideoUploader.vue'

// Props
const props = defineProps({
  contentType: {
    type: String,
    required: true,
    validator: (value) => ['drama', 'program'].includes(value)
  },
  contentId: {
    type: Number,
    default: null
  },
  currentSeason: {
    type: Number,
    default: 1
  },
  nextSeq: {
    type: Number,
    default: 1
  }
})

// Emits
const emit = defineEmits(['reload', 'saved', 'closed'])

// 使用 useVideo composable
const {
  form,
  isEditing,
  uploadType,
  resetForm,
  setEditData,
  submitForm,
  handleFileUploadSuccess,
  handleFileRemove,
  clearTempFiles
} = useVideo(props.contentType, props.contentId)

// Modal 相關
const modalForm = ref(null)
const videoUploaderRef = ref(null)
let modalInstance = null

// 計算屬性
const currentVideoUrl = computed(() => {
  if (isEditing.value && form.video_file_path) {
    return form.video_file_path.startsWith('http')
      ? form.video_file_path
      : `/storage/${form.video_file_path}`
  }
  return ''
})

const currentFileName = computed(() => {
  if (isEditing.value) {
    return form.original_filename || '現有影片檔案'
  }
  return ''
})

// 錯誤處理
const handleError = (error) => {
  console.error('上傳錯誤:', error)
}

// Modal 方法
const openModal = () => {
  resetForm(props.currentSeason)
  form.seq = props.nextSeq || 1
  nextTick(() => {
    if (modalInstance) {
      modalInstance.show()
    }
  })
}

const closeModal = () => {
  if (document.activeElement) {
    document.activeElement.blur()
  }
  resetForm()
  if (modalInstance) {
    modalInstance.hide()
  }
  emit('closed')

  // 延遲確保 modal 完全關閉
  setTimeout(() => {
    if (modalInstance) {
      modalInstance.hide()
    }
  }, 0)
}

// 編輯 Modal
const editModal = (data) => {
  const videoData = data.data || data
  setEditData(videoData, props.currentSeason)

  if (modalInstance) {
    modalInstance.show()
  }
}

// 提交表單
const sweetAlert = inject('$sweetAlert')

const onSubmit = async () => {
  const success = await submitForm(emit, closeModal)

  if (!success) {
    sweetAlert.error({
      msg: '提交失敗，請檢查是否有欄位錯誤！'
    })
  }
}

// 監聽季數變化
watch(() => props.currentSeason, (val) => {
  form.season = val
})

// 監聽上傳類型切換 - 清除舊資料
watch(uploadType, (newType, oldType) => {
  // ✅ 編輯模式載入資料時，不要清空欄位（避免 setEditData 觸發 watch 導致資料被清空）
  if (oldType && newType !== oldType && !isEditing.value) {
    // 與原版動線一致：切換來源時，清除另一類型的資料（含編輯狀態）
    if (oldType === 'youtube' && newType === 'upload') {
      form.youtube_url = ''
      form.video_type = ''
      form.clearErrors('youtube_url')
    } else if (oldType === 'upload' && newType === 'youtube') {
      form.video_file = ''
      form.video_file_path = ''
      form.original_filename = ''
      form.file_size = ''
      form.video_format = ''
      form.video_type = ''
      form.clearErrors('video_file')

      // 清除上傳器
      if (videoUploaderRef.value) {
        videoUploaderRef.value.reset()
      }
    }
  }

  // ✅ 但切換類型時，要更新 video_type（不論是否編輯模式）
  if (newType) {
    form.video_type = newType
  }
})

// 監聽 YouTube URL 變化
watch(() => form.youtube_url, (newVal) => {
  if (newVal) {
    form.video_type = 'youtube'
  } else if (!form.video_file_path) {
    form.video_type = ''
  }
})

// 生命週期
onMounted(() => {
  modalInstance = new Modal(modalForm.value, {
    backdrop: 'static',
    keyboard: false
  })
})

// 清理函數（用於 beforeunload）
const handleBeforeUnload = () => {
  navigator.sendBeacon(route('admin.uploads.tmp.clear-all'))
}

onMounted(() => {
  // 瀏覽器關閉時清理
  window.addEventListener('beforeunload', handleBeforeUnload)
})

onBeforeUnmount(() => {
  clearTempFiles()
  // 移除事件監聽器，避免記憶體洩漏
  window.removeEventListener('beforeunload', handleBeforeUnload)
})

// 暴露方法給父組件
defineExpose({
  openModal,
  editModal,
  closeModal
})
</script>

<style scoped>
/* 可依需求微調 */
.modal-dialog {
  max-width: 800px;
}
</style>
