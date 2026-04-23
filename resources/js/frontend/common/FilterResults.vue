<template>
    <div class="filter-results-component">
        <!-- 廣播篩選結果：使用 radio-list class（設計稿原生樣式） -->
        <div v-if="results.length > 0 && contentType === 'radio'" class="radio-list" :class="{ 'fade-in': contentLoaded }">
            <div v-for="item in results" :key="item.id" class="item">
                <a :href="getItemUrl(item)">
                    <div class="img">
                        <img
                            :src="item.image"
                            :alt="item.title"
                            loading="lazy"
                            decoding="async"
                            @load="onImageLoad">
                    </div>
                    <div class="info">
                        <div class="program">
                            <h3>{{ item.title }}</h3>
                            <p v-if="item.media_name">{{ item.media_name }}</p>
                        </div>
                    </div>
                </a>
            </div>
        </div>

        <!-- 影音/節目篩選結果：使用 filter-list-result class -->
        <div v-else-if="results.length > 0" class="filter-list-result" :class="{ 'fade-in': contentLoaded }">
            <div v-for="item in results" :key="item.id" class="item">
                <a :href="getItemUrl(item)">
                    <div class="img">
                        <img
                            :src="item.poster_desktop"
                            class="web"
                            :alt="item.title"
                            loading="lazy"
                            decoding="async"
                            @load="onImageLoad">
                        <img
                            :src="item.poster_mobile"
                            class="mobile"
                            :alt="item.title"
                            loading="lazy"
                            decoding="async"
                            @load="onImageLoad">
                    </div>
                    <div class="info">
                        <div class="program">
                            <h3>{{ item.title }}</h3>
                        </div>
                    </div>
                </a>
            </div>
        </div>

        <!-- 無結果（只在 AJAX 回應後確認無資料時顯示）-->
        <div v-else-if="!isLoading && hasReceivedData && results.length === 0" class="no-data">
            <p>{{ texts.no_data }}</p>
        </div>

        <!-- 分頁 -->
        <pagination
            v-if="totalPages > 1"
            :total-pages="totalPages"
            :current-page="currentPage"
            @page-change="handlePageChange"
        />
    </div>
</template>

<script setup>
import { ref, onMounted, computed, inject } from 'vue';
import Pagination from './Pagination.vue';

// Props
const props = defineProps({
    contentType: {
        type: String,
        required: true,
        validator: (value) => ['drama', 'program', 'radio'].includes(value)
    },
    texts: {
        type: Object,
        default: () => ({
            loading: '載入中...',
            no_data: '目前沒有資料',
            error: '載入失敗，請稍後再試'
        })
    }
});

// Inject 全域 Loading
const $loading = inject('$loading');

// State
const results = ref([]);
const total = ref(0);
const currentPage = ref(1);
const perPage = ref(20);  // 每頁顯示筆數
const isLoading = ref(false);  // 是否正在載入中
const hasReceivedData = ref(false);  // 是否已接收到 AJAX 回應
const contentLoaded = ref(false);  // 內容是否已載入（用於淡入效果）

// Computed
const totalPages = computed(() => {
    return Math.ceil(total.value / perPage.value);
});

const getItemUrl = computed(() => {
    return (item) => {
        // radio 使用不同的路由結構
        if (props.contentType === 'radio') {
            if (window.route) {
                return window.route('radio.show', { id: item.id });
            }
            return `/radio/${item.id}`;
        }

        // drama 和 program 使用 videos 路由
        const routeName = props.contentType === 'drama' ? 'drama.videos.index' : 'program.videos.index';
        const paramName = props.contentType === 'drama' ? 'dramaId' : 'programId';

        // 使用 Ziggy route helper (如果可用)
        if (window.route) {
            return window.route(routeName, { [paramName]: item.id });
        }

        // Fallback 到手動組合 URL
        return `/${props.contentType}/videos/${item.id}`;
    };
});

// Methods
const handlePageChange = (page) => {
    // 先淡出
    contentLoaded.value = false;

    setTimeout(() => {
        currentPage.value = page;

        // 通知 jQuery 需要重新載入該頁資料
        window.EventBus.emit('filter-page-changed', {
            page: page
        });

        // 滾動到頂部
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }, 150); // 150ms 淡出時間
};

const onImageLoad = (event) => {
    const imgElement = event.target;
    const imgContainer = imgElement.closest('.img');
    if (imgContainer) {
        imgContainer.classList.add('images-loaded');
    }
};

// Lifecycle
onMounted(() => {
    // 監聽 jQuery 傳來的資料更新
    window.EventBus.on('filter-results-updated', (data) => {
        results.value = data.results || [];
        total.value = data.total || 0;
        currentPage.value = data.current_page || 1;
        perPage.value = data.per_page || 20;

        // 標記已接收到資料並結束載入
        hasReceivedData.value = true;
        isLoading.value = false;

        // 隱藏全域 Loading
        $loading.hideLoading();

        // 資料載入完成後，延遲淡入
        setTimeout(() => {
            contentLoaded.value = true;
        }, 50);
    });

    // 監聽 Loading 狀態
    window.EventBus.on('filter-show-loading', () => {
        // 標記開始載入（不顯示「暫無資料」）並重置淡入狀態
        isLoading.value = true;
        contentLoaded.value = false;

        // 顯示全域 Loading（三個點動畫）
        $loading.showLoading(props.texts.loading || '載入中...');
    });

    // 監聽錯誤
    window.EventBus.on('filter-error', () => {
        results.value = [];
        hasReceivedData.value = true;  // 標記已收到回應（即使是錯誤）
        isLoading.value = false;

        // 隱藏全域 Loading
        $loading.hideLoading();
    });

    // 監聽重置（清除篩選時）
    window.EventBus.on('filter-reset', () => {
        hasReceivedData.value = false;  // 重置資料接收狀態
        isLoading.value = false;
        results.value = [];
        total.value = 0;
    });
});
</script>

<style scoped>
/* 使用前台既有的 main.css 樣式 */
.filter-results-component {
    min-height: 400px;
}

/* 淡入淡出動畫 - 影音/節目 */
.filter-list-result {
    opacity: 0;
    transition: opacity 0.3s ease-in-out;
}

.filter-list-result.fade-in {
    opacity: 1;
}

/* 淡入淡出動畫 - 廣播 */
.radio-list {
    opacity: 0;
    transition: opacity 0.3s ease-in-out;
}

.radio-list.fade-in {
    opacity: 1;
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
</style>
