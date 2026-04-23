<template>
  <form id="login-form" @submit.prevent="onSubmit">
    <div class="form-keyin-div">
      <div class="item">
        <div class="label">{{ props.texts.accountEmail }}<span>*</span></div>
        <div class="controller">
          <input 
            type="email" 
            :placeholder="props.texts.placeholderEmail" 
            v-model="form.email" 
            name="email"
            autocomplete="username"
            required
          >
          <div class="errorTxt"></div>
        </div>                                       
      </div> 
      
      <div class="item">
        <div class="label">{{ props.texts.password }}</div>
        <div class="controller">
          <div class="password-toggle">
            <input 
              :type="showPassword ? 'text' : 'password'" 
              :placeholder="props.texts.placeholderPassword" 
              v-model="form.password" 
              name="password"
              autocomplete="off"
              required
            >
            <i @click="togglePassword"></i>
          </div>
          <div class="errorTxt"></div>    
        </div>                                    
      </div>
      
      <div class="item action">
        <div class="two-cols">
          <div class="col01">
            <div class="checkbox-select">
              <label class="checkbox-container">
                <input type="checkbox" v-model="form.remember">
                <span class="checkmark"></span>
              </label>   
              <span class="desc">{{ props.texts.keepLogin }}</span>
            </div>
          </div>
          <div class="col02">
            <div class="sub-two-cols">
              <div class="sub-col01">
                <button class="btn-blue" type="submit" :disabled="loading">
                  {{ loading ? props.texts.loginProcessing : props.texts.loginBtn }}
                </button>
              </div>
              <div class="sub-col02">
                <a :href="route('member.password.forgot')">{{ props.texts.forgotPassword }}</a>
              </div>
            </div>
          </div>        
        </div>    
      </div>
      
      <div class="item separe"><span>{{ props.texts.otherLogin }}</span></div>
      
      <div class="item other-login">
        <a :href="route('member.social.redirect', 'google')" :title="props.texts.googleLogin">
          <img :src="asset('frontend/images/icon_google_logo.svg')" :alt="props.texts.googleLogin">
        </a>
        <a :href="route('member.social.redirect', 'line')" :title="props.texts.lineLogin">
          <img :src="asset('frontend/images/icon_line_logo.svg')" :alt="props.texts.lineLogin">
        </a>
      </div>
    </div>        
  </form>
</template>

<script setup>
import { ref, reactive, inject, onMounted, nextTick } from 'vue'
import { useFormValidation } from '@/composables/frontend/useFormValidation'

// Props
const props = defineProps({
  texts: {
    type: Object,
    default: () => ({
      accountEmail: '帳號(Email)',
      password: '密碼',
      placeholderEmail: '請輸入 Email',
      placeholderPassword: '請輸入密碼',
      keepLogin: '保持登入狀態',
      loginBtn: '登入',
      loginProcessing: '登入中...',
      forgotPassword: '忘記密碼？',
      otherLogin: '其他登入方式',
      googleLogin: '使用 Google 登入',
      lineLogin: '使用 LINE 登入',
      validationRequired: '此欄位必填',
      validationEmail: '請輸入有效的Email格式',
      required: '必填'
    })
  }
})

const $http = inject('$http')
const sweetAlert = inject('$sweetAlert')
const $loading = inject('$loading')
const asset = inject('asset')
const route = inject('route')
const { setupFormValidation } = useFormValidation()

const form = reactive({
  email: '',
  password: '',
  remember: false
})

const loading = ref(false)
const showPassword = ref(false)

const togglePassword = () => {
  showPassword.value = !showPassword.value
}

// 設定表單驗證規則
const initFormValidation = async () => {
  await setupFormValidation('#login-form', {
    rules: {
      email: {
        required: true,
        email: true
      },
      password: {
        required: true,
        minlength: 6
      }
    },
    messages: {
      email: {
        required: props.texts.validationRequired,
        email: props.texts.validationEmail
      },
      password: {
        required: props.texts.validationRequired,
        minlength: props.texts.validationPasswordMin || '密碼至少需要6個字元'
      }
    },
    invalidHandler: function(event, validator) {
      sweetAlert.showToast(props.texts.checkFormFields || '請檢查表單欄位', 'error')
    },
    submitHandler: handleLogin
  })
}

// Vue 表單提交處理
const onSubmit = (event) => {
  event.preventDefault()
  if (window.$ && $('#login-form').data('validator')) {
    // 只觸發驗證，如果通過會自動執行 submitHandler
    $('#login-form').valid()
  } else {
    // 沒載到 jQuery Validate 就直接送
    handleLogin()
  }
}

const handleLogin = async () => {
  // 顯示 Loading
  $loading.showLoading(props.texts.loginProcessing)
  loading.value = true
  
  try {
    const response = await $http.post('/member/login', {
      ...form,
      _token: document.querySelector('meta[name="csrf-token"]').getAttribute('content')
    })
    
    // API 回應格式：{ result: { status, msg, redirect } }
    const result = response.data.result
    sweetAlert.resultData(result, null, () => {
      if (result.redirect) {
        // 後端已經傳回完整網址，直接使用
        window.location.href = result.redirect
      } else {
        // 登入成功，重新載入頁面
        window.location.reload()
      }
    })
    
  } catch (error) {
    if (error.response?.status === 422) {
      // Laravel 驗證錯誤格式 - 使用jQuery顯示錯誤
      if (error.response.data.errors) {
        const backendErrors = error.response.data.errors
        Object.keys(backendErrors).forEach(field => {
          const element = $(`[name="${field}"]`)
          if (element.length > 0) {
            element.closest('.controller').find('.errorTxt').html(`<span>${backendErrors[field][0]}</span>`)
            element.addClass('error')
          }
        })
      } else {
        // 處理其他 422 錯誤（非欄位驗證錯誤）
        sweetAlert.resultData({
          status: false,
          msg: error.response.data.message || '登入失敗'
        })
      }
    } else {
      sweetAlert.resultData({
        status: false,
        msg: '登入失敗，請稍後再試'
      })
    }
  } finally {
    // 隱藏 Loading
    $loading.hideLoading()
    loading.value = false
  }
}

onMounted(() => {
  nextTick(async () => {
    // 設定 jQuery Validation
    await initFormValidation()
  })
})
</script>