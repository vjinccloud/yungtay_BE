<template>
    <div class="media-filter-component">
        <!-- 篩選表單區塊 -->
        <div class="block-div block-01">
            <div class="block-outer">
                <div class="filter-type-year show">
                    <div class="boxer">
                        <div class="tab-links-outer">
                            <div class="links">
                                <a v-for="(category, index) in categories.main"
                                   :key="category.id"
                                   :href="`#filter${category.id}`"
                                   :class="{ active: activeTab === category.id }"
                                   @click.prevent="switchTab(category.id)">
                                    {{ category.name }}
                                </a>
                            </div>
                        </div>
                        <div class="tab-content">
                            <div v-for="(category, index) in categories.main"
                                 :key="category.id"
                                 class="content"
                                 :class="{ active: activeTab === category.id }"
                                 :id="`filter${category.id}`">
                                <div class="item">
                                    <div class="label">
                                        {{ props.texts.type || props.texts.all_subcategories || 'Type' }}
                                    </div>
                                    <div class="filter-list">
                                        <div v-for="subcategory in getSubcategories(category.id)"
                                             :key="subcategory.id"
                                             class="checkbox-box"
                                             :class="{ active: filters.subcategories.includes(subcategory.id) }">
                                            <label class="checkbox-container">
                                                {{ subcategory.name }}
                                                <input type="checkbox"
                                                       :value="subcategory.id"
                                                       v-model="filters.subcategories"
                                                       @change="updateFilters">
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <div class="item">
                                    <div class="label">
                                        {{ props.texts.year || props.texts.all_years || 'Year' }}
                                    </div>
                                    <div class="filter-list">
                                        <div v-for="year in years"
                                             :key="year"
                                             class="checkbox-box"
                                             :class="{ active: filters.years.includes(year) }">
                                            <label class="checkbox-container">
                                                {{ year }}
                                                <input type="checkbox"
                                                       :value="year"
                                                       v-model="filters.years"
                                                       @change="updateFilters">
                                            </label>
                                        </div>
                                        <div class="checkbox-box"
                                             :class="{ active: filters.years.includes('before_2015') }">
                                            <label class="checkbox-container">
                                                {{ props.texts.yearBefore2015 || 'Before 2015' }}
                                                <input type="checkbox"
                                                       value="before_2015"
                                                       v-model="filters.years"
                                                       @change="updateFilters">
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <div class="item action">
                                    <button class="btn-reset" type="button" @click="clearFilters">{{ props.texts.clearFilter || props.texts.clear_filters || 'Clear Filter' }}</button>
                                    <button class="btn-search" type="button" @click="search">{{ props.texts.search || 'Search' }}</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 篩選結果區塊 -->
        <div class="block-div block-02">
            <div class="block-outer">
                <div class="filter-results">
                    <!-- 載入中狀態 -->
                    <div v-if="loading" class="loading-spinner">
                        <div class="text-center py-5">
                        </div>
                    </div>

                    <!-- 結果列表 -->
                    <div v-else-if="results.length > 0" class="filter-list-result">
                        <div v-for="item in results" :key="item.id" class="item">
                            <a :href="getItemUrl(item)">
                                <div class="img">
                                    <img :src="item.poster_desktop || '/frontend/images/hot_drama_img_01.png'" class="web" :alt="item.title">
                                    <img :src="item.poster_mobile || '/frontend/images/mobile_01.jpg'" class="mobile" :alt="item.title">
                                </div>
                                <div class="info">
                                    <div class="program">
                                        <h3>{{ item.title }}</h3>
                                    </div>
                                </div>
                            </a>
                        </div>
                    </div>

                    <!-- 無結果 -->
                    <div v-else class="no-data">
                        <p>{{ noResultsMessage }}</p>
                    </div>

                    <!-- 分頁 -->
                    <pagination 
                        v-if="total > perPage"
                        :total="total"
                        :current-page="currentPage"
                        :per-page="perPage"
                        @page-changed="changePage"
                    />
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, onBeforeUnmount, inject } from 'vue';
import Pagination from './Pagination.vue';

// 注入服務
const $http = inject('$http');
const $loading = inject('$loading');

// Props
const props = defineProps({
    // 內容類型：'drama' 或 'program'
    contentType: {
        type: String,
        required: true,
        validator: value => ['drama', 'program'].includes(value)
    },
    // 初始分類資料
    initialCategories: {
        type: Object,
        default: () => ({ main: [], sub: [] })
    },
    // 翻譯文字
    texts: {
        type: Object,
        default: () => ({
            type: 'Type',
            year: 'Year', 
            yearBefore2015: 'Before 2015',
            clearFilter: 'Clear Filter',
            search: 'Search',
            loading: 'Loading...',
            drama: 'Video',
            program: 'Program',
            no_data: 'No content found matching the criteria',
            error: 'An error occurred, please try again later'
        })
    }
});

// 響應式資料
const categories = ref(props.initialCategories);
const activeTab = ref(null);
const filters = reactive({
    categoryId: null,
    subcategories: [],
    years: []
});
const results = ref([]);
const loading = ref(false);
const currentPage = ref(1);
const perPage = ref(18);
const total = ref(0);
const years = ref([]);

// 計算屬性
const contentLabel = computed(() => {
    return props.contentType === 'drama' ? 
        (props.texts.drama || 'Video') : 
        (props.texts.program || 'Program');
});

const apiEndpoint = computed(() => {
    return `/api/v1/${props.contentType}/filter`;
});

