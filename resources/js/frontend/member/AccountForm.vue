<template>
  <div class="block-title">
    <div class="big-title">
      <h1>{{ props.texts.profile }}</h1>
    </div>                         
  </div>
  <div class="boxer">
    <form id="account-form" @submit.prevent="handleSubmit">
      <div class="form-keyin-div">
        <!-- 隱藏的 username 欄位供密碼管理器使用 -->
        <input 
          type="text" 
          name="username" 
          :value="form.email" 
          autocomplete="username" 
          style="display: none;"
        >
        
        <!-- 密碼重設 -->
        <div class="item">
          <div class="two-cols">
            <div class="col">               
              <div class="label">{{ props.texts.password }}</div>                     
              <div class="controller">
                <div class="password-toggle">
                  <input 
                    type="password" 
                    name="password"
                    v-model="form.password" 
                    :placeholder="props.texts.placeholderPassword" 
                    data-change-type="text"
                    autocomplete="new-password"
                  >
                  <i></i>
                </div> 
                <div class="errorTxt">
                  <span v-if="errors.password">{{ errors.password }}</span>
                </div>
              </div>                                            
            </div>
            <div class="col">
              <div class="label">{{ props.texts.confirmPassword }}</div>                     
              <div class="controller">
                <div class="password-toggle">
                  <input 
                    type="password" 
                    name="password_confirmation"
                    v-model="form.password_confirmation" 
                    :placeholder="props.texts.placeholderConfirmPassword" 
                    data-change-type="text"
                    autocomplete="new-password"
                  >
                  <i></i>
                </div> 
                <div class="errorTxt">
                  <span v-if="errors.password_confirmation">{{ errors.password_confirmation }}</span>
                </div>
              </div>
            </div>
          </div>        
        </div>
        
        <!-- 基本資料 -->
        <div class="item">
          <div class="two-cols">
            <div class="col">               
              <div class="label">{{ props.texts.name }}<span>*</span></div>                     
              <div class="controller">
                <input 
                  type="text" 
                  name="name"
                  v-model="form.name" 
                  :placeholder="props.texts.placeholderName"
                  autocomplete="name"
                > 
                <div class="errorTxt">
                  <span v-if="errors.name">{{ errors.name }}</span>
                </div>
              </div>                                            
            </div>
            <div class="col">
              <div class="label">{{ props.texts.email }}<span>*</span></div>                     
              <div class="controller">
                <input 
                  type="email" 
                  name="email"
                  v-model="form.email" 
                  :placeholder="props.texts.placeholderEmail" 
                  readonly
                  class="readonly-field"
                  autocomplete="email"
                >
                <div class="errorTxt">
                  <span v-if="errors.email">{{ errors.email }}</span>
                </div>
              </div>
            </div>
          </div>        
        </div>
        
        <!-- 聯絡資訊 -->
        <div class="item">
          <div class="two-cols">
            <div class="col">               
              <div class="label">{{ props.texts.phone }}</div>                     
              <div class="controller">
                <input 
                  type="tel" 
                  name="phone"
                  v-model="form.phone" 
                  :placeholder="props.texts.placeholderPhone"
                  autocomplete="tel"
                > 
                <div class="errorTxt">
                  <span v-if="errors.phone">{{ errors.phone }}</span>
                </div>
              </div>                                            
            </div>
            <div class="col">
              <div class="label">{{ props.texts.birthday }}（{{ props.texts.canOnlySetOnce }}）<span>*</span></div>                     
              <div class="controller">
                <div class="calendar-select">
                  <input 
                    type="text" 
                    name="birthdate"
                    v-model="form.birthdate" 
                    placeholder="尚未設定"
                    readonly
                    class="readonly-field datepicker"
                    id="datepicker"
                  >
                  <i id="calendarTrigger" style="opacity: 0.5; cursor: not-allowed;"></i>
                </div>
                <div class="errorTxt">
                  <span v-if="errors.birthdate">{{ errors.birthdate }}</span>
                </div>    
              </div>
            </div>
          </div>        
        </div>
        
        <!-- 個人資訊 -->
        <div class="item">
          <div class="two-cols">
            <div class="col">               
              <div class="label">{{ props.texts.idNumber }}</div>                     
              <div class="controller">
                <input 
                  type="text" 
                  name="id_number"
                  v-model="form.id_number" 
                  :placeholder="props.texts.placeholderIdNumber"
                > 
                <div class="errorTxt">
                  <span v-if="errors.id_number">{{ errors.id_number }}</span>
                </div>
              </div>                                                                                                 
            </div>
            <div class="col">
              <div class="label">{{ props.texts.gender }}<span>*</span></div>                     
              <div class="controller">
                <div class="dropdown-select" data-id="dropdownSex" :class="{ 'active': showGenderDropdown }">
                  <button type="button" @click="toggleGenderDropdown">
                    <span>
                      <b>{{ getGenderText(form.gender) }}</b>
                    </span>
                    <i></i>
                  </button>
                  <div class="sub-menu" v-show="showGenderDropdown">
                    <div class="sub-item" @click="selectGender('male')">
                      <input type="radio" name="gender" value="male" :checked="form.gender === 'male'">
                      <span>{{ props.texts.genderMale }}</span>
                    </div>
                    <div class="sub-item" @click="selectGender('female')">
                      <input type="radio" name="gender" value="female" :checked="form.gender === 'female'">
                      <span>{{ props.texts.genderFemale }}</span>
                    </div>
                  </div>
                  <!-- 隱藏欄位供驗證使用 -->
                  <input type="hidden" name="gender" v-model="form.gender">
                </div>
                <div class="errorTxt">
                  <span v-if="errors.gender">{{ errors.gender }}</span>
                </div>                                                          
              </div> 
            </div>
          </div>        
        </div>
        
        <!-- 居住地資訊 -->
        <div class="item">
          <div class="label">{{ texts.residence || texts.place_residence || '居住地' }}<span>*</span></div>
          <div class="two-cols">
            <div class="col">                    
              <div class="controller">
                <div class="dropdown-select" data-id="dropdownCity" :class="{ 'active': showCityDropdown, 'error': errors.residence_city }">
                  <button type="button" @click="toggleCityDropdown">
                    <span>
                      <b>{{ getCityName(form.residence_city) || props.texts.selectCity }}</b>
                    </span>
                    <i></i>
                  </button>
                  <div class="sub-menu" v-show="showCityDropdown">
                    <div 
                      class="sub-item" 
                      v-for="city in cities" 
                      :key="city.id"
                      @click="selectCity(city.id)"
                    >
                      <input type="radio" name="residence_city" :value="city.id" :checked="form.residence_city == city.id">
                      <span>{{ city.name }}</span>
                    </div>
                  </div>
                  <!-- 隱藏欄位供驗證使用 -->
                  <input type="hidden" name="residence_city" v-model="form.residence_city">
                </div>
                <div class="errorTxt">
                  <span v-if="errors.residence_city">{{ errors.residence_city }}</span>
                </div>
              </div>                                            
            </div>
            <div class="col">                   
              <div class="controller">
                <div class="dropdown-select" data-id="dropdownArea" :class="{ 'active': showAreaDropdown, 'error': errors.residence_area }">
                  <button type="button" @click="toggleAreaDropdown" :disabled="!form.residence_city">
                    <span>
                      <b>{{ getAreaName(form.residence_area) || props.texts.selectArea }}</b>
                    </span>
                    <i></i>
                  </button>
                  <div class="sub-menu" v-show="showAreaDropdown">
                    <div 
                      class="sub-item" 
                      v-for="area in areas" 
                      :key="area.id"
                      @click="selectArea(area.id)"
                    >
                      <input type="radio" name="residence_area" :value="area.id" :checked="form.residence_area == area.id">
                      <span>{{ area.name }}</span>
                    </div>
                  </div>
                  <!-- 隱藏欄位供驗證使用 -->
                  <input type="hidden" name="residence_area" v-model="form.residence_area">
                </div>
                <div class="errorTxt">
                  <span v-if="errors.residence_area">{{ errors.residence_area }}</span>
                </div>
              </div>
            </div>
          </div>
          <div class="full-width">
            <div class="controller">
              <input 
                type="text" 
                name="address"
                v-model="form.address" 
                :placeholder="props.texts.placeholderAddress"
              > 
              <div class="errorTxt">
                <span v-if="errors.address">{{ errors.address }}</span>
              </div>
            </div> 
          </div>        
        </div>                                 
        
        <!-- 提交按鈕 -->
        <div class="item action align-right">                                    
          <button class="btn-blue" type="submit" :disabled="loading">
            {{ loading ? props.texts.updateProcessing : props.texts.updateBtn }}
          </button>
        </div>
      </div>
    </form>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, inject, watch } from 'vue'
