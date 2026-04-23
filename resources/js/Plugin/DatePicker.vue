<!-- DatePicker.vue -->
<template>
    <div class="date-picker-container">
        <label v-if="label" class="form-label">{{ label }}</label>
        <div class="input-group">
            <input
                ref="dateInput"
                type="text"
                class="form-control"
                :placeholder="placeholder"
                :value="modelValue"
                @input="$emit('update:modelValue', $event.target.value)"
                readonly
                :class="[
                    { 'is-invalid': hasError },
                    errorClass
                ]"
            >
            <span class="input-group-text" @click="openPicker">
                <i :class="iconClass"></i>
            </span>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, watch } from 'vue';
import { loadJs, loadCss } from '@/utils/scriptLoader.js';

// Props
const props = defineProps({
    modelValue: {
        type: String,
        default: ''
    },
    label: {
        type: String,
        default: ''
    },
    placeholder: {
        type: String,
        default: '選擇日期'
    },
    dateFormat: {
        type: String,
        default: 'Y-m-d'
    },
    enableTime: {
        type: Boolean,
        default: false
    },
    noCalendar: {
        type: Boolean,
        default: false
    },
    minDate: {
        type: String,
        default: null
    },
    maxDate: {
        type: String,
        default: null
    },
    iconClass: {
        type: String,
        default: 'fa fa-clock'
    },
    locale: {
        type: String,
        default: 'zh_tw'
    },
    options: {
        type: Object,
        default: () => ({})
    },
    hasError: {
        type: Boolean,
        default: false
    },
    errorClass: {
        type: String,
        default: ''
    }
});

// Emits
const emit = defineEmits(['update:modelValue']);

// Refs
const dateInput = ref(null);
const flatpickrInstance = ref(null);
const flatpickrLoaded = ref(false);

// 載入 Flatpickr 資源
const loadFlatpickr = async () => {
    if (flatpickrLoaded.value) return;

    try {
        await Promise.all([
            loadCss('/js/plugins/flatpickr/flatpickr.min.css'),
            loadJs('/js/plugins/flatpickr/flatpickr.min.js'),
        ]);

        // 嘗試載入語言包
        if (props.locale === 'zh_tw') {
            try {
                await loadJs('/js/plugins/flatpickr/l10n/zh-tw.js');
            } catch (e) {
                console.log('zh-tw.js 載入失敗，使用預設語言');
            }
        }

        flatpickrLoaded.value = true;
    } catch (error) {
        console.error('載入 Flatpickr 失敗:', error);
    }
};

// 初始化 Flatpickr
const initFlatpickr = async () => {
    await loadFlatpickr();

    if (!flatpickrLoaded.value || !window.flatpickr || !dateInput.value) {
        return;
    }

    // 設定語言包
    if (props.locale === 'zh_tw' && window.flatpickr.l10ns?.zh_tw) {
        window.flatpickr.localize(window.flatpickr.l10ns.zh_tw);
    }

    // 合併選項
    const flatpickrOptions = {
        dateFormat: props.dateFormat,
        enableTime: props.enableTime,
        noCalendar: props.noCalendar,
        defaultDate: props.modelValue,
        ...props.options,
        onChange: (selectedDates, dateStr) => {
            emit('update:modelValue', dateStr);
            // 觸發自定義選項中的 onChange
            if (props.options.onChange) {
                props.options.onChange(selectedDates, dateStr);
            }
        }
    };

    // 設定日期限制
    if (props.minDate) {
        flatpickrOptions.minDate = props.minDate;
    }
    if (props.maxDate) {
        flatpickrOptions.maxDate = props.maxDate;
    }

    // 建立 Flatpickr 實例
    flatpickrInstance.value = window.flatpickr(dateInput.value, flatpickrOptions);
};

// 開啟日期選擇器
const openPicker = () => {
    if (flatpickrInstance.value) {
        flatpickrInstance.value.open();
    }
};

// 監聽 modelValue 變化
watch(() => props.modelValue, (newValue) => {
    if (flatpickrInstance.value && newValue !== flatpickrInstance.value.input.value) {
        flatpickrInstance.value.setDate(newValue, false);
    }
});

// 生命週期
onMounted(() => {
    initFlatpickr();
});

onUnmounted(() => {
    if (flatpickrInstance.value) {
        flatpickrInstance.value.destroy();
    }
});

// 暴露方法給父組件
defineExpose({
    flatpickrInstance,
    openPicker,
    clear: () => {
        if (flatpickrInstance.value) {
            flatpickrInstance.value.clear();
        }
    },
    setDate: (date) => {
        if (flatpickrInstance.value) {
            flatpickrInstance.value.setDate(date);
        }
    }
});
</script>

<style scoped>
.date-picker-container {
    width: 100%;
}

.form-label {
    font-weight: 500;
    color: #495057;
    margin-bottom: 4px;
}

.input-group-text {
    cursor: pointer;
    background-color: #fff;
    border-left: 0;
}

.input-group .form-control {
    border-right: 0;
}

.input-group:focus-within .input-group-text {
    border-color: #86b7fe;
}
</style>
