<template>
  <form id="register-form" @submit.prevent="onSubmit">
    <div class="form-keyin-div">
      <div class="item">
        <div class="label">{{ props.texts.accountEmail }}<span>*</span></div>
        <div class="controller">
          <input 
            type="email" 
            :placeholder="props.texts.placeholderEmail" 
            v-model="form.account" 
            name="account"
            autocomplete="off"
            required
          >
          <div class="errorTxt"></div>
        </div>                                       
      </div> 
      
      <div class="item">
        <div class="label">{{ props.texts.password }}<span>*</span></div>
        <div class="controller">
          <div class="password-toggle">
            <input 
              :type="showPassword ? 'text' : 'password'" 
              :placeholder="props.texts.placeholderPassword" 
              v-model="form.password" 
              name="password"
              id="password"
              autocomplete="new-password"
              minlength="6" 
              maxlength="16" 
              required
            >
            <i @click="togglePassword"></i>
          </div>
          <div class="errorTxt"></div>    
        </div>                                    
      </div> 
      
      <div class="item">
        <div class="label">{{ props.texts.passwordConfirm }}<span>*</span></div>
        <div class="controller">
          <div class="password-toggle">
            <input 
              :type="showConfirmPassword ? 'text' : 'password'" 
              :placeholder="props.texts.placeholderPasswordConfirm" 
              v-model="form.passwordVerify" 
              name="passwordVerify"
              autocomplete="new-password"
              minlength="6" 
              maxlength="16" 
              required
            >
            <i @click="toggleConfirmPassword"></i>
          </div>
          <div class="errorTxt"></div>    
        </div>                                    
      </div>
      
      <div class="item">
        <div class="label">{{ props.texts.name }}<span>*</span></div>
        <div class="controller">
          <input 
            type="text" 
            :placeholder="props.texts.placeholderName" 
            v-model="form.user" 
            name="user"
            autocomplete="off"
            required
          >
          <div class="errorTxt"></div>
        </div>                                    
      </div>
      
      <div class="item">
        <div class="label">{{ props.texts.birthday }}<span>*</span></div>
        <div class="controller">
          <div class="calendar-select">
            <input
              type="text"
              :placeholder="props.texts.placeholderDate"
              name="birthday"
              ref="dateInput"
              v-model="form.birthday"
              readonly
              required
            >
            <i @click="openDatePicker"></i>
          </div>
          <div class="errorTxt"></div>
        </div>
      </div>
      
      <div class="item">
        <div class="label">{{ props.texts.placeResidence }}<span>*</span></div>
        <div class="controller">
          <div class="dropdown-select" data-id="dropdownPlace" :class="{ 'active': showDropdown }">
            <input type="hidden" v-model="form.placeResidence" name="placeResidence">
            <button type="button" @click="toggleDropdown">
              <span><b>{{ selectedPlace || props.texts.placeholderResidence }}</b></span>
              <i></i>
            </button>
            <div class="sub-menu" v-show="showDropdown">                                                    
              <div class="sub-item" v-for="place in places" :key="place.value">
                <input 
                  type="radio" 
                  :value="place.value" 
                  v-model="form.placeResidence" 
                  @change="selectPlace(place)"
                >
                <span>{{ place.text }}</span>
              </div>
            </div>
          </div>  
          <div class="errorTxt"></div>
        </div>  
      </div>
      
      <div class="item">
        <div class="label">{{ props.texts.gender }}<span>*</span></div>
        <div class="controller">
          <div class="sex-select">
            <label class="radio-container">{{ props.texts.genderMale }}
              <input type="radio" v-model="form.sex" name="sex" value="male" >
              <span class="checkmark"></span>
            </label>
            <label class="radio-container">{{ props.texts.genderFemale }}
              <input type="radio" v-model="form.sex" name="sex" value="female">
              <span class="checkmark"></span>
            </label>
          </div>
          <div class="errorTxt"></div>    
        </div>                                                                       
      </div>                       
      
      <div class="item action">
        <div class="two-cols">
          <div class="col">
            <div class="checkbox-select">
              <label class="checkbox-container">
                <input type="checkbox" v-model="form.agree_terms" name="agree_terms" required>
                <span class="checkmark"></span>
               
                <span class="desc" v-html="props.texts.agreeTermsText"></span>
              </label> 
            </div>
            <div class="errorTxt"></div>                                               
          </div>
          <div class="col">                                            
            <button class="btn-blue" type="submit" :disabled="loading">
              {{ loading ? props.texts.registerProcessing : props.texts.registerBtn }}
            </button>
          </div>        
        </div>    
      </div>
    </div>        
  </form>
</template>

<script setup>
import { ref, reactive, onMounted, nextTick, watch, inject } from 'vue'
import { useFormValidation } from '@/composables/frontend/useFormValidation'
import { useFlatpickr } from '@/composables/frontend/useFlatpickr'

