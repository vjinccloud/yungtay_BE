<!-- resources/js/frontend/news/NewsList.vue-->
<template>
  <div class="news-list-component">
    <div class="content" :class="{ 'fade-in': contentLoaded }">
    <!-- 搜尋區塊 -->
    <div class="block-title">
      <div class="sub-title">
        <h2>{{ texts.title }}</h2>
      </div>
      <div class="more">    
        <div class="search">
          <button type="submit" @click="handleSearch"></button>
          <input 
            type="text" 
            v-model="searchKeyword"
            @keyup.enter="handleSearch"
            :placeholder="texts.searchPlaceholder">
        </div>
      </div>    
    </div>
    
    <!-- 新聞列表 -->
    <div class="news-list-div">
      <template v-if="newsList.length > 0">
        <div v-for="item in newsList" :key="item.id" class="item">
          <a :href="`/news/${item.id}`">
            <div class="img">
              <div class="image-loading-spinner"></div>
              <img
                :src="getImageUrl(item.image)"
                :alt="item.title"
                loading="lazy"
                decoding="async"
                @load="onImageLoad"
                onerror="this.onerror=null; this.src='/frontend/images/default.webp'; this.parentElement.classList.add('images-loaded');">
            </div>
            <div class="info">
              <div class="datetime">{{ item.publish_date }}</div>
              <div class="desc"><p>{{ item.title }}</p></div>
            </div>     
          </a>            
        </div>
      </template>
      
      <!-- 無資料提示 -->
      <div v-if="!loading && newsList.length === 0" class="no-data">
        <p>{{ texts.noData }}</p>
      </div>
    </div>
    
    <!-- 分頁 -->
    <pagination 
      :current-page="currentPage"
      :total-pages="totalPages"
      @page-change="changePage"
    />
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, nextTick, inject } from 'vue';
import Pagination from '../common/Pagination.vue';

const $http = inject('$http');
const $loading = inject('$loading');

// Props
const props = defineProps({
  apiEndpoint: {
    type: String,
    default: '/api/v1/news'
  },
  perPage: {
    type: Number,
    default: 6
  },
  texts: {
    type: Object,
    default: () => ({
      title: '最新消息',
      searchPlaceholder: '搜尋關鍵字', 
      noData: '目前沒有最新消息',
      loading: '載入中...'
    })
  }
});

// 響應式資料
const newsList = ref([]);
const currentPage = ref(1);
const totalPages = ref(1);
const searchKeyword = ref('');
const loading = ref(false);
const contentLoaded = ref(false);

// 方法
const getImageUrl = (image) => {
  // 如果沒有圖片，返回預設圖片
  if (!image) {
    return '/frontend/images/default.webp';
  }
  
  // 如果是完整的 URL（包含 http:// 或 https://），直接返回
  if (image.startsWith('http://') || image.startsWith('https://')) {
    return image;
  }
  
  // 如果是以 / 開頭的絕對路徑，直接返回
  if (image.startsWith('/')) {
    return image;
  }
  
  // 其他情況，假設是 storage 路徑，添加 /storage/ 前綴
  return '/storage/' + image;
};

const fetchNews = async () => {
  loading.value = true;
  contentLoaded.value = false; // 開始淡出
  $loading.showLoading(props.texts.loading);
  
  try {
    const response = await $http.get(props.apiEndpoint, {
      params: {
        page: currentPage.value,
        per_page: props.perPage,
        search: searchKeyword.value
      }
    });
    
    newsList.value = response.data.data.data || []; 
    totalPages.value = response.data.data.last_page || 1;
    currentPage.value = response.data.data.current_page || 1;
    
    // 資料載入完成後，延遲淡入
    nextTick(() => {
      setTimeout(() => {
        contentLoaded.value = true;
      }, 50);
    });
    
  } catch (error) {
    console.error('Failed to fetch news:', error);
  } finally {
    loading.value = false;
    $loading.hideLoading();
  }
};

const changePage = (page) => {
  if (page === currentPage.value) return;
  
  // 開始淡出效果
  contentLoaded.value = false;
  
  setTimeout(() => {
    currentPage.value = page;
    fetchNews();
  }, 150); // 150ms 淡出時間
};

const handleSearch = () => {
  // 開始淡出效果
  contentLoaded.value = false;
  
  setTimeout(() => {
    currentPage.value = 1;
    fetchNews();
  }, 150); // 150ms 淡出時間
};

const onImageLoad = (event) => {
  // 圖片載入完成，加上 loaded 樣式
  const imgElement = event.target;
  const imgContainer = imgElement.closest('.img');
  if (imgContainer) {
    imgContainer.classList.add('images-loaded');
  }
};

// 生命週期
onMounted(() => {
  contentLoaded.value = false; // 確保初始載入時也從淡出開始
  fetchNews();
});
</script>

<style scoped>
/* 淡入淡出效果 */
.content {
  opacity: 0;
  transition: opacity 0.3s ease-in-out;
}

.content.fade-in {
  opacity: 1;
}

/* 新聞列表容器預設高度 */
.news-list-div {
  position: relative;
  min-height: 300px;
}

/* 無資料狀態樣式 */
.no-data {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  display: flex;
  justify-content: center;
  align-items: center;
  text-align: center;
  color: #fff;
}

.no-data p {
  font-size: 18px;
  margin: 0;
}

/* 圖片載入樣式 */
.news-list-div .item .img {
  position: relative;
  overflow: hidden;
}

/* 載入中的 spinner */
.image-loading-spinner {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  width: 30px;
  height: 30px;
  border: 3px solid rgba(255, 255, 255, 0.3);
  border-top: 3px solid #fff;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  z-index: 1;
}

/* 圖片初始狀態 - 隱藏 */
.news-list-div .item .img img {
  opacity: 0;
  transition: opacity 0.3s ease-in-out, transform 0.3s ease-in-out;
  width: 100%;
  height: auto;
  display: block;
}

/* 圖片載入完成 - 顯示並隱藏 spinner */
.news-list-div .item .img.images-loaded img {
  opacity: 1;
}

.news-list-div .item .img.images-loaded .image-loading-spinner {
  display: none;
}

/* 滑鼠移入移出動畫 */
.news-list-div .item a:hover .img img {
  transform: scale(1.1);
}

/* 載入動畫 */
@keyframes spin {
  0% { transform: translate(-50%, -50%) rotate(0deg); }
  100% { transform: translate(-50%, -50%) rotate(360deg); }
}
</style>