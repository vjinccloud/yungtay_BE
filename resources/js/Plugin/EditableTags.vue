<template>
    <div class="editable-wrapper">
        <!-- 顯示模式 -->
        <div
            v-if="!isEditing"
            @click="startEdit"
            :class="['editable-display', 'tags-display', {
                'empty': isEmpty,
                'error': hasError,
                'form-control': true
            }]"
        >
            <div v-if="!isEmpty" class="tags-container">
                <span
                    v-for="(tag, index) in tagArray"
                    :key="index"
                    class="badge bg-primary me-1 mb-1"
                >
                    {{ tag }}
                </span>
            </div>
            <span v-else class="text-muted">{{ emptyText }}</span>
        </div>

        <!-- 編輯模式 -->
        <div v-else class="edit-form">
            <div class="tags-input-container">
                <!-- 已有標籤 -->
                <div class="current-tags mb-2" v-if="currentTags.length > 0">
                    <span
                        v-for="(tag, index) in currentTags"
                        :key="index"
                        class="badge bg-secondary me-1 mb-1 tag-item"
                    >
                        {{ tag }}
                        <button
                            @click="removeTag(index)"
                            type="button"
                            class="btn-close btn-close-white ms-1"
                            style="font-size: 0.6em;"
                        ></button>
                    </span>
                </div>

                <!-- 輸入框 -->
                <input
                    ref="inputRef"
                    v-model="inputValue"
                    @keyup.enter.prevent="addTag"
                    @keyup.escape="cancel"
                    @keydown.backspace="handleBackspace"
                    @keydown.enter.prevent
                    :class="['form-control', { 'is-invalid': hasError }]"
                    :placeholder="placeholder"
                    type="text"
                />

                <!-- 建議標籤 -->
                <div v-if="availableSuggestions.length > 0" class="suggestions mt-2">
                    <small class="text-muted d-block mb-1">建議標籤：</small>
                    <button
                        v-for="suggestion in availableSuggestions"
                        :key="suggestion"
                        @click="addSuggestion(suggestion)"
                        type="button"
                        class="btn btn-outline-secondary btn-sm me-1 mb-1"
                    >
                        {{ suggestion }}
                    </button>
                </div>
            </div>

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
                提示：輸入後按 Enter 添加標籤，按 Backspace 刪除最後一個標籤
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
        type: [String, Array],
        default: ''
    },
    separator: {
        type: String,
        default: ','
    },
    required: {
        type: Boolean,
        default: false
    },
    emptyText: {
        type: String,
        default: '點擊添加標籤'
    },
    placeholder: {
        type: String,
        default: '輸入標籤後按 Enter'
    },
    maxTags: {
        type: Number,
        default: null
    },
    allowDuplicates: {
        type: Boolean,
        default: false
    },
    trimValue: {
        type: Boolean,
        default: true
    },
    suggestions: {
        type: Array,
        default: () => []
    },
    validator: {
        type: Function,
        default: null
    }
});

const emit = defineEmits(['update:modelValue', 'update', 'save', 'cancel']);

// 響應式數據
const isEditing = ref(false);
const inputValue = ref('');
const currentTags = ref([]);
const error = ref('');
const inputRef = ref(null);

// 計算屬性
const tagArray = computed(() => {
    if (Array.isArray(props.modelValue)) {
        return props.modelValue;
    }
    if (typeof props.modelValue === 'string' && props.modelValue) {
        return props.modelValue.split(props.separator).map(tag => tag.trim()).filter(tag => tag);
    }
    return [];
});

const isEmpty = computed(() => {
    return tagArray.value.length === 0;
});

const hasError = computed(() => {
    return !!error.value;
});

const availableSuggestions = computed(() => {
    return props.suggestions.filter(suggestion =>
        !currentTags.value.includes(suggestion) &&
        suggestion.toLowerCase().includes(inputValue.value.toLowerCase())
    );
});

// 驗證函數
const validate = (tags) => {
    error.value = '';

    if (props.required && tags.length === 0) {
        error.value = '至少需要添加一個標籤';
        return false;
    }

    if (props.maxTags && tags.length > props.maxTags) {
        error.value = `標籤數量不能超過 ${props.maxTags} 個`;
        return false;
    }

    if (props.validator) {
        for (const tag of tags) {
            const result = props.validator(tag);
            if (result !== true) {
                error.value = result || '標籤格式不正確';
                return false;
            }
        }
    }

    return true;
};

// 方法
const startEdit = () => {
    currentTags.value = [...tagArray.value];
    inputValue.value = '';
    error.value = '';
    isEditing.value = true;

    nextTick(() => {
        if (inputRef.value) {
            inputRef.value.focus();
        }
    });
};

const addTag = () => {
    let newTag = inputValue.value;

    if (props.trimValue) {
        newTag = newTag.trim();
    }

    if (!newTag) return;

    // 檢查重複
    if (!props.allowDuplicates && currentTags.value.includes(newTag)) {
        error.value = '標籤已存在';
        return;
    }

    // 檢查最大數量
    if (props.maxTags && currentTags.value.length >= props.maxTags) {
        error.value = `最多只能添加 ${props.maxTags} 個標籤`;
        return;
    }

    currentTags.value.push(newTag);
    inputValue.value = '';
    error.value = '';
};

const addSuggestion = (suggestion) => {
    if (!props.allowDuplicates && currentTags.value.includes(suggestion)) {
        return;
    }

    if (props.maxTags && currentTags.value.length >= props.maxTags) {
        error.value = `最多只能添加 ${props.maxTags} 個標籤`;
        return;
    }

    currentTags.value.push(suggestion);
    inputValue.value = '';
    error.value = '';
};

const removeTag = (index) => {
    currentTags.value.splice(index, 1);
    error.value = '';
};

const handleBackspace = () => {
    if (!inputValue.value && currentTags.value.length > 0) {
        currentTags.value.pop();
    }
};

const save = () => {
    if (!validate(currentTags.value)) {
        return;
    }

    const result = Array.isArray(props.modelValue)
        ? currentTags.value
        : currentTags.value.join(props.separator);

    emit('update:modelValue', result);
    emit('update', result);
    emit('save', result);

    isEditing.value = false;
    error.value = '';
};

const cancel = () => {
    currentTags.value = [...tagArray.value];
    inputValue.value = '';
    isEditing.value = false;
    error.value = '';
    emit('cancel');
};

// 監聽 modelValue 變化
watch(() => props.modelValue, (newValue) => {
    if (!isEditing.value) {
        // 更新本地標籤數組
    }
});
</script>