// Props
const props = defineProps({
  cities: {
    type: Array,
    required: true
  },
  locale: {
    type: String,
    default: 'zh_TW'
  },
  texts: {
    type: Object,
    default: () => ({
      accountEmail: '帳號(Email)',
      password: '密碼',
      passwordConfirm: '確認密碼',
      name: '姓名',
      birthday: '生日',
      placeResidence: '居住地',
      gender: '性別',
      genderMale: '男性',
      genderFemale: '女性',
      agreeTerms: '我已詳閱並同意',
      termsService: '服務條款',
      privacyPolicy: '隱私權政策',
      registerBtn: '註冊',
      registerProcessing: '註冊中...',
      placeholderEmail: '請輸入Email',
      placeholderPassword: '請輸入 6-16 位英數字碼',
      placeholderPasswordConfirm: '請再輸入一次密碼',
      placeholderName: '請輸入姓名',
      placeholderDate: '請選擇日期',
      placeholderResidence: '請選擇居住地',
      placeholderGender: '請選擇性別',
      validationRequired: '此欄位必填',
      validationEmail: '請輸入有效的Email格式',
      validationPasswordMin: '密碼至少需要6個字元',
      validationPasswordConfirm: '密碼確認不一致',
      required: '必填'
    })
  }
})

const $http = inject('$http')
const sweetAlert = inject('$sweetAlert')
const $loading = inject('$loading')

// 使用表單驗證 composable
const { setupFormValidation } = useFormValidation()

const form = reactive({
  account: '',
  password: '',
  passwordVerify: '',
  user: '',
  birthday: '',
  placeResidence: '',
  sex: 'male',
  agree_terms: false
})

const errors = ref({})
const loading = ref(false)
const showPassword = ref(false)
const showConfirmPassword = ref(false)
const showDropdown = ref(false)
const selectedPlace = ref('')
const places = ref(props.cities.map(city => ({
  value: city.id ? city.id.toString() : '',
  text: city.name || ''
})))

// Flatpickr 日期選擇器
const dateInput = ref(null)
const { initFlatpickr, openDatePicker } = useFlatpickr({
  locale: props.locale,
  dateFormat: 'Y-m-d',
  maxDate: 'today'
})

const togglePassword = () => {
  showPassword.value = !showPassword.value
}

const toggleConfirmPassword = () => {
  showConfirmPassword.value = !showConfirmPassword.value
}

const toggleDropdown = (e) => {
  e.preventDefault()
  e.stopPropagation()
  showDropdown.value = !showDropdown.value
}

const selectPlace = (place) => {
  selectedPlace.value = place.text
  form.placeResidence = place.value
  showDropdown.value = false
  
  // 觸發隱藏欄位驗證
  if (window.$ && $('#register-form').data('validator')) {
    $('[name="placeResidence"]').valid()
  }
}


// 使用 useFormValidation 設定表單驗證
const initFormValidation = async () => {
  await setupFormValidation('#register-form', {
    // 自訂驗證方法
    customMethods: {
      accountFormat: {
        validator: function(value, element) {
          return this.optional(element) || /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/.test(value)
        },
        message: '請輸入有效的Email格式'
      },
      passwordFormat: {
        validator: function(value, element) {
          if (this.optional(element)) return true
          if (value.length < 6 || value.length > 16) return false
          return /[a-zA-Z]/.test(value) && /[0-9]/.test(value)
        },
        message: '密碼須為6-16位英數字碼'
      },
      dateFormat: {
        validator: function(value, element) {
          if (this.optional(element)) return true
          const dateRegex = /^\d{4}-\d{2}-\d{2}$/
          if (!dateRegex.test(value)) return false
          const date = new Date(value)
          return date instanceof Date && !isNaN(date) && value === date.toISOString().split('T')[0]
        },
        message: '請選擇有效的日期'
      }
    },
    // 驗證規則
    rules: {
      account: {
        required: true,
        email: true,
        accountFormat: true
      },
      password: {
        required: true,
        minlength: 6,
        maxlength: 16,
        passwordFormat: true
      },
      passwordVerify: {
        required: true,
        equalTo: '#password'
      },
      user: {
        required: true,
        minlength: 2,
        maxlength: 50
      },
      birthday: {
        required: true,
        dateFormat: true
      },
      placeResidence: {
        required: true
      },
      sex: {
        required: true
      },
      agree_terms: {
        required: true
      }
    },
    // 錯誤訊息
    messages: {
      account: {
        required: props.texts.validationRequired || '此欄位必填',
        email: props.texts.validationEmail || '請輸入有效的Email格式'
      },
      password: {
        required: props.texts.validationRequired || '此欄位必填',
        minlength: props.texts.validationPasswordMin || '密碼至少需要6個字元',
        maxlength: '密碼不能超過16個字元'
      },
      passwordVerify: {
        required: props.texts.validationRequired || '此欄位必填',
        equalTo: props.texts.validationPasswordConfirm || '兩次輸入的密碼不一致'
      },
      user: {
        required: props.texts.validationRequired || '此欄位必填',
        minlength: '姓名至少需要2個字元',
        maxlength: '姓名不能超過50個字元'
      },
      birthday: {
        required: props.texts.validationRequired || '此欄位必填',
        dateFormat: props.texts.validationBirthday || '請選擇有效的日期'
      },
      placeResidence: {
        required: props.texts.validationRequired || '此欄位必填'
      },
      sex: {
        required: props.texts.validationRequired || '此欄位必填'
      },
      agree_terms: {
        required: props.texts.validationAgreeTerms || '請同意服務條款'
      }
    },
    // 自訂錯誤位置處理
    errorPlacement: function(error, element) {
      if (element.attr('type') === 'checkbox' && element.attr('name') === 'agree_terms') {
        const container = element.closest('.checkbox-select').parent().find('> .errorTxt').first();
        container.html('<span>' + error.text() + '</span>');
      } else if (element.attr('name') === 'birthday') {
        const container = element.closest('.controller').find('.errorTxt');
        if (container.length) container.html('<span>' + error.text() + '</span>');
      } else {
        const container = element.closest('.controller').find('.errorTxt');
        if (container.length) container.html('<span>' + error.text() + '</span>');
      }
    },
    // 自訂高亮處理
    highlight: function(element) {
      $(element).addClass('error');
      if (element.name === 'agree_terms') {
        $(element).closest('.checkbox-container').addClass('error');
      } else if (element.name === 'birthday') {
        $(element).closest('.calendar-select').addClass('error');
      }
    },
    // 自訂取消高亮處理
    unhighlight: function(element) {
      $(element).removeClass('error');
      if (element.name === 'agree_terms') {
        $(element).closest('.checkbox-container').removeClass('error');
        const container = $(element).closest('.checkbox-select').parent().find('> .errorTxt').first();
        container.empty();
      } else if (element.name === 'birthday') {
        $(element).closest('.calendar-select').removeClass('error');
        const container = $(element).closest('.controller').find('.errorTxt');
        container.empty();
      } else {
        const container = $(element).closest('.controller').find('.errorTxt');
        container.empty();
      }
    },
    // 驗證失敗處理
    invalidHandler: function(event, validator) {
      sweetAlert.showToast(props.texts.checkFormFields || '請檢查表單欄位', 'error')
    },
    // 提交處理
    submitHandler: handleRegister
  });
};

