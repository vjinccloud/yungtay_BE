<template>
  <div class="member-customer-service-records">
    <!-- Loading State -->
    <div v-if="loading" class="loading-state">
    </div>

    <!-- Customer Service Records -->
    <div v-else-if="customerServices.length > 0" class="customer-service-records-list">
      <div class="table-div">
        <div class="table">
          <div class="thead">
            <div class="tr">
              <div class="td">{{ texts.subject || '主旨' }}</div>
              <div class="td">{{ texts.message || '留言' }}</div>
              <div class="td">{{ texts.date || '日期' }}</div>
            </div>
          </div>
          <div class="tbody">
            <div v-for="record in customerServices" :key="record.id" class="tr">
              <div class="td">
                {{ record.subject }}
              </div>
              <div class="td">
                {{ truncateText(record.message, 100) }}
              </div>
              <div class="td">
                {{ formatDate(record.created_at) }}
              </div>
            </div>
          </div>
        </div>

        <!-- Pagination -->
        <div v-if="totalPages > 1" class="pagination-wrapper">
          <pagination
            :current-page="currentPage"
            :total-pages="totalPages"
            @page-change="handlePageChange">
          </pagination>
        </div>
      </div>
    </div>

    <!-- Empty State - 只在載入完成且沒有資料時顯示 -->
    <div v-else-if="!loading && customerServices.length === 0" class="no-data">
      <p>{{ texts.no_records || '目前沒有客服紀錄' }}</p>
    </div>

  </div>
</template>

<script setup>
import { ref, onMounted, inject } from 'vue'
import axios from 'axios'
import Pagination from '@/frontend/common/Pagination.vue'

// Props
const props = defineProps({
  texts: {
    type: Object,
    default: () => ({
      subject: '主旨',
      message: '留言',
      date: '日期',
      no_records: '目前沒有客服紀錄'
    })
  }
})

// Injections
const sweetAlert = inject('$sweetAlert')

// Reactive state
const customerServices = ref([])
const currentPage = ref(1)
const totalPages = ref(1)
const loading = ref(true) // 初始狀態為載入中

// Methods
const loadCustomerServices = async (page = 1) => {
  loading.value = true

  try {
    const response = await axios.get('/member/customer-service-records', {
      params: {
        page: page,
        ajax: 1 // 標記為 AJAX 請求
      }
    })

    if (response.data.status) {
      const paginatedData = response.data.data // 這裡直接是分頁物件
      customerServices.value = paginatedData.data || []
      currentPage.value = paginatedData.current_page || 1
      totalPages.value = paginatedData.last_page || 1
    } else {
      sweetAlert.showToast(response.data.msg || '載入失敗', 'error')
      customerServices.value = []
    }
  } catch (error) {
    console.error('[MemberCustomerServiceRecords] 載入客服紀錄失敗:', error)
    sweetAlert.showToast('載入失敗，請稍後再試', 'error')
    customerServices.value = []
  } finally {
    loading.value = false
  }
}

const handlePageChange = (page) => {
  currentPage.value = page
  loadCustomerServices(page)
}

// Utility functions
const truncateText = (text, maxLength) => {
  if (!text) return ''
  if (text.length <= maxLength) return text
  return text.substring(0, maxLength) + '...'
}

const formatDate = (dateString) => {
  if (!dateString) return ''
  const date = new Date(dateString)
  return date.toLocaleDateString('zh-TW') + ' ' + date.toLocaleTimeString('zh-TW', {
    hour: '2-digit',
    minute: '2-digit'
  })
}

// Lifecycle
onMounted(async () => {
  // 載入客服紀錄
  await loadCustomerServices()
})
</script>

<style scoped>
/* 使用設計版面的原生樣式，只保留必要的覆寫 */
.member-customer-service-records {
  width: 100%;
}

/* Loading 狀態樣式 */
.loading-state {
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: 400px;
  text-align: center;
  color: #fff;
}

.loading-state p {
  font-size: 18px;
  margin: 0;
}

/* 無資料狀態樣式 */
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



/* 分頁容器 */
.pagination-wrapper {
  margin-top: 30px;
  display: flex;
  justify-content: center;
}
</style>