<template>
  <div class="content" :class="{ 'fade-in': contentLoaded }">
    <!-- Loading 佔位區塊（避免高度跳動） -->
    <div v-if="isLoading" class="loading-placeholder">
      <!-- 全域 Loading 已經顯示，這裡只需要佔位 -->
    </div>

    <!-- 有資料時顯示列表 -->
    <div v-else-if="articles.length > 0">
      <div class="news-list">
        <div v-for="article in articles" :key="article.id" class="item">
          <a :href="`/articles/${article.id}`">
            <div class="img">
              <img
                :src="article.image || asset('frontend/images/default.webp')"
                :alt="article.title"
                loading="lazy"
                decoding="async"
                @load="onImageLoad"
                @error="handleImageError"
              >
            </div>
            <div class="info">
              <div class="datetime">{{ article.publish_date }}</div>
              <div class="desc">
                <h3>{{ article.title }}</h3>
              </div>
            </div>
          </a>
        </div>
      </div>

      <!-- 分頁組件 -->
      <Pagination
          v-if="pagination.last_page > 1"
          :current-page="pagination.current_page"
          :total-pages="pagination.last_page"
          @page-change="changePage"
      />
    </div>

    <!-- 無資料提示 -->
    <div v-else-if="!isLoading" class="no-data-message">
      <p>{{ texts.noData }}</p>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, nextTick, inject } from 'vue';
import Pagination from '../common/Pagination.vue';

const asset = inject('asset');
const $http = inject('$http');
const $loading = inject('$loading');

// Props
const props = defineProps({
  initialCategory: {
    type: String,
    default: ''
  },
  categories: {
    type: Array,
    default: () => []
  },
  initialData: {
    type: [Object, String],
    default: null
  },
  texts: {
    type: Object,
    default: () => ({
      noData: '此分類暫無新聞',
      loading: '載入中...'
    })
  }
});

// 響應式資料
const articles = ref([]);
const pagination = ref({});
const contentLoaded = ref(false);
const isLoading = ref(false);

// 方法
const initializeFromProps = () => {
  // 使用 Blade 傳入的初始資料
  if (props.initialData && typeof props.initialData === 'object') {
    articles.value = props.initialData.data || [];
    pagination.value = {
      current_page: props.initialData.current_page,
      last_page: props.initialData.last_page,
      total: props.initialData.total,
      per_page: props.initialData.per_page
    };

    // 延遲淡入
    nextTick(() => {
      setTimeout(() => {
        contentLoaded.value = true;
      }, 50);
    });
  }
};

// 動態載入新聞列表
const loadArticles = async (categoryId = null, page = 1) => {
  try {
    isLoading.value = true;
    contentLoaded.value = false;

    // 顯示全域 Loading
    $loading.showLoading(props.texts.loading || '載入中...');

    const params = { page };
    if (categoryId && categoryId !== '0') {
      params.category_id = categoryId;
    }

    const response = await $http.get('/api/v1/articles', { params });

    if (response.data.success) {
      const data = response.data.data;
      articles.value = data.data || [];
      pagination.value = {
        current_page: data.current_page,
        last_page: data.last_page,
        total: data.total,
        per_page: data.per_page
      };

      // 隱藏全域 Loading
      $loading.hideLoading();

      // 延遲淡入
      nextTick(() => {
        setTimeout(() => {
          contentLoaded.value = true;
          isLoading.value = false;
        }, 50);
      });
    }
  } catch (error) {
    console.error('[ArticleListPage] 載入失敗', error);

    // 隱藏全域 Loading
    $loading.hideLoading();

    isLoading.value = false;
    contentLoaded.value = true;
  }
};

const changePage = (page) => {
  // 發送事件給外層 Blade，讓它處理 URL 更新和 AJAX
  if (window.EventBus) {
    window.EventBus.emit('article-page-changed', { page });
  }
};

const onImageLoad = (event) => {
  const imgElement = event.target;
  const imgContainer = imgElement.closest('.img');
  if (imgContainer) {
    imgContainer.classList.add('images-loaded');
  }
};

// 處理圖片載入錯誤
const handleImageError = (event) => {
  event.target.src = asset('frontend/images/default.webp');
  // 避免預設圖片也載入失敗導致無限循環
  event.target.onerror = null;
};

// 監聽 Blade 的篩選事件
onMounted(() => {
  nextTick(() => {
    initializeFromProps();
  });

  // 監聽搜尋事件
  if (window.EventBus) {
    window.EventBus.on('article-filter-changed', (data) => {
      console.log('[ArticleListPage] 接收到篩選事件', data);
      loadArticles(data.categoryId, data.page || 1);
    });
  }
});
</script>

<style scoped>
 .content {
  opacity: 0;
  transition: opacity 0.3s ease-in-out;
}

.content.fade-in {
  opacity: 1;
}

/* Loading 佔位區塊 - 保持最小高度避免跳動 */
.loading-placeholder {
  min-height: 600px;
  display: flex;
  justify-content: center;
  align-items: center;
}

/* 無資料提示樣式 */
.no-data-message {
  display: flex;
  justify-content: center;
  align-items: center;
  height: 400px;
  color: #ffffff;
  font-size: 18px;
}

.no-data-message p {
  margin: 0;
}

.news-list .item a {
  display: flex;
  flex-direction: column;
  height: 100%;
}

.news-list .item .info {
  display: flex;
  flex-direction: column;
  flex: 1 1 auto;
}

.news-list .item .desc h3 {
  line-height: 1.4;
  display: -webkit-box;
  -webkit-line-clamp: 2; /* 顯示 2 行，多餘截斷 */
  -webkit-box-orient: vertical;
  overflow: hidden;
}

</style>