<template>
  <div class="content">
    <BreadcrumbItem />

    <!-- 頁面標題與返回按鈕 -->
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h4 class="mb-0">
        <span class="text-muted">廣播觀看統計：</span>
        <span class="text-primary">{{ contentTitle }}</span>
      </h4>
      <button class="btn btn-secondary" @click="goBack">
        <i class="fa fa-arrow-left me-1"></i> 返回列表
      </button>
    </div>

    <!-- 🔽 統計卡片 -->
    <div class="row">
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
  </div>
</template>

<script setup>
import { computed } from "vue";
import BreadcrumbItem from "@/Shared/Admin/Partials/BreadcrumbItem.vue";

// Props
const props = defineProps({
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
});

// 統計數據（四張卡片）
const stats = computed(() => [
  { label: "總觀看次數", value: props.statistics.total_views || 0, color: "text-primary" },
  { label: "會員觀看次數", value: props.statistics.member_views || 0, color: "text-success" },
  { label: "訪客觀看次數", value: props.statistics.guest_views || 0, color: "text-info" },
  { label: "收藏人數", value: props.statistics.collection_count || 0, color: "text-warning" },
]);

// 返回上一頁
const goBack = () => window.history.back();
</script>

<script>
import Layout from "@/Shared/Admin/Layout.vue";
export default { layout: Layout };
</script>