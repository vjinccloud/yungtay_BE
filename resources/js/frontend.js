// resources/js/frontend.js
import { createApp, reactive } from 'vue';
import axios from 'axios';
import { ZiggyVue } from '../../vendor/tightenco/ziggy/dist/index.esm';
import { Ziggy } from './ziggy';
import SweetAlertPlugin from './utils/sweetalert2Plugin';
// 確保 SweetAlert2 CSS 有較高的優先級
import 'sweetalert2/dist/sweetalert2.min.css';

// ========================================
// EventBus 機制：jQuery ↔ Vue 通訊
// ========================================
const EventBus = reactive({
    events: {},
    emit(event, data) {
        if (this.events[event]) {
            this.events[event].forEach(callback => callback(data));
        }
    },
    on(event, callback) {
        if (!this.events[event]) {
            this.events[event] = [];
        }
        this.events[event].push(callback);
    },
    off(event, callback) {
        if (!this.events[event]) return;
        const index = this.events[event].indexOf(callback);
        if (index > -1) {
            this.events[event].splice(index, 1);
        }
    }
});

// 暴露到全域供 jQuery 使用
window.EventBus = EventBus;

// 設定 axios
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
axios.defaults.headers.common['X-CSRF-TOKEN'] = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
axios.defaults.withCredentials = true; // 確保 session cookie 被發送

// 匯入前台 Vue 組件
import NewsList from './frontend/news/NewsList.vue';
import Pagination from './frontend/common/Pagination.vue';
import MediaFilter from './frontend/common/MediaFilter.vue';
import FilterResults from './frontend/common/FilterResults.vue';
import EpisodeList from './frontend/video/EpisodeList.vue';
import LivePlayer from './frontend/live/LivePlayer.vue';
import RadioList from './frontend/radio/RadioList.vue';
import RadioEpisodeList from './frontend/radio/RadioEpisodeList.vue';
import ArticleListPage from './frontend/articles/ArticleListPage.vue';
import LoginForm from './frontend/member/LoginForm.vue';
import RegisterForm from './frontend/member/RegisterForm.vue';
import CompleteProfileForm from './frontend/member/CompleteProfileForm.vue';
import EmailVerification from './frontend/member/EmailVerification.vue';
import AccountForm from './frontend/member/AccountForm.vue';
import ForgotPasswordForm from './frontend/member/ForgotPasswordForm.vue';
import ResetPasswordForm from './frontend/member/ResetPasswordForm.vue';
import LoadingOverlay from './frontend/common/LoadingOverlay.vue';
import ViewRecorder from './frontend/common/ViewRecorder.vue';
import CollectionButton from './frontend/common/CollectionButton.vue';
import MemberCollectionList from './frontend/member/MemberCollectionList.vue';
import MemberViewHistory from './frontend/member/MemberViewHistory.vue';
import SearchResults from './frontend/search/SearchResults.vue';
import CustomerServiceForm from './frontend/customer-service/CustomerServiceForm.vue';
import MemberCustomerServiceRecords from './frontend/member/MemberCustomerServiceRecords.vue';
import MemberNotificationList from './frontend/member/MemberNotificationList.vue';
import AudioPlayer from './frontend/common/AudioPlayer.vue';

// 匯入 Loading composable
import { useLoading } from './composables/frontend/useLoading.js';

// 設定 Loading
const loading = useLoading();

// 建立 Vue 應用
const app = createApp({
  setup() {
    // 解構 loading 物件，讓模板可以直接使用
    const { isLoading, showLoading, hideLoading } = loading;
    return {
      isLoading,
      showLoading,
      hideLoading,
      loading: loading
    }
  },
  data() {
    return {
      currentForm: 'login'
    }
  },
  mounted() {
    // 從 meta 標籤或全域變數取得當前 tab（如果有的話）
    const metaCurrentTab = document.querySelector('meta[name="current-tab"]')?.getAttribute('content');
    if (metaCurrentTab) {
      this.currentForm = metaCurrentTab;
    } else {
      // 根據當前網址設定初始表單
      const path = window.location.pathname
      this.currentForm = path.includes('/register') ? 'register' : 'login'
    }
    
    // 暴露給全域使用
    window.vueApp = this;
    window.$loading = this.loading; // 暴露 loading 給全域
  }
});

// 使用 Ziggy
app.use(ZiggyVue, Ziggy);

// 全域註冊組件
app.component('news-list', NewsList);
app.component('pagination', Pagination);
app.component('program-filter', MediaFilter); // 節目篩選使用共用組件
app.component('media-filter', MediaFilter); // 共用篩選組件
app.component('media-filter-results', FilterResults); // 篩選結果顯示組件（影音/節目整合頁面使用）
app.component('episode-list', EpisodeList);
app.component('live-player', LivePlayer);
app.component('radio-list', RadioList);
app.component('radio-episode-list', RadioEpisodeList);
app.component('article-list-page', ArticleListPage);
app.component('login-form', LoginForm);
app.component('register-form', RegisterForm);
app.component('complete-profile-form', CompleteProfileForm);
app.component('email-verification', EmailVerification);
app.component('account-form', AccountForm);
app.component('forgot-password-form', ForgotPasswordForm);
app.component('reset-password-form', ResetPasswordForm);
app.component('loading-overlay', LoadingOverlay);
app.component('view-recorder', ViewRecorder);
app.component('collection-button', CollectionButton);
app.component('member-collection-list', MemberCollectionList);
app.component('member-view-history', MemberViewHistory);
app.component('search-results', SearchResults);
app.component('customer-service-form', CustomerServiceForm);
app.component('member-customer-service-records', MemberCustomerServiceRecords);
app.component('member-notification-list', MemberNotificationList);
app.component('audio-player', AudioPlayer);

// 全域屬性（如果需要）
app.config.globalProperties.$axios = axios;

// 提供其他全域函數給組件使用
app.provide('asset', window.asset || ((path) => `/${path}`));

// 提供 HTTP 客戶端給組件使用
app.provide('$http', axios);

// 提供 SweetAlert2 給組件使用
app.provide('$sweetAlert', SweetAlertPlugin);

// 同時將原始 SweetAlert2 和包裝方法都暴露到全域，供 jQuery/vanilla JS 使用
import Swal from 'sweetalert2';
window.Swal = Swal;
window.$sweetAlert = SweetAlertPlugin;

// 提供 Loading 給組件使用
app.provide('$loading', loading);

// 注意：已改為手動控制 loading，不使用自動攔截器
// loading.setupAxiosInterceptors(axios);

// 掛載應用
const rootElement = document.getElementById('frontend-app');
if (rootElement) {
    app.mount('#frontend-app');
}

// 其他前台功能初始化
document.addEventListener('DOMContentLoaded', () => {
    // 其他前台 JavaScript 功能
});
