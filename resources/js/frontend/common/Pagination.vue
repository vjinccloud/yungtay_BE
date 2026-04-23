
<!-- resources/js/frontend/common/Pagination.vue -->
<template>
  <div v-if="totalPages > 1" class="pagination-div">
    <a 
      class="prev" 
      :class="{ disabled: currentPage === 1 }"
      @click.prevent="handlePageChange(currentPage - 1)"
      style="cursor: pointer"></a>
    
    <template v-for="page in displayPages" :key="page">
      <span v-if="page === '...'" class="dots">...</span>
      <a 
        v-else
        :class="{ active: page === currentPage }"
        @click.prevent="handlePageChange(page)"
        style="cursor: pointer">
        {{ page }}
      </a>
    </template>
    
    <a 
      class="next"
      :class="{ disabled: currentPage === totalPages }"
      @click.prevent="handlePageChange(currentPage + 1)"
      style="cursor: pointer"></a>
  </div>
</template>

<script setup>
import { computed } from 'vue';

// Props
const props = defineProps({
  currentPage: {
    type: Number,
    required: true
  },
  totalPages: {
    type: Number,
    required: true
  },
  maxVisible: {
    type: Number,
    default: 7
  }
});

// Emits
const emit = defineEmits(['page-change']);

// 計算屬性 - 顯示的頁碼
const displayPages = computed(() => {
  const pages = [];
  const total = props.totalPages;
  const current = props.currentPage;
  const maxVisible = props.maxVisible;
  
  if (total <= maxVisible) {
    // 如果總頁數小於等於最大顯示數，顯示所有頁碼
    for (let i = 1; i <= total; i++) {
      pages.push(i);
    }
  } else {
    // 總是顯示第一頁
    pages.push(1);
    
    if (current > 3) {
      pages.push('...');
    }
    
    // 顯示當前頁附近的頁碼
    for (let i = Math.max(2, current - 1); i <= Math.min(total - 1, current + 1); i++) {
      pages.push(i);
    }
    
    if (current < total - 2) {
      pages.push('...');
    }
    
    // 總是顯示最後一頁
    pages.push(total);
  }
  
  return pages;
});

// 方法
const handlePageChange = (page) => {
  if (page < 1 || page > props.totalPages || page === props.currentPage) return;
  
  emit('page-change', page);
  
  // 可選：滾動到頂部
  window.scrollTo({ top: 0, behavior: 'smooth' });
};
</script>

<style scoped>
/* 分頁組件樣式 */
.pagination-div .disabled {
  opacity: 0.5;
  cursor: not-allowed;
  pointer-events: none;
}

.pagination-div .dots {
  padding: 0 10px;
}
</style>