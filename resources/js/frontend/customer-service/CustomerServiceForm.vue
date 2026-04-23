<template>
  <form id="customer-service-form" @submit.prevent="onSubmit">
    <div class="form-keyin-div">
      <!-- 姓名和Email -->
      <div class="item">
        <div class="two-cols">
          <div class="col">
            <div class="label">{{ translations.name || '姓名' }}<span>*</span></div>
            <div class="controller">
              <input
                type="text"
                name="name"
                :placeholder="translations.name_placeholder || '請輸入姓名'"
                v-model="form.name"
                required
              >
              <div class="errorTxt"></div>
            </div>
          </div>
          <div class="col">
            <div class="label">Email<span>*</span></div>
            <div class="controller">
              <input
                type="email"
                name="email"
                :placeholder="translations.email_placeholder || '請輸入Email'"
                v-model="form.email"
                required
              >
              <div class="errorTxt"></div>
            </div>
          </div>
        </div>
      </div>

      <!-- 聯絡電話和聯絡地址 -->
      <div class="item">
        <div class="two-cols">
          <div class="col">
            <div class="label">{{ translations.phone_label || '聯絡電話' }}</div>
            <div class="controller">
              <input
                type="tel"
                name="phone"
                :placeholder="translations.phone_placeholder || '請輸入聯絡電話'"
                v-model="form.phone"
              >
              <div class="errorTxt"></div>
            </div>
          </div>
          <div class="col">
            <div class="label">{{ translations.address_label || '聯絡地址' }}</div>
            <div class="controller">
              <input
                type="text"
                name="address"
                :placeholder="translations.address_placeholder || '請輸入聯絡地址'"
                v-model="form.address"
              >
              <div class="errorTxt"></div>
            </div>
          </div>
        </div>
      </div>

      <!-- 主旨 -->
      <div class="item">
        <div class="label">{{ translations.subject || '主旨' }}<span>*</span></div>
        <div class="controller">
          <input
            type="text"
            name="subject"
            :placeholder="translations.subject_placeholder || '請輸入主旨'"
            v-model="form.subject"
            required
          >
          <div class="errorTxt"></div>
        </div>
      </div>

      <!-- 內容 -->
      <div class="item">
        <div class="label">{{ translations.message || '內容' }}<span>*</span></div>
        <div class="controller">
          <textarea
            name="message"
            :placeholder="translations.message_placeholder || '最多 250 個字'"
            maxlength="250"
            v-model="form.message"
            required
          ></textarea>
          <div class="errorTxt"></div>
        </div>
      </div>

      <!-- 同意條款和送出按鈕 -->
      <div class="item action">
        <div class="two-cols">
          <div class="col">
            <div class="checkbox-select">
              <label class="checkbox-container">
                <input
                  type="checkbox"
                  name="agree_terms"
                  v-model="form.agree_terms"
                  required
                >
                <span class="checkmark"></span>
              </label>
              <span class="desc">
                {{ translations.agree_terms || '同意本站' }}<a :href="privacyUrl" target="_blank">{{ translations.privacy_policy_link || '隱私權保護政策' }}</a>。
              </span>
            </div>
            <div class="errorTxt"></div>
          </div>
          <div class="col">
            <button class="btn-blue" type="submit" :disabled="loading">
              {{ loading ? (translations.submitting || '送出中...') : (translations.submit_btn || '確認送出') }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </form>
</template>

<script setup>
import { ref, reactive, onMounted, nextTick, inject, computed } from 'vue'
import { useFormValidation } from '@/composables/frontend/useFormValidation'

// Props
const props = defineProps({
  privacyUrl: {
    type: String,
    default: '/privacy'
  },
  submitUrl: {
    type: String,
    default: '/customer-service/send'
  },
  csrfToken: {
    type: String,
    required: false
  },
  userData: {
    type: Object,
    default: () => ({})
  },
  translations: {
    type: Object,
    default: () => ({})
  }
})

// Inject services
const $http = inject('$http')
const sweetAlert = inject('$sweetAlert')
const $loading = inject('$loading')

// 使用表單驗證 composable
const { setupFormValidation, showBackendErrors } = useFormValidation()

// 表單資料 - 初始化時使用會員資料（如果有）
const form = reactive({
  name: props.userData?.name || '',
  email: props.userData?.email || '',
  phone: props.userData?.phone || '',
  address: props.userData?.address || '',
  subject: '',
  message: '',
  agree_terms: false
})

// 狀態
const loading = ref(false)
const successMessage = ref('')
const errorMessages = ref([])

// 初始化表單驗證
const initFormValidation = async () => {
  await setupFormValidation('#customer-service-form', {
    // 自訂驗證方法
    customMethods: {
      phoneFormat: {
        validator: function(value, element) {
          // 允許空值（選填欄位）
          if (this.optional(element)) return true
          // 台灣手機或市話格式
          const phoneRegex = /^(\+886|0)?[0-9]{8,10}$/
          return phoneRegex.test(value.replace(/[-\s]/g, ''))
        },
        message: '請輸入有效的電話號碼'
      },
      emailFormat: {
        validator: function(value, element) {
          return this.optional(element) || /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/.test(value)
        },
        message: '請輸入有效的Email格式'
      }
    },
    // 驗證規則
    rules: {
      name: {
        required: true,
        minlength: 2,
        maxlength: 50
      },
      email: {
        required: true,
        email: true,
        emailFormat: true
      },
      phone: {
        phoneFormat: true
      },
      address: {
        maxlength: 200
      },
      subject: {
        required: true,
        minlength: 2,
        maxlength: 100
      },
      message: {
        required: true,
        minlength: 10,
        maxlength: 250
      },
      agree_terms: {
        required: true
      }
    },
    // 錯誤訊息
    messages: {
      name: {
        required: props.translations.validation?.name_required || '姓名為必填欄位',
        minlength: props.translations.validation?.name_minlength || '姓名至少需要2個字元',
        maxlength: props.translations.validation?.name_maxlength || '姓名不能超過50個字元'
      },
      email: {
        required: props.translations.validation?.email_required || 'Email為必填欄位',
        email: props.translations.validation?.email_format || '請輸入有效的Email格式'
      },
      phone: {
        phoneFormat: props.translations.validation?.phone_format || '請輸入有效的電話號碼'
      },
      address: {
        maxlength: props.translations.validation?.address_maxlength || '地址不能超過200個字元'
      },
      subject: {
        required: props.translations.validation?.subject_required || '主旨為必填欄位',
        minlength: props.translations.validation?.subject_minlength || '主旨至少需要2個字元',
        maxlength: props.translations.validation?.subject_maxlength || '主旨不能超過100個字元'
      },
      message: {
        required: props.translations.validation?.message_required || '內容為必填欄位',
        minlength: props.translations.validation?.message_minlength || '內容至少需要10個字元',
        maxlength: props.translations.validation?.message_maxlength || '內容不能超過250個字元'
      },
      agree_terms: {
        required: props.translations.validation?.agree_required || '請同意隱私權保護政策'
      }
    },
    // 自訂錯誤位置處理
    errorPlacement: function(error, element) {
      if (element.attr('type') === 'checkbox' && element.attr('name') === 'agree_terms') {
        const container = element.closest('.checkbox-select').parent().find('> .errorTxt').first()
        container.html('<span>' + error.text() + '</span>')
      } else {
        const container = element.closest('.controller').find('.errorTxt')
        if (container.length) container.html('<span>' + error.text() + '</span>')
      }
    },
    // 自訂高亮處理
    highlight: function(element) {
      $(element).addClass('error')
      if (element.name === 'agree_terms') {
        $(element).closest('.checkbox-container').addClass('error')
      }
    },
    // 自訂取消高亮處理
    unhighlight: function(element) {
      $(element).removeClass('error')
      if (element.name === 'agree_terms') {
        $(element).closest('.checkbox-container').removeClass('error')
        const container = $(element).closest('.checkbox-select').parent().find('> .errorTxt').first()
        container.empty()
      } else {
        const container = $(element).closest('.controller').find('.errorTxt')
        container.empty()
      }
    },
    // 提交處理
    submitHandler: handleSubmit,
    // 驗證失敗處理
    invalidHandler: function(event, validator) {
      // 使用翻譯變數顯示錯誤訊息
      sweetAlert.showToast(props.translations.required_field || '請檢查表單欄位', 'error')
    }
  })
}

// Vue 表單提交處理
const onSubmit = (event) => {
  event.preventDefault()

  // 清除之前的訊息
  successMessage.value = ''
  errorMessages.value = []

  if (window.$ && $('#customer-service-form').data('validator')) {
    // 觸發驗證，通過後會自動調用 submitHandler
    $('#customer-service-form').valid()
  } else {
    // 如果 jQuery Validation 沒載入，直接提交
    handleSubmit()
  }
}

// 處理表單提交
const handleSubmit = async () => {
  // 準備提交的資料
  const submitData = {
    ...form,
    agree_terms: form.agree_terms ? '1' : '0', // 確保符合 Laravel accepted 驗證
    _token: props.csrfToken || document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
  }

  // 顯示確認對話框
  sweetAlert.confirm(
    props.translations.submit_btn || '確認送出',
    async () => {
      // 顯示 Loading
      $loading.showLoading(props.translations.submitting || '送出中...')
      loading.value = true

      try {
        // 送出請求
        const response = await $http.post(props.submitUrl, submitData)

        // 使用統一的結果處理方法
        sweetAlert.resultData(response.data, null, () => {
          if (response.data.status) {
            // 成功時清空表單
            resetForm()
            successMessage.value = response.data.msg || props.translations.submit_success || '您的訊息已成功送出，我們會盡快與您聯繫。'
          } else {
            // 失敗時顯示錯誤訊息
            errorMessages.value = [response.data.msg || props.translations.submit_failed || '送出失敗']
          }

          // 處理重導向
          if (response.data.redirect) {
            window.location.href = response.data.redirect
          }
        })

      } catch (error) {
        if (error.response?.status === 422) {
          // 處理驗證錯誤
          const errors = error.response.data.errors || {}

          // 顯示後端驗證錯誤
          showBackendErrors(errors, props.translations)

          // 收集錯誤訊息
          errorMessages.value = Object.values(errors).flat()
        } else {
          // 一般錯誤
          const errorMsg = error.response?.data?.message || error.message || props.translations.submit_failed || '送出失敗，請稍後再試'
          errorMessages.value = [errorMsg]

          sweetAlert.showToast(errorMsg, 'error')
        }
      } finally {
        // 隱藏 Loading
        $loading.hideLoading()
        loading.value = false
      }
    },
    props.translations.confirm_submit || '確定要提交客服表單嗎？'
  )
}

// 重置表單
const resetForm = () => {
  form.name = ''
  form.email = ''
  form.phone = ''
  form.address = ''
  form.subject = ''
  form.message = ''
  form.agree_terms = false

  // 清除驗證狀態
  if (window.$ && $('#customer-service-form').data('validator')) {
    const validator = $('#customer-service-form').data('validator')
    validator.resetForm()
    $('#customer-service-form').find('.error').removeClass('error')
    $('#customer-service-form').find('.errorTxt').empty()
  }
}

// 組件掛載
onMounted(() => {
  nextTick(() => {
    // 初始化表單驗證
    initFormValidation()
  })
})
</script>

<style scoped>
.alert {
  padding: 15px;
  margin-bottom: 20px;
  border: 1px solid transparent;
  border-radius: 4px;
}

.alert-success {
  color: #3c763d;
  background-color: #dff0d8;
  border-color: #d6e9c6;
}

.alert-danger {
  color: #a94442;
  background-color: #f2dede;
  border-color: #ebccd1;
}

.alert ul {
  margin: 0;
  padding-left: 20px;
}
</style>