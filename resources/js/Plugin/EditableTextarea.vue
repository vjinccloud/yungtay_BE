<template>
    <div class="editable-wrapper">
        <!-- 顯示模式 -->
        <div
            v-if="!isEditing"
            @click="startEdit"
            :class="['editable-display', 'textarea-display', {
                'empty': isEmpty,
                'error': hasError,
                'form-control': true
            }]"
        >
            <div v-if="!isEmpty" class="content-display">
                <pre v-if="preserveNewlines">{{ displayValue }}</pre>
                <div v-else v-html="formattedContent"></div>
            </div>
            <span v-else class="text-muted">{{ emptyText }}</span>
        </div>

        <!-- 編輯模式 -->
        <div v-else class="edit-form">
            <textarea
                ref="textareaRef"
                v-model="inputValue"
                @keyup.ctrl.enter="save"
                @keyup.escape="cancel"
                @blur="onBlur"
                :class="['form-control', { 'is-invalid': hasError }]"
                :placeholder="placeholder"
                :rows="rows"
                :cols="cols"
            ></textarea>
            <div class="btn-group mt-2">
                <button
                    @click="save"
                    class="btn btn-success btn-sm"
                    type="button"
                >
                    ✓ 保存
                </button>
                <button
                    @click="cancel"
                    class="btn btn-secondary btn-sm"
                    type="button"
                >
                    ✕ 取消
                </button>
            </div>
            <small class="text-muted d-block mt-1">
                提示：按 Ctrl+Enter 快速保存，按 Esc 取消
            </small>
        </div>

        <!-- 錯誤訊息 -->
        <div v-if="hasError" class="invalid-feedback d-block">
            {{ error }}
        </div>
    </div>
</template>

<script setup>
import { ref, computed, nextTick, watch } from 'vue';

const props = defineProps({
    modelValue: {
        type: String,
        default: ''
    },
    required: {
        type: Boolean,
        default: false
    },
    emptyText: {
        type: String,
        default: '點擊編輯'
    },
    placeholder: {
        type: String,
        default: ''
    },
    rows: {
        type: Number,
        default: 4
    },
    cols: {
        type: Number,
        default: null
    },
    maxLength: {
        type: Number,
        default: null
    },
    preserveNewlines: {
        type: Boolean,
        default: true
    },
    validator: {
        type: Function,
        default: null
    },
    autoSave: {
        type: Boolean,
        default: true
    }
});

const emit = defineEmits(['update:modelValue', 'update', 'save', 'cancel']);

// 響應式數據
const isEditing = ref(false);
const inputValue = ref('');
const error = ref('');
const textareaRef = ref(null);

// 計算屬性
const displayValue = computed(() => {
    return props.modelValue || '';
});

const isEmpty = computed(() => {
    return !props.modelValue || props.modelValue.toString().trim() === '';
});

const hasError = computed(() => {
    return !!error.value;
});

const formattedContent = computed(() => {
    if (!displayValue.value) return '';
    return displayValue.value.replace(/\n/g, '<br>');
});

// 驗證函數
const validate = (value) => {
    error.value = '';

    if (props.required && (!value || value.toString().trim() === '')) {
        error.value = '此欄位為必填';
        return false;
    }

    if (props.maxLength && value && value.length > props.maxLength) {
        error.value = `內容長度不能超過 ${props.maxLength} 個字符`;
        return false;
    }

    if (props.validator) {
        const result = props.validator(value);
        if (result !== true) {
            error.value = result || '輸入格式不正確';
            return false;
        }
    }

    return true;
};

// 方法
const startEdit = () => {
    inputValue.value = props.modelValue || '';
    error.value = '';
    isEditing.value = true;

    nextTick(() => {
        if (textareaRef.value) {
            textareaRef.value.focus();
            // 將光標移到文本末尾
            const length = textareaRef.value.value.length;
            textareaRef.value.setSelectionRange(length, length);
        }
    });
};

const save = () => {
    if (!validate(inputValue.value)) {
        return;
    }

    emit('update:modelValue', inputValue.value);
    emit('update', inputValue.value);
    emit('save', inputValue.value);

    isEditing.value = false;
    error.value = '';
};

const cancel = () => {
    inputValue.value = props.modelValue || '';
    isEditing.value = false;
    error.value = '';
    emit('cancel');
};

const onBlur = () => {
    if (props.autoSave) {
        // 延遲執行，避免點擊按鈕時觸發
        setTimeout(() => {
            if (isEditing.value) {
                save();
            }
        }, 150);
    }
};

// 監聽 modelValue 變化
watch(() => props.modelValue, (newValue) => {
    if (!isEditing.value) {
        inputValue.value = newValue || '';
    }
});
</script>

<style scoped></style>
