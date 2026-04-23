<template>
  <form id="complete-profile-form" @submit.prevent="onSubmit">
    <div class="form-keyin-div">
      <!-- 歡迎訊息 -->
      <div class="welcome-section">
        <h3>{{ props.texts.title }}</h3>
        <p>{{ props.texts.description }}</p>
      </div>
      
      <!-- 編輯用戶基本資訊 -->
      <div class="item">
        <div class="label">{{ props.texts.email }}<span>*</span></div>
        <div class="controller">
          <input 
            type="email" 
            v-model="form.email" 
            name="email"
            :placeholder="props.texts.placeholderEmail"
            autocomplete="email"
            readonly
            class="readonly-field"
          >
          <div class="errorTxt"></div>
        </div>                                    
      </div>
      
      <div class="item">
        <div class="label">{{ props.texts.name }}<span>*</span></div>
        <div class="controller">
          <input 
            type="text" 
            v-model="form.name" 
            name="name"
            :placeholder="props.texts.placeholderName"
            autocomplete="name"
          >
          <div class="errorTxt"></div>
        </div>                                    
      </div>
      
      <!-- 必填欄位 -->
      <div class="item">
        <div class="label">{{ props.texts.birthday }}<span>*</span></div>
        <div class="controller">
          <div class="calendar-select">
            <input
              type="text"
              :placeholder="props.texts.placeholderDate"
              name="birthdate"
              ref="dateInput"
              v-model="form.birthdate"
              readonly
              required
            >
            <i @click="openDatePicker"></i>
          </div>
          <div class="errorTxt"></div>
        </div>
      </div>
      
      <div class="item">
        <div class="label">{{ props.texts.gender }}<span>*</span></div>
        <div class="controller">
          <div class="sex-select">
            <label class="radio-container">{{ props.texts.genderMale }}
              <input type="radio" v-model="form.gender" name="gender" value="male" >
              <span class="checkmark"></span>
            </label>
            <label class="radio-container">{{ props.texts.genderFemale }}
              <input type="radio" v-model="form.gender" name="gender" value="female">
              <span class="checkmark"></span>
            </label>
          </div>
          <div class="errorTxt"></div>    
        </div>                                                                       
      </div>
      
      <!-- 選填欄位 -->
      <div class="item">
        <div class="label">{{ props.texts.placeResidence }}<span>*</span></div>
        <div class="controller">
          <div class="dropdown-select" data-id="dropdownPlace" :class="{ 'active': showDropdown }">
            <input type="hidden" v-model="form.address" name="address">
            <button type="button" @click="toggleDropdown">
              <span><b>{{ selectedPlace || props.texts.placeholderResidence }}</b></span>
              <i></i>
            </button>
            <div class="sub-menu" v-show="showDropdown">                                                    
              <div class="sub-item" v-for="place in places" :key="place.value">
                <input 
                  type="radio" 
                  :value="place.value" 
                  v-model="form.address" 
                  @change="selectPlace(place)"
                >
                <span>{{ place.text }}</span>
              </div>
            </div>
          </div>  
          <div class="errorTxt"></div>
        </div>  
      </div>
                       
      <div class="item action">
        <div class="single-col">                                          
          <button class="btn-blue" type="submit" :disabled="loading">
            {{ loading ? props.texts.processing : props.texts.submitBtn }}
          </button>
        </div>    
      </div>
    </div>        
  </form>
</template>

<script setup>
import { ref, reactive, onMounted, nextTick, inject } from 'vue'
import { useFormValidation } from '@/composables/frontend/useFormValidation'
import { useFlatpickr } from '@/composables/frontend/useFlatpickr'

