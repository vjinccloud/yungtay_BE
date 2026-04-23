<template>
  <section class="section-member">            
    <div class="block-div block-01">
      <div class="block-outer">
        <div class="member-div">
          <div class="boxer">
            <div class="block-title">
              <div class="big-title">
                <h1>{{ props.texts.waitingTitle }}</h1>
              </div>                     
            </div>
            <div class="remind">
              <p>{{ props.texts.waitingMessage }}</p>
              <p v-if="userEmail" class="email-info">{{ props.texts.verificationSent }}<strong>{{ userEmail }}</strong></p>
              <p v-else class="email-info" style="color: red;">{{ props.texts.notLoggedIn }}</p>
            </div>                                
            <div class="verification-buttons">
              <button
                class="btn-primary-custom"
                @click="resendEmail"
                :disabled="isResending || countdown > 0"
              >
                {{ buttonText }}
              </button>
              <a href="/" class="btn-outline-custom">{{ props.texts.backHome }}</a>
            </div>        
          </div>    
        </div>
      </div>
    </div>                       
  </section>
</template>

<script setup>
import { ref, computed, inject, onBeforeUnmount } from 'vue'

const props = defineProps({
  userEmail: {
    type: String,
    default: ''
  },
  texts: {
    type: Object,
    default: () => ({
      waitingTitle: 'Email 驗證',
      waitingMessage: '會員認證信件已寄至您的 Email 信箱，請點擊信件中的驗證連結完成帳號驗證。',
      verificationSent: '驗證信已寄至：',
      notLoggedIn: '⚠️ 未登入狀態',
      backHome: '回首頁',
      resendProcessing: '發送中...',
      resendEmail: '重寄驗證信',
      resendEmailCountdown: '重寄驗證信 ({countdown}s)',
      resendLimit: '請稍後再試，每60秒只能發送一次。'
    })
  }
})

const $http = inject('$http')
const $sweetAlert = inject('$sweetAlert')
const $loading = inject('$loading')

const isResending = ref(false)
const countdown = ref(0)
const countdownInterval = ref(null)

const buttonText = computed(() => {
  if (isResending.value) {
    return props.texts.resendProcessing
  }
  if (countdown.value > 0) {
    return props.texts.resendEmailCountdown.replace('{countdown}', countdown.value)
  }
  return props.texts.resendEmail
})

const resendEmail = async () => {
  if (isResending.value || countdown.value > 0) return
  
  // 顯示 Loading
  $loading.showLoading('傳送驗證信中...')
  isResending.value = true
  
  try {
    const response = await $http.post('/member/resend-verification')
    
    if (response.data.result && response.data.result.status) {
      $sweetAlert.showToast('驗證信已重新寄出，請檢查您的信箱', 'success')
      startCountdown()
    } else {
      const message = response.data.result ? response.data.result.msg : '發送失敗，請稍後再試'
      $sweetAlert.showToast(message, 'error')
    }
  } catch (error) {
    console.error('重寄驗證信失敗:', error)
    
    let message = '發送失敗，請檢查網路連線'
    
    if (error.response) {
      if (error.response.status === 429) {
        message = props.texts.resendLimit
      } else if (error.response.status === 401) {
        message = '請先登入'
        // 可以選擇跳轉到登入頁面
        // setTimeout(() => {
        //   window.location.href = '/member/login'
        // }, 2000)
      } else if (error.response.data && error.response.data.result) {
        message = error.response.data.result.msg
      }
    }
    
    $sweetAlert.showToast(message, 'error')
  } finally {
    // 隱藏 Loading
    $loading.hideLoading()
    isResending.value = false
  }
}

const startCountdown = () => {
  countdown.value = 60
  countdownInterval.value = setInterval(() => {
    countdown.value--
    if (countdown.value <= 0) {
      clearInterval(countdownInterval.value)
      countdownInterval.value = null
    }
  }, 1000)
}

onBeforeUnmount(() => {
  if (countdownInterval.value) {
    clearInterval(countdownInterval.value)
  }
})
</script>

<style scoped>
.verification-buttons {
  display: flex;
  gap: 10px;
  justify-content: center;
  margin-top: 30px;
  padding: 0 10px;
}

.btn-primary-custom,
.btn-outline-custom {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 10px 20px;
  border-radius: 100px;
  text-decoration: none;
  font-size: 14px;
  font-weight: 400;
  transition: all 0.3s;
  white-space: nowrap;
  flex: 1;
  max-width: 150px;
  cursor: pointer;
}

.btn-primary-custom {
  background: linear-gradient(90deg, #2CC0E2 0%, #49D2BA 100%);
  border: none;
  color: #fff;
}

.btn-primary-custom:hover:not(:disabled) {
  background: linear-gradient(90deg, #49D2BA 0%, #2CC0E2 100%);
  color: #fff;
  text-decoration: none;
}

.btn-primary-custom:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.btn-outline-custom {
  background: transparent;
  border: 1px solid #fff;
  color: #fff;
}

.btn-outline-custom:hover {
  background-color: #2CC0E2;
  border-color: #2CC0E2;
  color: #fff;
  text-decoration: none;
}

@media (max-width: 380px) {
  .btn-primary-custom,
  .btn-outline-custom {
    padding: 10px 15px;
    font-size: 13px;
  }
}
</style>

