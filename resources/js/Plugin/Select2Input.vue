<template>
    <div :id="wrapperId" class="select2-wrapper">
      <select
        ref="selectElement"
        class="js-select2 form-select"
        style="width: 100%;"
        :multiple="multiple"
        :data-placeholder="placeholder"
        :data-container="`#${wrapperId}`"
        v-model="internalValue"
      >
        <option v-if="!multiple && placeholder" value=""></option>
        <option
          v-for="opt in options"
          :key="opt.value"
          :value="opt.value"
        >{{ opt.text }}</option>
      </select>
    </div>
  </template>

  <script setup>
  import { ref, watch, onMounted, nextTick } from 'vue'
  import { loadJs, loadCss } from '@/utils/scriptLoader.js'

  const props = defineProps({
    modelValue: { type: [String, Number, Array], default: '' },
    options: { type: Array, default: () => [] },
    multiple: { type: Boolean, default: false },
    placeholder: { type: String, default: '請選擇…' },
    // AJAX 支援
    ajax: { type: Object, default: null },
    minimumInputLength: { type: Number, default: 0 },
    allowClear: { type: Boolean, default: true },
  })

  const emit = defineEmits(['update:modelValue'])
  const internalValue = ref(props.modelValue)
  const selectElement = ref(null)
  const wrapperId = 'select2-' + Math.random().toString(36).substr(2, 9)
  const select2Instance = ref(null)

  // 監聽變化（避免循環引用）
  watch(() => props.modelValue, (newVal) => {
    if (newVal !== internalValue.value) {
      internalValue.value = newVal;
    }
  })

  watch(internalValue, (newVal) => {
    if (newVal !== props.modelValue) {
      emit('update:modelValue', newVal);
    }
  })

onMounted(async () => {
  try {
    // 載入必要的資源
    await loadCss('/js/plugins/bootstrap-datepicker/css/bootstrap-datepicker3.min.css')
    await loadCss('/js/plugins/select2/css/select2.min.css')
    await loadJs('/js/lib/jquery.min.js')
    await loadJs('/js/plugins/select2/js/select2.full.min.js')
    await loadJs('/js/plugins/select2/js/i18n/zh-TW.js')
    await loadCss('/js/plugins/select2/css/select2-bootstrap-5-theme.min.css')
    
    // 等待 DOM 更新
    await nextTick()

    // 初始化 Select2
    const $ = window.jQuery
    if ($ && $.fn.select2 && selectElement.value) {
      const options = {
        theme: 'bootstrap-5',
        placeholder: props.placeholder,
        allowClear: props.allowClear,
        language: 'zh-TW',
        dropdownParent: $(`#${wrapperId}`),
        minimumInputLength: props.minimumInputLength
      }

      // 如果有 AJAX 配置，加入 AJAX 選項
      if (props.ajax) {
        options.ajax = props.ajax
      }

      select2Instance.value = $(selectElement.value).select2(options)
        .on('change', (e) => {
          const value = $(e.target).val()
          const newValue = props.multiple && value ? (Array.isArray(value) ? value : [value]) : value

          // 避免循環引用，只在值真的改變時才發送事件
          if (JSON.stringify(newValue) !== JSON.stringify(props.modelValue)) {
            emit('update:modelValue', newValue)
          }
        });
    }
  } catch (error) {
    console.error('Select2 初始化失敗:', error)
  }
});


const clearSelection = () => {
  if (select2Instance.value) {
    const $ = window.jQuery
    $(selectElement.value).val(null).trigger('change')
  }
}

// 設定值
const setValue = (value) => {
  if (select2Instance.value) {
    const $ = window.jQuery
    $(selectElement.value).val(value).trigger('change')
  }
}

// 獲取值
const getValue = () => {
  if (select2Instance.value) {
    const $ = window.jQuery
    return $(selectElement.value).val()
  }
  return null
}

// 啟用/停用
const enable = () => {
  if (select2Instance.value) {
    const $ = window.jQuery
    $(selectElement.value).prop('disabled', false)
  }
}

const disable = () => {
  if (select2Instance.value) {
    const $ = window.jQuery
    $(selectElement.value).prop('disabled', true)
  }
};
defineExpose({
  clearSelection,
  setValue,
  getValue,
  enable,
  disable,
  select2Instance
});

</script>

<style>



</style>