// Vue 表單提交處理
const onSubmit = (event) => {
  event.preventDefault();
  if (window.$ && $('#register-form').data('validator')) {
    // 觸發驗證，通過後會自動調用 submitHandler
    $('#register-form').valid();
  } else {
    // 沒載到 jQuery Validate 就直接送
    handleRegister();
  }
};

const handleRegister = () => {
  // 送出前確認
  sweetAlert.confirm(
    props.texts.registerBtn,
    async () => {
      // 確認後才執行 AJAX
      // 顯示 Loading
      $loading.showLoading(props.texts.registerProcessing)
      loading.value = true
      
      try {
        const response = await $http.post('/member/register', form)
        
        // 使用 resultData 統一處理結果
        const result = response.data.result || {
          status: response.data.success,
          msg: response.data.message || '註冊成功',
          redirect: response.data.redirect || '/member/email-verification'
        }
        
        sweetAlert.resultData(result, null, () => {
          if (result.redirect) {
            window.location.href = result.redirect
          }
        })
        
      } catch (error) {
    if (error.response?.status === 422) {
      // 顯示後端驗證錯誤
      const backendErrors = error.response.data.errors || {}
      Object.keys(backendErrors).forEach(field => {
        const element = $(`[name="${field}"]`)
        if (element.length > 0) {
          element.closest('.controller').find('.errorTxt').html(`<span>${backendErrors[field][0]}</span>`)
          element.addClass('error')
        }
      })
      sweetAlert.showToast(props.texts.checkFormFields || '請檢查表單欄位', 'error')
    } else {
      sweetAlert.resultData({
        status: false,
        msg: error.response?.data?.message || '註冊失敗',
        text: '請檢查您的網路連線或稍後再試'
      })
    }
      } finally {
        // 隱藏 Loading
        $loading.hideLoading()
        loading.value = false
      }
    },
    props.texts.confirmRegister || '確定要提交註冊資料嗎？'
  )
}

// 縣市資料已通過 props 傳入，不需要 API 呼叫

// 監聽生日欄位變化，觸發驗證
watch(() => form.birthday, (newValue) => {
  if (window.$ && $('#register-form').data('validator')) {
    // 觸發隱藏欄位驗證
    $('[name="birthday"]').valid()
  }
})

onMounted(() => {
  // 縣市資料已通過 props 初始化

  nextTick(async () => {
    // 設定 jQuery Validation
    initFormValidation()

    // 初始化 Flatpickr 日期選擇器
    if (dateInput.value) {
      await initFlatpickr(dateInput.value, {
        onChange: (selectedDates, dateStr) => {
          form.birthday = dateStr
          // 觸發表單驗證
          nextTick(() => {
            if (window.$ && $('#register-form').data('validator')) {
              $('[name="birthday"]').valid()
            }
          })
        }
      })
    }
  })

  // 點擊外部關閉下拉選單
  document.addEventListener('click', (e) => {
    // 延遲執行，避免與按鈕點擊事件衝突
    setTimeout(() => {
      const dropdown = document.querySelector('.dropdown-select')
      if (dropdown && !dropdown.contains(e.target)) {
        showDropdown.value = false
      }
    }, 100)
  })
})
</script>

