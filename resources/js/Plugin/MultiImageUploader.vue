<template>
    <div class="multi-image-uploader">
        <!-- 現有圖片列表 -->
        <div class="image-gallery" v-if="images.length > 0">
            <div 
                v-for="(img, index) in images" 
                :key="index" 
                class="image-item"
            >
                <img :src="formatUrl(img)" alt="圖片" @error="handleImageError" />
                <button 
                    type="button" 
                    class="btn-remove"
                    @click="removeImage(index)"
                    :disabled="isUploading"
                >
                    <i class="fa fa-times"></i>
                </button>
            </div>
        </div>
        <div v-else class="text-muted mb-2">{{ emptyText }}</div>
        
        <!-- 上傳區域 -->
        <div v-if="images.length < limit" class="upload-area">
            <input 
                type="file" 
                :accept="accept"
                multiple
                class="form-control"
                @change="handleUpload"
                :disabled="isUploading"
                ref="fileInput"
            />
            <small class="text-muted d-block mt-1">
                <span v-if="isUploading">
                    <i class="fa fa-spinner fa-spin me-1"></i>上傳中...
                </span>
                <span v-else>
                    可多選，還可上傳 {{ limit - images.length }} 張
                </span>
            </small>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import axios from 'axios';

const props = defineProps({
    modelValue: {
        type: Array,
        default: () => []
    },
    limit: {
        type: Number,
        default: 10
    },
    accept: {
        type: String,
        default: 'image/*'
    },
    uploadUrl: {
        type: String,
        required: true
    },
    emptyText: {
        type: String,
        default: '尚無圖片'
    }
});

const emit = defineEmits(['update:modelValue']);

const fileInput = ref(null);
const isUploading = ref(false);

// 使用 computed 來同步 modelValue
const images = computed({
    get: () => props.modelValue || [],
    set: (val) => emit('update:modelValue', val)
});

// 格式化圖片 URL
const formatUrl = (url) => {
    if (!url) return '';
    if (url.startsWith('http')) return url;
    return url.startsWith('/') ? url : '/' + url;
};

// 圖片載入錯誤處理
const handleImageError = (event) => {
    event.target.src = '/frontend/images/default.webp';
};

// 移除圖片
const removeImage = (index) => {
    const newImages = [...images.value];
    newImages.splice(index, 1);
    emit('update:modelValue', newImages);
};

// 上傳圖片
const handleUpload = async (event) => {
    const files = event.target.files;
    if (!files || files.length === 0) return;
    
    isUploading.value = true;
    const newImages = [...images.value];
    
    for (const file of files) {
        if (newImages.length >= props.limit) break;
        
        const formData = new FormData();
        // 使用 files[] 陣列格式，符合後端驗證要求
        formData.append('files[]', file);
        
        try {
            const response = await axios.post(props.uploadUrl, formData, {
                headers: {
                    'Content-Type': 'multipart/form-data',
                },
            });
            
            if (response.data?.url) {
                newImages.push(response.data.url);
            }
        } catch (error) {
            console.error('Upload failed:', error);
        }
    }
    
    emit('update:modelValue', newImages);
    isUploading.value = false;
    
    // 清空 input
    if (fileInput.value) {
        fileInput.value.value = '';
    }
};
</script>

<style scoped>
.multi-image-uploader {
    width: 100%;
}

.image-gallery {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-bottom: 15px;
}

.image-item {
    position: relative;
    width: 100px;
    height: 100px;
    border: 1px solid #ddd;
    border-radius: 4px;
    overflow: hidden;
}

.image-item img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.image-item .btn-remove {
    position: absolute;
    top: 2px;
    right: 2px;
    width: 22px;
    height: 22px;
    padding: 0;
    border: none;
    border-radius: 50%;
    background: rgba(220, 53, 69, 0.9);
    color: white;
    font-size: 12px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
}

.image-item .btn-remove:hover {
    background: #dc3545;
    transform: scale(1.1);
}

.image-item .btn-remove:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.upload-area {
    margin-top: 10px;
}
</style>
