<template>
  <div class="content">
    <BreadcrumbItem />

    <!-- 頁面標題與返回按鈕 -->
    <div class="d-flex justify-content-between align-items-center mb-4">
      <button class="btn btn-secondary" @click="goBack">
        <i class="fa fa-arrow-left me-1"></i> 返回列表
      </button>
    </div>

    <!-- 🔽 統計卡片 -->
    <div class="row mb-4">
      <div class="col-md-3 col-sm-6" v-for="stat in stats" :key="stat.label">
        <div class="block block-rounded">
          <div class="block-content block-content-full">
            <div class="py-3 text-center">
              <div :class="['fs-2 fw-bold', stat.color]">
                {{ stat.value.toLocaleString() }}
              </div>
              <div class="fs-sm fw-medium text-muted text-uppercase mt-1">
                {{ stat.label }}
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- 🔽 集數觀看明細表格 -->
    <div class="block block-rounded">
      <div class="block-header block-header-default">
        <h3 class="block-title">
          <button type="button"
                  class="btn btn-sm btn-outline-primary"
                  @click="toggleFilter">
            <i class="fas fa-filter me-1"></i>
            搜尋
            <i class="fas fa-chevron-up ms-2"
               :class="{ 'fa-rotate-180': filterExpanded }"
               style="transition: transform 0.3s ease; font-size: 12px;"></i>
          </button>
        </h3>
      </div>

      <div class="block-content block-content-full">
        <!-- 搜尋表單 -->
        <div v-show="filterExpanded" class="mb-4">
          <form @submit.prevent="searchData">
            <div class="row g-3">
              <!-- 季數 -->
              <div class="col-md-4">
                <label class="form-label">季數</label>
                <select class="form-select" v-model="searchParams.season">
                  <option value="">請選擇季數</option>
                  <option v-for="season in props.availableSeasons" :key="season" :value="season">
                    第 {{ season }} 季
                  </option>
                </select>
              </div>

              <!-- 按鈕 -->
              <div class="col-12">
                <div class="d-flex gap-2 mt-3">
                  <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search me-1"></i>搜尋
                  </button>
                  <button type="button" class="btn btn-secondary" @click="resetSearch">
                    <i class="fas fa-refresh me-1"></i>重置
                  </button>
                </div>
              </div>
            </div>
          </form>
        </div>

        <!-- DataTable -->
        <DataTable
          class="table table-bordered table-striped table-vcenter"
          :columns="episodesColumns"
          :options="episodesOptions"
          ref="episodesTable"
        />
      </div>
    </div>
  </div>
</template>


<script setup>
import { ref, reactive, computed, onMounted, nextTick, toRaw } from "vue";
import DataTablesCore from "datatables.net-bs5";
import DataTable from "datatables.net-vue3";
import DataTableHelper from "@/utils/datatableHelper";
import BreadcrumbItem from "@/Shared/Admin/Partials/BreadcrumbItem.vue";

DataTable.use(DataTablesCore);

// Props
const props = defineProps({
  contentType: { type: String, required: true },
  contentId: { type: Number, required: true },
  contentTitle: { type: String, default: "" },
  statistics: {
    type: Object,
    default: () => ({
      total_views: 0,
      member_views: 0,
      guest_views: 0,
      collection_count: 0,
    }),
  },
  availableSeasons: {
    type: Array,
    default: () => []
  }
});

// DataTable refs
const episodesTable = ref(null);
const episodesDt = ref(null);
const rows = ref([]);

// 搜尋功能
const filterExpanded = ref(false);
const searchParams = reactive({
  season: ''
});

// 切換搜尋區塊
const toggleFilter = () => {
  filterExpanded.value = !filterExpanded.value;
};

// 執行搜尋
const searchData = () => {
  if (episodesDt.value && episodesDt.value.ajax) {
    episodesDt.value.ajax.reload();
  }
};