import { useFormValidation } from '@/composables/frontend/useFormValidation'

// Props
const props = defineProps({
  user: {
    type: Object,
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
      profile: '個人資料',
      name: '姓名',
      email: 'Email',
      phone: '手機號碼',
      birthday: '生日',
      gender: '性別',
      password: '密碼',
      confirmPassword: '確認密碼',
      idNumber: '身分證字號',
      residence: '居住地',
      address: '完整地址',
      updateBtn: '確認更新',
      updateProcessing: '更新中...',
      placeholderName: '請輸入姓名',
      placeholderPassword: '請輸入密碼',
      placeholderConfirmPassword: '請再輸入一次密碼',
      placeholderEmail: '請輸入Email',
      placeholderPhone: '請輸入手機號碼',
      placeholderIdNumber: '請輸入身分證字號',
      placeholderAddress: '請輸入縣市之後的完整地址',
      genderMale: '男性',
      genderFemale: '女性',
      selectGender: '請選擇性別',
      selectCity: '請選擇縣市',
      selectArea: '請選擇地區',
      confirmUpdate: '確認更新嗎?',
      checkFormFields: '請檢查表單欄位',
      updateFailed: '更新失敗，請稍後再試',
      validationPasswordMatch: '兩次輸入的密碼不一致',
      required: '必填',
      optional: '選填'
    })
  }
})

