<template>
    <div class="editable-wrapper">
        <!-- 顯示模式 -->
        <div
            v-if="!isEditing"
            @click="startEdit"
            :class="['editable-display', {
                'empty': isEmpty,
                'error': hasError,
                'form-control': true
            }]"
        >
            <span v-if="!isEmpty">{{ displayValue }}</span>
            <span v-else class="text-muted">{{ emptyText }}</span>
        </div>

        <!-- 編輯模式 -->
        <div v-else class="edit-form d-flex flex-column align-items-center gap-2">
            <input
                ref="inputRef"
                v-model="inputValue"
                @keyup.escape="cancel"
                @blur="onBlur"
                :type="type"
                :class="['form-control', { 'is-invalid': hasError }]"
                :placeholder="placeholder"
            />
            <div class="btn-group">
                <button
                @click="save"
                class="btn btn-success btn-sm"
                type="button"
                >保存</button>
                <button
                @click="cancel"
                class="btn btn-secondary btn-sm"
                type="button"
                >取消</button>
            </div>
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
        type: [String, Number],
        default: ''
    },
    type: {
        type: String,
        default: 'text'
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
const inputRef = ref(null);

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

// 驗證函數
const validate = (value) => {
    error.value = '';

    if (props.required && (!value || value.toString().trim() === '')) {
        error.value = '此欄位為必填';
        return false;
    }

    if (props.type === 'email' && value) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(value)) {
            error.value = '請輸入有效的電子郵件格式';
            return false;
        }
    }

    if (props.type === 'url' && value) {
        try {
            new URL(value);
        } catch {
            error.value = '請輸入有效的網址格式';
            return false;
        }
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
        if (inputRef.value) {
            inputRef.value.focus();
            inputRef.value.select();
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
        save();
    }

     //isEditing.value = false;
};

// 監聽 modelValue 變化
watch(() => props.modelValue, (newValue) => {
    if (!isEditing.value) {
        inputValue.value = newValue || '';
    }
});
</script>

<style scoped>

</style>