// 重置搜尋
const resetSearch = () => {
  searchParams.season = '';
  if (episodesDt.value && episodesDt.value.ajax) {
    episodesDt.value.ajax.reload();
  }
};

// 統計數據（四張卡片）
const stats = [
  { label: "總觀看次數", value: props.statistics.total_views || 0, color: "text-primary" },
  { label: "會員觀看次數", value: props.statistics.member_views || 0, color: "text-success" },
  { label: "訪客觀看次數", value: props.statistics.guest_views || 0, color: "text-info" },
  { label: "收藏人數", value: props.statistics.collection_count || 0, color: "text-warning" },
];

// 返回上一頁
const goBack = () => window.history.back();

// DataTable 欄位設定
const episodesColumns = computed(() => [
  {
    title: props.contentType === 'drama' ? "影音名稱" : "節目名稱",
    data: null,
    className: "fw-semibold",
    width: "200px",
    orderable: false,
    render: () => props.contentTitle || "-",
  },
  {
    title: "季數",
    data: "season_number",
    className: "text-center fw-semibold",
    width: "80px",
    orderable: false,
    render: (d) => {
      const num = Number(d);
      return Number.isFinite(num) && num > 0 ? `第${num}季` : "-";
    },
  },
  {
    title: "集數",
    data: "episode_number",
    className: "text-center fw-semibold",
    width: "80px",
    orderable: false,
    render: (d) => {
      const num = Number(d);
      return Number.isFinite(num) && num > 0 ? `第${num}集` : "-";
    },
  },
  {
    title: "總觀看次數",
    data: "total_views",
    className: "text-center",
    width: "120px",
    orderable: false,

  },
  {
    title: "會員觀看次數",
    data: "member_views",
    className: "text-center",
    width: "120px",
    orderable: false,

  },
  {
    title: "訪客觀看次數",
    data: "guest_views",
    className: "text-center",
    width: "120px",
    orderable: false,

  },
]);

// DataTable 選項
const episodesOptions = reactive({
  ...DataTableHelper.getBaseOptions(),
  searching: false,
  ordering: false,
  pageLength: 25,
  lengthMenu: [10, 25, 50, 100],
  ajax: (data, callback) => {
    const searchData = {
      ...data,
      search_params: { ...toRaw(searchParams) },
    };

    DataTableHelper.fetchTableData(
      `/admin/${props.contentType}s/${props.contentId}/view-logs/data`,
      searchData,
      callback,
      rows,
      "",              // responseKey 不需要（AJAX 模式）
      searchData.search_params,  // 傳遞搜尋參數
      (error) => {
        console.error('DataTable 載入失敗:', error);
      },
      true             // useAjax = true（純 AJAX）
    );
  },
  drawCallback: () => {
    DataTableHelper.defaultDrawCallback();
  },
});

// 初始化 DataTable
const initializeDataTable = async () => {
  try {
    // 等待 DOM 完全載入
    await nextTick();

    if (!episodesTable.value) {
      console.error('Table element not found');
      return;
    }

    // 先初始化 DataTable（不管回傳值）
    await DataTableHelper.createDataTable(episodesTable.value, episodesOptions);

    // 再從 DOM 元素取得 DataTable API 實例
    await nextTick();
    episodesDt.value = episodesTable.value?.dt;

    if (!episodesDt.value) {
      console.error('DataTable API instance not found');
    }
  } catch (error) {
    console.error('初始化 DataTable 時發生錯誤:', error);
  }
};

// 組件掛載
onMounted(async () => {
  try {
    await initializeDataTable();
  } catch (error) {
    console.error('組件掛載時發生錯誤:', error);
  }
});
</script>

<script>
import Layout from "@/Shared/Admin/Layout.vue";
export default { layout: Layout };
</script>

<style scoped>
.fa-rotate-180 {
  transform: rotate(180deg);
}

.form-label {
  font-weight: 500;
  color: #495057;
  margin-bottom: 4px;
}

.gap-2 {
  gap: 0.5rem !important;
}
</style>