// Inject dependencies
const $http = inject('$http')
const sweetAlert = inject('$sweetAlert')
const $loading = inject('$loading')

// Form validation composable
const {
  setupFormValidation,
  validateField,
  isFormValid,
  clearAllErrors,
  showBackendErrors
} = useFormValidation()

// Reactive data
const loading = ref(false)
const errors = ref({})

const cities = ref(props.cities)
const allAreas = ref(props.areas) // 所有地區資料
const areas = ref([])   // 當前縣市的地區資料

const showGenderDropdown = ref(false)
const showCityDropdown = ref(false)
const showAreaDropdown = ref(false)

// Form data
const form = reactive({
  password: '',
  password_confirmation: '',
  name: props.user.name || '',
  email: props.user.email || '',
  phone: props.user.phone || '',
  birthdate: props.user.birthdate || '',
  id_number: props.user.id_number || '',
  gender: props.user.gender || '',
  residence_city: props.user.residence_city || '',
  residence_area: props.user.residence_area || '',
  address: props.user.address || ''
})

// Methods
const initializeAreas = () => {
  // 根據當前選擇的城市初始化地區列表
  if (form.residence_city) {
    filterAreasByCity(form.residence_city)
  }
}

const filterAreasByCity = (cityId) => {
  if (!cityId) {
    areas.value = []
    return
  }
  // 從所有地區中篩選出指定縣市的地區
  areas.value = allAreas.value.filter(area => area.city_id == cityId)
}

const getCityName = (cityId) => {
  if (!cityId) return ''
  const city = cities.value.find(c => c.id == cityId)
  return city ? city.name : ''
}

const getAreaName = (areaId) => {
  if (!areaId) return ''
  const area = areas.value.find(a => a.id == areaId)
  return area ? area.name : ''
}

const getGenderText = (gender) => {
  switch (gender) {
    case 'male': return props.texts.genderMale
    case 'female': return props.texts.genderFemale
    default: return props.texts.selectGender
  }
}

// Dropdown toggles
const toggleGenderDropdown = () => {
  showGenderDropdown.value = !showGenderDropdown.value
  showCityDropdown.value = false
  showAreaDropdown.value = false
}

const toggleCityDropdown = () => {
  showCityDropdown.value = !showCityDropdown.value
  showGenderDropdown.value = false
  showAreaDropdown.value = false
}

const toggleAreaDropdown = () => {
  if (!form.residence_city) return
  showAreaDropdown.value = !showAreaDropdown.value
  showGenderDropdown.value = false
  showCityDropdown.value = false
}

// Select methods
const selectGender = (gender) => {
  form.gender = gender
  showGenderDropdown.value = false
  if (errors.value.gender) delete errors.value.gender
}

const selectCity = (cityId) => {
  form.residence_city = cityId
  form.residence_area = '' // 重置地區選擇
  showCityDropdown.value = false
  if (errors.value.residence_city) delete errors.value.residence_city
  
  // 篩選對應地區
  filterAreasByCity(cityId)
}

const selectArea = (areaId) => {
  form.residence_area = areaId
  showAreaDropdown.value = false
  if (errors.value.residence_area) delete errors.value.residence_area
}


// 設置驗證規則
const validationRules = {
  name: {
    required: true,
    minlength: 2
  },
  password: {
    minlength: 6
  },
  password_confirmation: {
    equalTo: '[name="password"]'
  },
  gender: {
    required: true
  },
  residence_city: {
    required: true
  },
  residence_area: {
    required: true  
  },
  address: {
    required: true,
    minlength: 3
  }
}

