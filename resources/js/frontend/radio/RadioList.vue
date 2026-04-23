<!-- resources/js/frontend/radio/RadioList.vue -->
<template>
    <div class="content" :class="{ 'fade-in': contentLoaded }">
        <div v-if="loading && !radios.length" class="text-center py-4">
            <div class="spinner-border" role="status">
                <span class="sr-only"></span>
            </div>
        </div>
        
        <div v-else>
            <!-- 廣播列表 -->
            <div v-if="radios.length > 0" class="radio-list">
                <div v-for="radio in radios" :key="radio.id" class="item">
                    <a :href="getRadioUrl(radio.id)">
                        <div class="img">
                            <img :src="radio.image || defaultImage" :alt="radio.title" 
                                 loading="lazy" @load="onImageLoad">
                        </div>
                        <div class="info">
                            <div class="program">
                                <h3>{{ radio.title }}</h3>
                                <p>{{ radio.media_name || '' }}</p>
                            </div>
                        </div>
                    </a>
                </div>
            </div>
            
            <!-- 無資料提示 -->
            <div v-else class="no-data">
                <p>{{ texts.no_data }}</p>
            </div>
        </div>
    </div>
    
    <!-- 分頁組件 -->
    <Pagination
        v-if="totalPages > 1"
        :current-page="currentPage"
        :total-pages="totalPages"
        @page-change="handlePageChange"
    />
</template>

<script setup>
import { ref, onMounted, watch, nextTick, inject } from 'vue';
import Pagination from '../common/Pagination.vue';

// 注入全域服務
const $loading = inject('$loading');

// Props
const props = defineProps({
    initialCategory: {
        type: String,
        default: ''
    },
    perPage: {
        type: Number,
        default: 20
    },
    categories: {
        type: Array,
        default: () => []
    },
    texts: {
        type: Object,
        default: () => ({
            no_data: '目前沒有廣播資料'
        })
    }
});

// 響應式資料
const radios = ref([]);
const loading = ref(false);
const currentPage = ref(1);
const totalPages = ref(1);
const currentCategory = ref(props.initialCategory);
const defaultImage = '/frontend/images/radio_img_01.png';
const contentLoaded = ref(false);

// 方法
const initializeFromUrl = () => {
    const urlParams = new URLSearchParams(window.location.search);
    let category = urlParams.get('category') || '';
    const page = parseInt(urlParams.get('page')) || 1;
    
    // 保持空字串表示顯示全部廣播，不自動選擇第一個分類
    currentCategory.value = category;
    currentPage.value = page;
    contentLoaded.value = false; // 確保初始載入時也從淡出開始
    fetchRadios(category, page);
};

const switchCategory = (categoryId) => {
    // 開始淡出效果
    contentLoaded.value = false;
    
    // 更新麵包屑文字
    const categoryLink = document.querySelector(`[data-category="${categoryId}"]`);
    if (categoryLink) {
        const categoryName = categoryLink.textContent.trim();
        const breadcrumbElement = document.getElementById('breadcrumb-category');
        if (breadcrumbElement) {
            breadcrumbElement.textContent = categoryName;
        }
    }
    
    setTimeout(() => {
        currentCategory.value = categoryId;
        currentPage.value = 1;
        fetchRadios(categoryId, 1);
        updateUrl(categoryId, 1);
    }, 150); // 150ms 淡出時間
};

const fetchRadios = async (category = '', page = 1) => {
    loading.value = true;
    contentLoaded.value = false;
    
    // 顯示全域 Loading
    $loading.showLoading('載入中...');
    
    try {
        const params = {
            page: page,
            per_page: props.perPage
        };
        
        if (category) {
            params.category_id = category;
        }
        
        const response = await fetch(`/api/v1/radio?${new URLSearchParams(params)}`);
        const data = await response.json();
        
        if (data.success) {
            const paginatedData = data.data;
            radios.value = paginatedData.data || [];
            currentPage.value = paginatedData.current_page || 1;
            totalPages.value = paginatedData.last_page || 1;
            
            // 資料載入完成後，延遲淡入
            nextTick(() => {
                setTimeout(() => {
                    contentLoaded.value = true;
                }, 50);
            });
        } else {
            console.error('取得廣播列表失敗:', data.message);
            radios.value = [];
        }
    } catch (error) {
        console.error('API 請求錯誤:', error);
        radios.value = [];
    } finally {
        loading.value = false;
        // 隱藏全域 Loading
        $loading.hideLoading();
    }
};

const updateUrl = (categoryId, page) => {
    const url = new URL(window.location);
    if (categoryId) {
        url.searchParams.set('category', categoryId);
    } else {
        url.searchParams.delete('category');
    }
    if (page > 1) {
        url.searchParams.set('page', page);
    } else {
        url.searchParams.delete('page');
    }
    history.pushState(null, '', url);
};

const handlePageChange = (page) => {
    if (page === currentPage.value) return;
    
    // 開始淡出效果
    contentLoaded.value = false;
    
    setTimeout(() => {
        currentPage.value = page;
        fetchRadios(currentCategory.value, page);
        updateUrl(currentCategory.value, page);
        
        // 滾動到頂部
        window.scrollTo({
            top: document.querySelector('.section-radio-list').offsetTop - 100,
            behavior: 'smooth'
        });
    }, 150); // 150ms 淡出時間
};

const bindCategoryEvents = () => {
    const categoryLinks = document.querySelectorAll('[data-category]');
    categoryLinks.forEach(link => {
        link.addEventListener('click', (e) => {
            e.preventDefault();
            
            // 移除所有 active class
            categoryLinks.forEach(l => l.classList.remove('active'));
            // 加入 active class 到當前點擊的
            e.target.classList.add('active');
            
            const categoryId = e.target.dataset.category;
            switchCategory(categoryId);
        });
    });
};

const onImageLoad = (event) => {
    const imgElement = event.target;
    const imgContainer = imgElement.closest('.img');
    if (imgContainer) {
        imgContainer.classList.add('images-loaded');
    }
};

const getRadioUrl = (id) => {
    return `/radio/${id}`;
};

// 生命週期
onMounted(async () => {
    await nextTick();
    bindCategoryEvents();
    initializeFromUrl();
});

// 監聽屬性變化
watch(() => props.initialCategory, (newCategory) => {
    currentCategory.value = newCategory;
    fetchRadios(newCategory, 1);
});

// 暴露給父組件的方法（如果需要）
defineExpose({
    refresh: () => fetchRadios(currentCategory.value, currentPage.value)
});
</script>

<style scoped>
.content {
    opacity: 0;
    transition: opacity 0.3s ease-in-out;
    min-height: 400px; /* 預設高度避免版面跳動 */
}

.content.fade-in {
    opacity: 1;
}

/* 載入狀態樣式與新聞列表一致 */
.text-center {
    text-align: center;
}

.py-4 {
    padding: 1.5rem 0;
}

.no-data {
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 400px;
    text-align: center;
    color: #fff;
}

.no-data p {
    font-size: 18px;
    margin: 0;
}

/* 圖片載入完成效果 */
.img.images-loaded {
    opacity: 1;
}
</style>