// 接收 props
const props = defineProps({
  userEmail: {
    type: String,
    required: true
  },
  cities: {
    type: Array,
    required: true
  },
  areas: {
    type: Array,
    required: true
  },
  texts: {
    type: Object,
    default: () => ({
      title: '資料補完',
      description: '請填寫以下資訊來完成註冊流程，以便我們提供更好的服務。',
      email: 'Email',
      name: '姓名',
      phone: '手機號碼',
      birthday: '生日',
      gender: '性別',
      placeResidence: '居住地',
      genderMale: '男',
      genderFemale: '女',
      submitBtn: '完成註冊',
      processing: '更新中...',
      placeholderEmail: '請輸入Email',
      placeholderName: '請輸入姓名',
      placeholderPhone: '請輸入手機號碼',
      placeholderDate: '請選擇日期',
      placeholderResidence: '請選擇居住地',
      selectCity: '請選擇縣市',
      selectArea: '請選擇區域',
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
  name: '',
  email: props.userEmail, // 從 props 直接設定
  birthdate: '',
  gender: 'male',
  address: ''
})

const loading = ref(false)
const showDropdown = ref(false)
const selectedPlace = ref('')
const places = ref(props.cities.map(city => ({
  value: city.id ? city.id.toString() : '',
  text: city.name || ''
})))

// Flatpickr 日期選擇器
const dateInput = ref(null)
const { initFlatpickr, openDatePicker } = useFlatpickr({
  locale: 'zh_TW',
  dateFormat: 'Y-m-d',
  maxDate: 'today'
})

const toggleDropdown = () => {
  showDropdown.value = !showDropdown.value
}

const selectPlace = (place) => {
  selectedPlace.value = place.text
  form.address = place.value
  showDropdown.value = false
  
  // 清除驗證錯誤
  nextTick(() => {
    if (window.$ && $('#complete-profile-form').data('validator')) {
      $('[name="address"]').valid()
    }
  })
}


// 使用 useFormValidation 設定表單驗證
const initFormValidation = async () => {
  await setupFormValidation('#complete-profile-form', {
    customMethods: {
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
    rules: {
      name: {
        required: true,
        minlength: 2
      },
      birthdate: {
        required: true,
        dateFormat: true
      },
      gender: {
        required: true
      },
      address: {
        required: true
      }
    },
    messages: {
      name: {
        required: '請輸入姓名',
        minlength: '姓名至少需要2個字元'
      },
      birthdate: {
        required: '請選擇生日',
        dateFormat: '請選擇有效的日期'
      },
      gender: {
        required: '請選擇性別'
      },
      address: {
        required: '請選擇居住地'
      }
    },
    errorPlacement: function(error, element) {
      if (element.attr('name') === 'birthdate') {
        const container = element.closest('.controller').find('.errorTxt');
        if (container.length) container.html('<span>' + error.text() + '</span>');
      } else if (element.attr('name') === 'address') {
        const container = element.closest('.controller').find('.errorTxt');
        if (container.length) container.html('<span>' + error.text() + '</span>');
      } else {
        const container = element.closest('.controller').find('.errorTxt');
        if (container.length) container.html('<span>' + error.text() + '</span>');
      }
    },
    highlight: function(element) {
      $(element).addClass('error');
      if (element.name === 'birthdate') {
        $(element).closest('.calendar-select').addClass('error');
      } else if (element.name === 'address') {
        $(element).closest('.dropdown-select').addClass('error');
      }
    },
    unhighlight: function(element) {
      $(element).removeClass('error');
      if (element.name === 'birthdate') {
        $(element).closest('.calendar-select').removeClass('error');
        const container = $(element).closest('.controller').find('.errorTxt');
        container.empty();
      } else if (element.name === 'address') {
        $(element).closest('.dropdown-select').removeClass('error');
        const container = $(element).closest('.controller').find('.errorTxt');
        container.empty();
      } else {
        const container = $(element).closest('.controller').find('.errorTxt');
        container.empty();
      }
    },
    submitHandler: handleCompleteProfile
  });
};

// Vue 表單提交處理
const onSubmit = (event) => {
  event.preventDefault();
  if (window.$ && $('#complete-profile-form').data('validator')) {
    $('#complete-profile-form').valid();
  } else {
    handleCompleteProfile();
  }
};

const handleCompleteProfile = () => {
  sweetAlert.confirm(
    '完成註冊',
    async () => {
      $loading.showLoading('更新中...')
      loading.value = true
      
      try {
        const response = await $http.post('/member/complete-profile', {
          name: form.name,
          birthdate: form.birthdate,
          gender: form.gender,
          address: form.address
        })
        
        const result = response.data
        
        sweetAlert.resultData(result, null, () => {
          if (result.status) {
            window.location.href = '/member/account'
          }
        })
        
      } catch (error) {
        if (error.response?.status === 422) {
          const backendErrors = error.response.data.errors || {}
          Object.keys(backendErrors).forEach(field => {
            const element = $(`[name="${field}"]`)
            if (element.length > 0) {
              element.closest('.controller').find('.errorTxt').html(`<span>${backendErrors[field][0]}</span>`)
              element.addClass('error')
            }
          })
          sweetAlert.showToast('請檢查表單欄位', 'error')
        } else {
          sweetAlert.resultData({
            status: false,
            msg: error.response?.data?.message || '更新失敗',
            text: '請檢查您的網路連線或稍後再試'
          })
        }
      } finally {
        $loading.hideLoading()
        loading.value = false
      }
    },
    '確定要完成註冊嗎？'
  )
}

// email 已從 props 直接設定，不需要 AJAX 載入


onMounted(() => {
  nextTick(async () => {
    initFormValidation()

    // 初始化 Flatpickr 日期選擇器
    if (dateInput.value) {
      await initFlatpickr(dateInput.value, {
        onChange: (selectedDates, dateStr) => {
          form.birthdate = dateStr
          nextTick(() => {
            if (window.$ && $('#complete-profile-form').data('validator')) {
              $('[name="birthdate"]').valid()
            }
          })
        }
      })
    }
  })

  // 點擊外部關閉下拉選單
  document.addEventListener('click', (e) => {
    const dropdown = document.querySelector('.dropdown-select')
    if (dropdown && !dropdown.contains(e.target)) {
      showDropdown.value = false
    }
  })
})
</script>

<style scoped>
/* 歡迎訊息區塊 - 組件專用 */
.welcome-section {
  text-align: center;
  margin-bottom: 30px;
  padding: 20px;
  background: #1a1a1a;
  border-radius: 8px;
  border: 1px solid #404040;
}

.welcome-section h3 {
  color: #2CC0E2;
  margin-bottom: 10px;
  font-size: 24px;
}

.welcome-section p {
  color: #b0b0b0;
  margin: 0;
  font-size: 16px;
}

/* 單列按鈕佈局 - 組件專用 */
.single-col {
  display: flex;
  justify-content: center;
  margin-top: 20px;
}

.single-col .btn-blue {
  min-width: 150px;
  padding: 12px 30px;
  font-size: 16px;
}

/* 手機版優化 */
@media all and (max-width: 750px) {
  .welcome-section {
    padding: 15px;
    margin-bottom: 20px;
  }
  
  .welcome-section h3 {
    font-size: 20px;
  }
  
  .welcome-section p {
    font-size: 14px;
  }
}
</style>