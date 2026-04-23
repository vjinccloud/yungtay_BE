<template>
  <!-- Main Container -->
  <main id="main-container">
    <!-- Page Content -->
    <div class="content">
      <!-- Header Row -->
      <div class="row mb-4">
        <div class="col-12">
          <div class="d-flex align-items-center justify-content-between content-heading">
            <h3 class="mb-0">儀表板</h3>
            <div class="d-flex gap-2">
              <!-- 重新計算統計數據按鈕 - 需要 admin.statistics.recalculate 權限 -->
              <button
                v-if="can('admin.statistics.recalculate')"
                @click="recalculateStatistics"
                :disabled="isRecalculating"
                class="btn btn-sm btn-alt-warning"
                title="從 view_logs 重新計算所有統計（清理已刪除記錄）"
              >
                <i class="fa" :class="isRecalculating ? 'fa-spinner fa-spin' : 'fa-calculator'"></i>
                {{ isRecalculating ? '重算中...' : '重新計算' }}
              </button>
              <!-- 更新儀表板數據按鈕 - 需要 admin.dashboard.refresh 權限 -->
              <button
                v-if="can('admin.dashboard.refresh')"
                @click="clearCache"
                :disabled="isClearingCache"
                class="btn btn-sm btn-alt-primary"
                title="快速同步 Redis 到資料庫並清除快取"
              >
                <i class="fa" :class="isClearingCache ? 'fa-spinner fa-spin' : 'fa-sync-alt'"></i>
                {{ isClearingCache ? '更新中...' : '更新數據' }}
              </button>
            </div>
          </div>
        </div>
      </div>
      <!-- Stats Row -->
      <div class="row">
        <!-- 新聞觀看數 -->
        <div class="col-6 col-lg-4 col-xl-2">
          <div class="block block-rounded block-link-shadow text-end">
            <div class="block-content block-content-full d-sm-flex justify-content-between align-items-center">
              <div class="d-none d-sm-block">
                <i class="fa fa-newspaper fa-2x opacity-25 text-primary"></i>
              </div>
              <div>
                <div class="fs-3 fw-semibold">{{ formatNumber(todayStats.viewsByType?.article?.total || 0) }}</div>
                <div class="fs-sm fw-semibold text-uppercase text-muted">新聞觀看</div>
                <div class="fs-sm text-info">
                  <i class="fa fa-chart-line"></i> 今日 {{ todayStats.viewsByType?.article?.today || 0 }}
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- 影音觀看數 -->
        <div class="col-6 col-lg-4 col-xl-2">
          <div class="block block-rounded block-link-shadow text-end">
            <div class="block-content block-content-full d-sm-flex justify-content-between align-items-center">
              <div class="d-none d-sm-block">
                <i class="fa fa-tv fa-2x opacity-25 text-success"></i>
              </div>
              <div>
                <div class="fs-3 fw-semibold">{{ formatNumber(todayStats.viewsByType?.drama?.total || 0) }}</div>
                <div class="fs-sm fw-semibold text-uppercase text-muted">影音觀看</div>
                <div class="fs-sm text-info">
                  <i class="fa fa-chart-line"></i> 今日 {{ todayStats.viewsByType?.drama?.today || 0 }}
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- 節目觀看數 -->
        <div class="col-6 col-lg-4 col-xl-2">
          <div class="block block-rounded block-link-shadow text-end">
            <div class="block-content block-content-full d-sm-flex justify-content-between align-items-center">
              <div class="d-none d-sm-block">
                <i class="fa fa-play-circle fa-2x opacity-25 text-pink"></i>
              </div>
              <div>
                <div class="fs-3 fw-semibold">{{ formatNumber(todayStats.viewsByType?.program?.total || 0) }}</div>
                <div class="fs-sm fw-semibold text-uppercase text-muted">節目觀看</div>
                <div class="fs-sm text-info">
                  <i class="fa fa-chart-line"></i> 今日 {{ todayStats.viewsByType?.program?.today || 0 }}
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- 直播觀看數 -->
        <div class="col-6 col-lg-4 col-xl-2">
          <div class="block block-rounded block-link-shadow text-end">
            <div class="block-content block-content-full d-sm-flex justify-content-between align-items-center">
              <div class="d-none d-sm-block">
                <i class="fa fa-broadcast-tower fa-2x opacity-25 text-warning"></i>
              </div>
              <div>
                <div class="fs-3 fw-semibold">{{ formatNumber(todayStats.viewsByType?.live?.total || 0) }}</div>
                <div class="fs-sm fw-semibold text-uppercase text-muted">直播觀看</div>
                <div class="fs-sm text-info">
                  <i class="fa fa-chart-line"></i> 今日 {{ todayStats.viewsByType?.live?.today || 0 }}
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- 廣播觀看數 -->
        <div class="col-6 col-lg-4 col-xl-2">
          <div class="block block-rounded block-link-shadow text-end">
            <div class="block-content block-content-full d-sm-flex justify-content-between align-items-center">
              <div class="d-none d-sm-block">
                <i class="fa fa-microphone fa-2x opacity-25 text-danger"></i>
              </div>
              <div>
                <div class="fs-3 fw-semibold">{{ formatNumber(todayStats.viewsByType?.radio?.total || 0) }}</div>
                <div class="fs-sm fw-semibold text-uppercase text-muted">廣播收聽</div>
                <div class="fs-sm text-info">
                  <i class="fa fa-chart-line"></i> 今日 {{ todayStats.viewsByType?.radio?.today || 0 }}
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- 總會員數 -->
        <div class="col-6 col-lg-4 col-xl-2">
          <div class="block block-rounded block-link-shadow text-end">
            <div class="block-content block-content-full d-sm-flex justify-content-between align-items-center">
              <div class="d-none d-sm-block">
                <i class="fa fa-users fa-2x opacity-25"></i>
              </div>
              <div>
                <div class="fs-3 fw-semibold">{{ formatNumber(todayStats.users?.total || 0) }}</div>
                <div class="fs-sm fw-semibold text-uppercase text-muted">總會員</div>
                <div class="fs-sm text-info">
                  <i class="fa fa-user-plus"></i> 今日註冊 {{ todayStats.users?.today_registered || 0 }}
                </div>
              </div>
            </div>
          </div>
        </div>

      </div>

      <!-- View Statistics Row -->
      <div class="row">
        <div class="col-md-8">
          <div class="block block-rounded">
            <div class="block-header">
              <h3 class="block-title">
                觀看數統計 <small>各類型觀看數據</small>
              </h3>
            </div>
            <div class="block-content">
              <div class="table-responsive">
                <table class="table table-sm table-hover">
                  <thead>
                    <tr>
                      <th>內容類型</th>
                      <th class="text-center">會員觀看記錄人次</th>
                      <th class="text-center">非會員觀看記錄人次</th>
                      <th class="text-center">總觀看記錄人次</th>
                      <th class="text-center">總收藏人數</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="stat in todayStats.viewStatistics" :key="stat.type">
                      <td>
                        <span class="badge" :class="getContentTypeBadge(stat.type)">
                          {{ getContentTypeLabel(stat.type) }}
                        </span>
                      </td>
                      <td class="text-center fw-semibold">
                        {{ formatNumber(stat.member_views || 0) }}
                      </td>
                      <td class="text-center fw-semibold text-info">
                        {{ formatNumber(stat.guest_views || 0) }}
                      </td>
                      <td class="text-center fw-semibold text-success">
                        {{ formatNumber(stat.total_views || 0) }}
                      </td>
                      <td class="text-center fw-semibold text-primary">
                        {{ formatNumber(stat.collection_count || 0) }}
                      </td>
                    </tr>
                    <tr v-if="!todayStats.viewStatistics || todayStats.viewStatistics.length === 0">
                      <td colspan="5" class="text-center text-muted py-3">
                        暫無觀看數據
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>

        <!-- System Status Detail -->
        <div class="col-md-4">
          <div class="block block-rounded">
            <div class="block-header">
              <h3 class="block-title">
                系統監控 <small>服務狀態</small>
              </h3>
            </div>
            <div class="block-content">
              <div class="row items-push">
                <div class="col-sm-6">
                  <div class="d-flex align-items-center">
                    <div class="me-3">
                      <i class="fa fa-database fa-2x" :class="getStatusColor(systemStatus.database.status)"></i>
                    </div>
                    <div>
                      <div class="fw-semibold">資料庫</div>
                      <div class="fs-sm text-muted">{{ systemStatus.database.message }}</div>
                    </div>
                  </div>
                </div>
                <div class="col-sm-6">
                  <div class="d-flex align-items-center">
                    <div class="me-3">
                      <i class="fa fa-memory fa-2x" :class="getStatusColor(systemStatus.cache.status)"></i>
                    </div>
                    <div>
                      <div class="fw-semibold">快取系統</div>
                      <div class="fs-sm text-muted">{{ systemStatus.cache.message }}</div>
                    </div>
                  </div>
                </div>
              </div>
              <div class="row items-push">
                <div class="col-sm-6">
                  <div class="d-flex align-items-center">
                    <div class="me-3">
                      <i class="fa fa-hdd fa-2x" :class="getStatusColor(systemStatus.storage.status)"></i>
                    </div>
                    <div>
                      <div class="fw-semibold">儲存空間</div>
                      <div class="fs-sm text-muted">{{ systemStatus.storage.message }}</div>
                    </div>
                  </div>
                </div>
                <div class="col-sm-6">
                  <div class="d-flex align-items-center">
                    <div class="me-3">
                      <i class="fa fa-tasks fa-2x" :class="getStatusColor(systemStatus.queue.status)"></i>
                    </div>
                    <div>
                      <div class="fw-semibold">佇列系統</div>
                      <div class="fs-sm text-muted">{{ systemStatus.queue.message }}</div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Recent Activities Row -->
      <div class="row">
        <!-- Popular Content with Tabs -->
        <div class="col-md-8">
          <div class="block block-rounded">
            <div class="block-header">
              <h3 class="block-title">
                熱門內容排行 <small>各時段排名</small>
              </h3>
            </div>
            <div class="block-content">
              <!-- Tab Navigation -->
              <ul class="nav nav-tabs nav-tabs-block" role="tablist">
                <li class="nav-item">
                  <button 
                    class="nav-link"
                    :class="{ active: activePeriodTab === 'today' }"
                    @click="activePeriodTab = 'today'"
                    type="button"
                  >
                    <i class="fa fa-fw fa-sun"></i> 今日
                  </button>
                </li>
                <li class="nav-item">
                  <button 
                    class="nav-link"
                    :class="{ active: activePeriodTab === 'week' }"
                    @click="activePeriodTab = 'week'"
                    type="button"
                  >
                    <i class="fa fa-fw fa-calendar-week"></i> 本週
                  </button>
                </li>
                <li class="nav-item">
                  <button 
                    class="nav-link"
                    :class="{ active: activePeriodTab === 'month' }"
                    @click="activePeriodTab = 'month'"
                    type="button"
                  >
                    <i class="fa fa-fw fa-calendar"></i> 本月
                  </button>
                </li>
                <li class="nav-item">
                  <button 
                    class="nav-link"
                    :class="{ active: activePeriodTab === 'total' }"
                    @click="activePeriodTab = 'total'"
                    type="button"
                  >
                    <i class="fa fa-fw fa-chart-bar"></i> 總計
                  </button>
                </li>
              </ul>

              <!-- 熱門內容排行表格 -->
              <div class="table-responsive">
                <table class="table table-hover" v-if="getCurrentPeriodData() && Object.values(getCurrentPeriodData()).some(arr => arr && arr.length > 0)">
                  <thead>
                    <tr>
                      <th width="120">內容類型</th>
                      <th width="17%">第1名</th>
                      <th width="17%">第2名</th>
                      <th width="17%">第3名</th>
                      <th width="17%">第4名</th>
                      <th width="17%">第5名</th>
                    </tr>
                  </thead>
                  <tbody>
                    <!-- 新聞排行 -->
                    <tr>
                      <td>
                        <span class="badge bg-primary">新聞</span>
                      </td>
                      <td v-for="index in 5" :key="`article-${index}`">
                        <span v-if="getCurrentPeriodData().article && getCurrentPeriodData().article[index-1]" 
                              class="fw-semibold content-title"
                              :title="getCurrentPeriodData().article[index-1].title">
                          {{ getCurrentPeriodData().article[index-1].title }}
                        </span>
                        <span v-else class="text-muted fs-sm">暫無數據</span>
                      </td>
                    </tr>
                    
                    <!-- 影音排行 -->
                    <tr>
                      <td>
                        <span class="badge bg-success">影音</span>
                      </td>
                      <td v-for="index in 5" :key="`drama-${index}`">
                        <span v-if="getCurrentPeriodData().drama && getCurrentPeriodData().drama[index-1]" 
                              class="fw-semibold content-title"
                              :title="getCurrentPeriodData().drama[index-1].title">
                          {{ getCurrentPeriodData().drama[index-1].title }}
                        </span>
                        <span v-else class="text-muted fs-sm">暫無數據</span>
                      </td>
                    </tr>
                    
                    <!-- 節目排行 -->
                    <tr>
                      <td>
                        <span class="badge bg-pink">節目</span>
                      </td>
                      <td v-for="index in 5" :key="`program-${index}`">
                        <span v-if="getCurrentPeriodData().program && getCurrentPeriodData().program[index-1]" 
                              class="fw-semibold content-title"
                              :title="getCurrentPeriodData().program[index-1].title">
                          {{ getCurrentPeriodData().program[index-1].title }}
                        </span>
                        <span v-else class="text-muted fs-sm">暫無數據</span>
                      </td>
                    </tr>
                    
                    <!-- 直播排行 -->
                    <tr>
                      <td>
                        <span class="badge bg-warning">直播</span>
                      </td>
                      <td v-for="index in 5" :key="`live-${index}`">
                        <span v-if="getCurrentPeriodData().live && getCurrentPeriodData().live[index-1]" 
                              class="fw-semibold content-title"
                              :title="getCurrentPeriodData().live[index-1].title">
                          {{ getCurrentPeriodData().live[index-1].title }}
                        </span>
                        <span v-else class="text-muted fs-sm">暫無數據</span>
                      </td>
                    </tr>
                    
                    <!-- 廣播排行 -->
                    <tr>
                      <td>
                        <span class="badge bg-danger">廣播</span>
                      </td>
                      <td v-for="index in 5" :key="`radio-${index}`">
                        <span v-if="getCurrentPeriodData().radio && getCurrentPeriodData().radio[index-1]" 
                              class="fw-semibold content-title"
                              :title="getCurrentPeriodData().radio[index-1].title">
                          {{ getCurrentPeriodData().radio[index-1].title }}
                        </span>
                        <span v-else class="text-muted fs-sm">暫無數據</span>
                      </td>
                    </tr>
                  </tbody>
                </table>
                
                <!-- 沒有數據時的提示 -->
                <div v-else class="text-center py-5">
                  <div class="text-muted">暫無熱門內容數據</div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Recent Users -->
        <div class="col-md-4">
          <div class="block block-rounded">
            <div class="block-header">
              <h3 class="block-title">
                最新會員 <small>最近註冊</small>
              </h3>
            </div>
            <div class="block-content">
              <div v-if="recentActivities.recent_users?.length > 0">
                <div 
                  v-for="user in recentActivities.recent_users" 
                  :key="user.id"
                  class="d-flex py-2"
                >
                  <div class="flex-shrink-0">
                    <i class="fa fa-user-circle fa-2x text-muted"></i>
                  </div>
                  <div class="flex-grow-1 ms-3">
                    <div class="fw-semibold">{{ user.name }}</div>
                    <div class="fs-sm text-muted">{{ user.email }}</div>
                    <div class="fs-sm text-muted">{{ formatDateTime(user.created_at) }}</div>
                  </div>
                </div>
              </div>
              <div v-else class="text-center py-3">
                <div class="text-muted">暫無新會員</div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    <!-- END Page Content -->
  </main>
  <!-- END Main Container -->
