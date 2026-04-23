import { ref, onUnmounted } from 'vue'
import { loadJs, loadCss } from '@/utils/scriptLoader'

/**
 * Flatpickr 日期選擇器 Composable
 *
 * @param {Object} options - 設定選項
 * @param {String} options.locale - 語系設定 (預設: 'zh_TW')
 * @param {String} options.dateFormat - 日期格式 (預設: 'Y-m-d')
 * @param {String|Date} options.maxDate - 最大日期 (預設: 'today')
 * @param {String|Date} options.minDate - 最小日期 (預設: null)
 * @param {Boolean} options.enableTime - 是否啟用時間選擇 (預設: false)
 * @param {Function} options.onChange - 日期變更回調函數
 *
 * @returns {Object} - 包含 refs 和 methods
 */
export function useFlatpickr(options = {}) {
  const flatpickrLoaded = ref(false)
  const flatpickrInstance = ref(null)
  const dateInputRef = ref(null)

  // 預設選項
  const defaultOptions = {
    locale: 'zh_TW',
    dateFormat: 'Y-m-d',
    maxDate: 'today',
    minDate: null,
    enableTime: false,
    disableMobile: true,
    onChange: null
  }

  // 合併選項
  const mergedOptions = { ...defaultOptions, ...options }

  /**
   * 載入 Flatpickr 資源
   */
  const loadFlatpickrResources = async () => {
    if (flatpickrLoaded.value) return true

    try {
      await Promise.all([
        loadCss('/js/plugins/flatpickr/flatpickr.min.css'),
        loadJs('/js/plugins/flatpickr/flatpickr.min.js'),
      ])

      // 嘗試載入語言包
      if (mergedOptions.locale === 'zh_TW') {
        try {
          await loadJs('/js/plugins/flatpickr/l10n/zh-tw.js')
        } catch (e) {
          console.log('zh-tw.js 載入失敗，使用預設語言')
        }
      }

      flatpickrLoaded.value = true
      return true
    } catch (error) {
      console.error('載入 Flatpickr 失敗:', error)
      return false
    }
  }

  /**
   * 初始化 Flatpickr
   * @param {HTMLElement|Ref} inputElement - 輸入框元素或 ref
   * @param {Object} customOptions - 自訂選項（可覆蓋預設）
   */
  const initFlatpickr = async (inputElement, customOptions = {}) => {
    await loadFlatpickrResources()

    if (!flatpickrLoaded.value || !window.flatpickr) {
      console.error('Flatpickr 尚未載入')
      return null
    }

    // 取得實際的 DOM 元素
    const element = inputElement?.value || inputElement
    if (!element) {
      console.error('找不到輸入框元素')
      return null
    }

    // 儲存 ref
    dateInputRef.value = element

    // 設定語言包
    if (mergedOptions.locale === 'zh_TW' && window.flatpickr.l10ns?.zh_tw) {
      window.flatpickr.localize(window.flatpickr.l10ns.zh_tw)
    }

    // 合併選項
    const flatpickrConfig = {
      dateFormat: mergedOptions.dateFormat,
      maxDate: mergedOptions.maxDate,
      minDate: mergedOptions.minDate,
      enableTime: mergedOptions.enableTime,
      disableMobile: mergedOptions.disableMobile,
      ...customOptions
    }

    // 處理 onChange 回調
    if (mergedOptions.onChange || customOptions.onChange) {
      flatpickrConfig.onChange = (selectedDates, dateStr, instance) => {
        if (customOptions.onChange) {
          customOptions.onChange(selectedDates, dateStr, instance)
        } else if (mergedOptions.onChange) {
          mergedOptions.onChange(selectedDates, dateStr, instance)
        }
      }
    }

    // 建立 Flatpickr 實例
    flatpickrInstance.value = window.flatpickr(element, flatpickrConfig)

    return flatpickrInstance.value
  }

  /**
   * 開啟日期選擇器
   */
  const openDatePicker = () => {
    if (flatpickrInstance.value) {
      flatpickrInstance.value.open()
    }
  }

  /**
   * 關閉日期選擇器
   */
  const closeDatePicker = () => {
    if (flatpickrInstance.value) {
      flatpickrInstance.value.close()
    }
  }

  /**
   * 設定日期
   * @param {String|Date} date - 日期值
   * @param {Boolean} triggerChange - 是否觸發 onChange 回調
   */
  const setDate = (date, triggerChange = false) => {
    if (flatpickrInstance.value) {
      flatpickrInstance.value.setDate(date, triggerChange)
    }
  }

  /**
   * 清除日期
   */
  const clearDate = () => {
    if (flatpickrInstance.value) {
      flatpickrInstance.value.clear()
    }
  }

  /**
   * 銷毀 Flatpickr 實例
   */
  const destroyFlatpickr = () => {
    if (flatpickrInstance.value) {
      flatpickrInstance.value.destroy()
      flatpickrInstance.value = null
    }
  }

  // 組件卸載時自動清理
  onUnmounted(() => {
    destroyFlatpickr()
  })

  return {
    // Refs
    flatpickrLoaded,
    flatpickrInstance,
    dateInputRef,

    // Methods
    loadFlatpickrResources,
    initFlatpickr,
    openDatePicker,
    closeDatePicker,
    setDate,
    clearDate,
    destroyFlatpickr
  }
}