const validationMessages = {
  name: {
    required: props.texts.validationNameRequired || '姓名為必填欄位',
    minlength: props.texts.validationNameMinlength || '姓名至少需要2個字元'
  },
  birthdate: {
    required: props.texts.validationBirthdateRequired || '生日為必填欄位'
  },
  password: {
    minlength: props.texts.validationPasswordMin || '密碼至少需要6個字元'
  },
  password_confirmation: {
    equalTo: props.texts.validationPasswordMatch || '兩次輸入的密碼不一致'
  },
  gender: {
    required: props.texts.validationGenderRequired || '請選擇性別'
  },
  residence_city: {
    required: props.texts.validationCityRequired || '請選擇縣市'
  },
  residence_area: {
    required: props.texts.validationAreaRequired || '請選擇地區'
  },
  address: {
    required: props.texts.validationAddressRequired || '請輸入完整地址',
    minlength: props.texts.validationAddressMinlength || '詳細地址至少需要3個字元'
  }
}

// Form submission
const handleSubmit = async () => {
  // 使用 useFormValidation 的驗證方法
  if (!isFormValid('#account-form')) {
    sweetAlert.showToast(props.texts.checkFormFields, 'error')
    return
  }
  
  // 確認對話框
  sweetAlert.confirm(
    props.texts.confirmUpdate,
    async () => {
      // 確認後才執行更新
      await executeUpdate()
    }
  )
}

// 執行更新的函數
const executeUpdate = async () => {
  
  $loading.showLoading(props.texts.updateProcessing)
  loading.value = true
  
  
  try {
    const response = await $http.post('/member/profile', form)
    
    // 使用 resultData 統一處理結果
    const result = response.data
    
    sweetAlert.resultData(result, null, () => {
      if (result.status) {
        // 清空密碼欄位
        form.password = ''
        form.password_confirmation = ''
      }
    })
  } catch (error) {
    console.error('更新失敗:', error)
    
    if (error.response && error.response.status === 422) {
      // 使用 useFormValidation 處理後端驗證錯誤
      const validationErrors = error.response.data.errors || {}
      showBackendErrors(validationErrors)
    } else {
      sweetAlert.showToast(props.texts.updateFailed, 'error')
    }
  } finally {
    $loading.hideLoading()
    loading.value = false
  }
}

// Click outside to close dropdowns
const handleClickOutside = (e) => {
  // 檢查點擊是否在下拉選單外部
  const dropdowns = document.querySelectorAll('.dropdown-select')
  let clickedOutside = true
  
  dropdowns.forEach(dropdown => {
    if (dropdown && dropdown.contains(e.target)) {
      clickedOutside = false
    }
  })
  
  if (clickedOutside) {
    showGenderDropdown.value = false
    showCityDropdown.value = false
    showAreaDropdown.value = false
  }
}

// Lifecycle
onMounted(async () => {
  // 初始化地區列表（如果已有選中的縣市）
  initializeAreas()
  
  // 初始化表單驗證
  await setupFormValidation('#account-form', {
    rules: validationRules,
    messages: validationMessages,
    highlight: function(element) {
      $(element).addClass('error')
      // 處理下拉選單錯誤樣式
      if (element.name === 'residence_city' || element.name === 'residence_area') {
        $(element).closest('.dropdown-select').addClass('error')
      }
    },
    unhighlight: function(element) {
      $(element).removeClass('error')
      // 處理下拉選單錯誤樣式移除
      if (element.name === 'residence_city' || element.name === 'residence_area') {
        $(element).closest('.dropdown-select').removeClass('error')
      }
      // 清除錯誤訊息
      const container = $(element).closest('.controller').find('.errorTxt')
      container.empty()
    },
    invalidHandler: function(event, validator) {
      sweetAlert.showToast(props.texts.checkFormFields || '請檢查表單欄位', 'error')
    },
    submitHandler: handleSubmit
  })
  
  // 點擊外部關閉下拉選單
  document.addEventListener('click', handleClickOutside)
})
</script>

<style scoped>
/* 唯讀欄位樣式 - 暗系風格 */
.readonly-field {
  background-color: #2a2a2a !important;
  color: #888 !important;
  cursor: not-allowed !important;
  border-color: #404040 !important;
  opacity: 0.8;
}

.readonly-field:focus {
  box-shadow: none !important;
  border-color: #404040 !important;
}

/* 佈局樣式 - 組件專用 */
.three-cols {
  display: flex;
  gap: 15px;
  justify-content: center;
  flex-wrap: wrap;
}

.three-cols .col {
  flex: 1;
  min-width: 120px;
}

.full-width {
  width: 100%;
  margin-top: 15px;
}

.dropdown-select .sub-item:last-child {
  border-bottom: none;
}

.btn-blue:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

/* 下拉選單錯誤狀態 */
.dropdown-select.error {
  border-color: #dc3545 !important;
}

.dropdown-select.error button {
  border-color: #dc3545 !important;
}

@media (max-width: 768px) {
  .three-cols {
    flex-direction: column;
  }
  
  .three-cols .col {
    min-width: 100%;
  }
}
</style>