</template>

<script setup>
import { computed, ref, inject } from 'vue'
import { router } from '@inertiajs/vue3'
import Layout from '@/Shared/Admin/Layout.vue'

// Layout設定
defineOptions({
  layout: Layout
})

// Props
const props = defineProps({
  todayStats: {
    type: Object,
    required: true
  },
  systemStatus: {
    type: Object,
    required: true
  },
  recentActivities: {
    type: Object,
    required: true
  },
  pageTitle: {
    type: String,
    default: '儀表板'
  }
})

// Inject dependencies
const sweetAlert = inject('$sweetAlert')
const can = inject('can')

// Reactive data
const activePeriodTab = ref('today')
const isClearingCache = ref(false)
const isRecalculating = ref(false)

// Methods
const formatNumber = (number) => {
  return new Intl.NumberFormat('zh-TW').format(number || 0)
}

const formatDateTime = (dateTime) => {
  if (!dateTime) return ''
  return new Date(dateTime).toLocaleString('zh-TW', {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const clearCache = () => {
  isClearingCache.value = true
  router.post(route('admin.dashboard.clear-cache'), {}, {
    preserveState: false, // 刷新整個頁面數據
    preserveScroll: true,
    onSuccess: (finalRes) => {
      const res = finalRes.props.flash?.result || finalRes.props.result
      if (res && res.status) {
        sweetAlert.showToast(res.msg, 'success')
      } else if (res) {
        sweetAlert.showToast(res.msg || '操作失敗', 'error')
      }
    },
    onError: (errors) => {
      console.error('清除快取失敗:', errors)
      sweetAlert.showToast('更新數據失敗，請重試！', 'error')
    },
    onFinish: () => {
      isClearingCache.value = false
    }
  })
}

const recalculateStatistics = () => {
  sweetAlert.confirm(
    '確定要重新計算？',
    () => {
      isRecalculating.value = true
      router.post(route('admin.dashboard.recalculate-statistics'), {}, {
        preserveState: false, // 刷新整個頁面數據
        preserveScroll: true,
        onSuccess: (finalRes) => {
          const res = finalRes.props.flash?.result || finalRes.props.result
          if (res && res.status) {
            sweetAlert.showToast(res.msg, 'success')
          } else if (res) {
            sweetAlert.showToast(res.msg || '操作失敗', 'error')
          }
        },
        onError: (errors) => {
          console.error('重新計算錯誤:', errors)
          sweetAlert.showToast('重新計算失敗，請重試！', 'error')
        },
        onFinish: () => {
          isRecalculating.value = false
        }
      })
    },
    '此操作會重新計算觀看數資料庫統計，可能會需要一些時間。'
  )
}

const getStatusColor = (status) => {
  switch (status) {
    case 'healthy':
      return 'text-success'
    case 'warning':
      return 'text-warning'
    case 'error':
      return 'text-danger'
    default:
      return 'text-muted'
  }
}

const getSystemStatusClass = () => {
  const healthyCount = Object.values(props.systemStatus).filter(s => s.status === 'healthy').length
  const totalCount = Object.keys(props.systemStatus).length
  
  if (healthyCount === totalCount) return 'text-success'
  if (healthyCount >= totalCount / 2) return 'text-warning'
  return 'text-danger'
}

const getSystemStatusIcon = () => {
  const healthyCount = Object.values(props.systemStatus).filter(s => s.status === 'healthy').length
  const totalCount = Object.keys(props.systemStatus).length
  
  if (healthyCount === totalCount) return 'fa fa-check-circle'
  if (healthyCount >= totalCount / 2) return 'fa fa-exclamation-triangle'
  return 'fa fa-times-circle'
}

const getSystemStatusText = () => {
  const healthyCount = Object.values(props.systemStatus).filter(s => s.status === 'healthy').length
  const totalCount = Object.keys(props.systemStatus).length
  
  if (healthyCount === totalCount) return '系統正常'
  if (healthyCount >= totalCount / 2) return '部分異常'
  return '系統異常'
}

const getSystemStatusTextClass = () => {
  const healthyCount = Object.values(props.systemStatus).filter(s => s.status === 'healthy').length
  const totalCount = Object.keys(props.systemStatus).length
  
  if (healthyCount === totalCount) return 'text-success'
  if (healthyCount >= totalCount / 2) return 'text-warning'
  return 'text-danger'
}

const getContentTypeBadge = (type) => {
  const badges = {
    '新聞': 'bg-primary',
    '影音': 'bg-success',
    '節目': 'bg-pink',
    '直播': 'bg-warning',
    '廣播': 'bg-danger',
    'article': 'bg-primary',
    'drama': 'bg-success',
    'program': 'bg-pink',
    'live': 'bg-warning',
    'radio': 'bg-danger'
  }
  return badges[type] || 'bg-secondary'
}

const getContentTypeLabel = (type) => {
  const labels = {
    'article': '新聞',
    'drama': '影音',
    'program': '節目',
    'live': '直播',
    'radio': '廣播'
  }
  return labels[type] || type
}

// Tab functionality methods
const getCurrentPeriodData = () => {
  const periodData = props.recentActivities?.popularContentsByPeriod?.[activePeriodTab.value]
  return periodData || {
    article: [],
    drama: [],
    program: [],
    live: [],
    radio: []
  }
}

const getRankBadgeClass = (index) => {
  switch (index) {
    case 0:
      return 'bg-warning text-dark' // 金色第一名
    case 1:
      return 'bg-secondary text-white' // 銀色第二名
    case 2:
      return 'bg-danger text-white' // 銅色第三名
    default:
      return 'bg-info text-white' // 其他名次
  }
}
</script>

<style scoped>
.badge-circle {
  width: 24px;
  height: 24px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 12px;
  font-weight: 600;
  line-height: 1;
}

.nav-tabs-block {
  margin-bottom: 1.5rem;
}

.nav-tabs-block .nav-link {
  border-radius: 0.375rem 0.375rem 0 0;
  border: 1px solid transparent;
  color: var(--bs-gray-600);
}

.nav-tabs-block .nav-link.active {
  background-color: var(--bs-primary);
  border-color: var(--bs-primary);
  color: white;
}

.nav-tabs-block .nav-link:hover:not(.active) {
  background-color: var(--bs-gray-100);
  border-color: var(--bs-gray-300);
}

/* 內容標題樣式 - 最多兩行顯示，超過省略號 */
.content-title {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
  text-overflow: ellipsis;
  line-height: 1.3;
  max-width: 200px;
  word-break: break-word;
  min-height: 1.3em;
}
</style>