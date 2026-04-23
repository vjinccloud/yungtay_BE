import { ref, nextTick } from 'vue'
import { loadJs } from '@/utils/scriptLoader'
import SweetAlertPlugin from '@/utils/sweetalert2Plugin'
const { sweetAlertMethods } = SweetAlertPlugin

export function useFormValidation(translations = {}) {
  const validationReady = ref(false)
  const jQueryValidationLoaded = ref(false)

  // 動態載入 jQuery Validation 插件
  const loadJQueryValidation = async () => {
    if (jQueryValidationLoaded.value || (window.$ && window.$.validator)) {
      jQueryValidationLoaded.value = true
      return true
    }

    // 確保 jQuery 已載入
    if (!window.$) {
      console.error('jQuery is required for validation')
      return false
    }

    try {
      // 載入 jQuery Validation 主檔案
      await loadJs('/js/plugins/jquery-validation/jquery.validate.min.js')
      
      // 載入額外方法檔案
      await loadJs('/js/plugins/jquery-validation/additional-methods.min.js')
      
      // 可選：載入中文訊息檔案
      try {
        await loadJs('/js/plugins/jquery-validation/localization/messages_zh_TW.min.js')
      } catch (e) {
        console.warn('中文訊息檔案載入失敗，使用預設訊息')
      }

      jQueryValidationLoaded.value = true
      return true
      
    } catch (error) {
      console.error('jQuery Validation 載入失敗:', error)
      return false
    }
  }

  // 註冊自定義驗證方法 - 由各組件自行定義需要的驗證規則
  const addCustomValidationMethods = (customMethods = {}) => {
    if (!window.$ || !window.$.validator) {
      console.warn('jQuery Validation not loaded')
      return false
    }

    // 註冊傳入的自定義驗證方法
    Object.keys(customMethods).forEach(methodName => {
      const method = customMethods[methodName]
      if (method.validator && method.message) {
        $.validator.addMethod(methodName, method.validator, method.message)
      }
    })

    return true
  }

  // 通用錯誤處理函數
  const defaultErrorPlacement = (error, element) => {
    // 通用錯誤放置邏輯
    const container = element.closest('.controller').find('.errorTxt')
    if (container.length) {
      container.html('<span>' + error.text() + '</span>')
    }
  }

  // 通用高亮處理函數
  const defaultHighlight = (element) => {
    $(element).addClass('error')
    // 讓各組件自行處理特殊欄位的高亮邏輯
  }

  // 通用取消高亮處理函數
  const defaultUnhighlight = (element) => {
    $(element).removeClass('error')
    const container = $(element).closest('.controller').find('.errorTxt')
    container.empty()
  }

  // 通用驗證失敗處理 - 現在主要用 showBackendErrors，這裡保持原樣
  const defaultInvalidHandler = (event, validator) => {
    sweetAlertMethods.showToast('請檢查表單欄位', 'error')
  }

  // 不預設驗證規則，由各組件自行定義

  // 設置表單驗證 - 支援動態載入
  const setupFormValidation = async (formSelector, options = {}) => {
    // 先載入 jQuery Validation
    const loaded = await loadJQueryValidation()
    if (!loaded) {
      console.error('無法載入 jQuery Validation 插件')
      return false
    }

    // 傳入自定義驗證方法
    const customMethods = options.customMethods || {}
    if (!addCustomValidationMethods(customMethods)) {
      return false
    }

    // 使用組件傳入的規則和訊息
    const rules = options.rules || {}
    const messages = options.messages || {}

    // 預設驗證配置
    const defaultConfig = {
      errorElement: 'span',
      ignore: '', // 不忽略隱藏欄位
      rules,
      messages,
      errorPlacement: options.errorPlacement || defaultErrorPlacement,
      highlight: options.highlight || defaultHighlight,
      unhighlight: options.unhighlight || defaultUnhighlight,
      invalidHandler: options.invalidHandler || defaultInvalidHandler,
      submitHandler: options.submitHandler || function(form) {
        return false
      }
    }

    // 合併自定義配置
    const config = { ...defaultConfig, ...options }

    // 設定表單驗證
    $(formSelector).validate(config)
    validationReady.value = true
    
    return true
  }

  // 觸發特定欄位驗證
  const validateField = (fieldName) => {
    if (window.$ && validationReady.value) {
      $(`[name="${fieldName}"]`).valid()
    }
  }

  // 檢查表單是否通過驗證
  const isFormValid = (formSelector) => {
    if (window.$ && validationReady.value) {
      return $(formSelector).valid()
    }
    return false
  }

  // 清除所有錯誤
  const clearAllErrors = (formSelector) => {
    if (window.$ && validationReady.value) {
      const validator = $(formSelector).data('validator')
      if (validator) {
        validator.resetForm()
        $(formSelector).find('.error').removeClass('error')
        $(formSelector).find('.errorTxt').empty()
      }
    }
  }

  // 顯示後端驗證錯誤
  const showBackendErrors = (errors, dynamicTranslations = null) => {
    Object.keys(errors).forEach(field => {
      const element = $(`[name="${field}"]`)
      if (element.length > 0) {
        element.closest('.controller').find('.errorTxt').html(`<span>${errors[field][0]}</span>`)
        element.addClass('error')
      }
    })
    const currentTranslations = dynamicTranslations || translations
    sweetAlertMethods.showToast(currentTranslations.required_field || '請檢查表單欄位', 'error')
  }

  return {
    validationReady,
    jQueryValidationLoaded,
    loadJQueryValidation,
    setupFormValidation,
    validateField,
    isFormValid,
    clearAllErrors,
    showBackendErrors,
    addCustomValidationMethods,
    defaultErrorPlacement,
    defaultHighlight,
    defaultUnhighlight,
    defaultInvalidHandler
  }
}