const noResultsMessage = computed(() => {
    return props.texts.no_data || `No ${contentLabel.value.toLowerCase()} found matching the criteria`;
});

// 方法
const initYears = () => {
    const currentYear = new Date().getFullYear();
    years.value = [];
    for (let year = currentYear; year >= 2015; year--) {
        years.value.push(year);
    }
};

// 從 URL 載入篩選條件
const loadFiltersFromURL = () => {
    const params = new URLSearchParams(window.location.search);
    
    // 載入主分類
    const categoryId = params.get('category_id');
    if (categoryId) {
        filters.categoryId = parseInt(categoryId);
        activeTab.value = parseInt(categoryId);
    } else if (categories.value.main.length > 0) {
        // 預設選擇第一個主分類
        activeTab.value = categories.value.main[0].id;
        filters.categoryId = categories.value.main[0].id;
    }
    
    // 載入子分類
    const subcategories = params.getAll('subcategories[]');
    filters.subcategories = subcategories.map(id => parseInt(id));
    
    // 載入年份
    const yearParams = params.getAll('years[]');
    filters.years = yearParams.map(year => {
        // 處理 "before_2015" 和數字年份
        return isNaN(year) ? year : parseInt(year);
    });
};

// 處理瀏覽器前進/後退
const handlePopState = () => {
    loadFiltersFromURL();
    if (hasFilters()) {
        search();
    }
};

// 檢查是否有篩選條件
const hasFilters = () => {
    return filters.categoryId !== null ||
           filters.subcategories.length > 0 ||
           filters.years.length > 0;
};

// 更新 URL（不重整頁面）
const updateURL = () => {
    const params = new URLSearchParams();
    
    if (filters.categoryId) {
        params.set('category_id', filters.categoryId);
    }
    
    filters.subcategories.forEach(id => {
        params.append('subcategories[]', id);
    });
    
    filters.years.forEach(year => {
        params.append('years[]', year);
    });
    
    if (currentPage.value > 1) {
        params.set('page', currentPage.value);
    }
    
    const newURL = `${window.location.pathname}${params.toString() ? '?' + params.toString() : ''}`;
    window.history.pushState({}, '', newURL);
};

const getSubcategories = (categoryId) => {
    return categories.value.sub.filter(sub => sub.parent_id === categoryId);
};

const switchTab = (categoryId) => {
    // 保留年份選擇，但清除類型選擇
    activeTab.value = categoryId;
    filters.categoryId = categoryId;
    filters.subcategories = [];
    // 不清除年份：filters.years 保持不變
    
    // 更新 URL
    updateURL();
};

const updateFilters = () => {
    // 選項變更時更新 URL（但不自動搜尋）
    updateURL();
};

const clearFilters = () => {
    // 只清除當前分頁的篩選
    filters.subcategories = [];
    filters.years = [];
    // 不清除 categoryId，保持在當前分頁
    
    // 重置頁碼
    currentPage.value = 1;
    
    // 更新 URL
    updateURL();
    
    // 直接執行搜尋
    fetchResults();
};

const search = async () => {
    currentPage.value = 1;
    
    // 更新 URL
    updateURL();
    
    await fetchResults();
};

const fetchResults = async () => {
    loading.value = true;
    
    // 顯示全域 Loading
    $loading.showLoading(props.texts.loading || 'Loading...');
    
    try {
        const response = await $http.get(apiEndpoint.value, {
            params: {
                category_id: filters.categoryId || activeTab.value,
                subcategories: filters.subcategories,
                years: filters.years,
                page: currentPage.value,
                per_page: perPage.value
            }
        });
        
        // 根據內容類型取得正確的資料欄位
        const dataKey = props.contentType === 'drama' ? 'dramas' : 'programs';
        results.value = response.data[dataKey] || [];
        total.value = response.data.total || 0;
    } catch (error) {
        console.error(props.texts.error || 'Search failed:', error);
        results.value = [];
        total.value = 0;
    } finally {
        loading.value = false;
        // 隱藏全域 Loading
        $loading.hideLoading();
    }
};

const changePage = async (page) => {
    currentPage.value = page;
    
    // 更新 URL
    updateURL();
    
    await fetchResults();
    
    // 滾動到頂部
    window.scrollTo({ top: 0, behavior: 'smooth' });
};

const getItemUrl = (item) => {
    return `/${props.contentType}/${item.id}/videos`;
};

// 生命週期
onMounted(() => {
    // 初始化年份選項
    initYears();
    
    // 從 URL 載入初始篩選條件
    loadFiltersFromURL();
    
    // 監聽瀏覽器前進/後退
    window.addEventListener('popstate', handlePopState);
    
    // 如果有篩選條件或有預設分類，執行搜尋
    if (hasFilters() || activeTab.value) {
        search();
    }
});

onBeforeUnmount(() => {
    // 移除事件監聽
    window.removeEventListener('popstate', handlePopState);
});
</script>

<style scoped>
/* 使用前台既有的 main.css 樣式，不需要額外覆寫 */
.loading-spinner {
    min-height: 200px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
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

/* 響應式圖片顯示控制 */
.img img.web {
    display: block;
}

.img img.mobile {
    display: none;
}

/* 手機版顯示手機圖片，隱藏桌面圖片 */
@media (max-width: 768px) {
    .img img.web {
        display: none;
    }
    
    .img img.mobile {
        display: block;
    }
}
</style>