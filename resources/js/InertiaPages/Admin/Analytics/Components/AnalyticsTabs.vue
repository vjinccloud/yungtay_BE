<template>
    <div class="block block-rounded">
        <div class="block-content block-content-full p-0">
            <!-- Tab Navigation -->
            <ul class="nav nav-tabs nav-tabs-block" role="tablist">
                <li class="nav-item" v-for="(tab, index) in tabs" :key="index">
                    <button
                        class="nav-link"
                        :class="{ active: currentTab === index }"
                        @click="changeTab(index)"
                        type="button"
                        role="tab"
                    >
                        {{ tab.title }}
                    </button>
                </li>
            </ul>

            <!-- Tab Content -->
            <div class="tab-content">
                <slot :name="`tab-${currentTab}`"></slot>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue';

/**
 * Analytics Tab 切換組件
 *
 * 功能：
 * - 4 個 Tab 切換介面
 * - 性別總觀看、年齡層總觀看、男性年齡層、女性年齡層
 */

// Props 定義
const props = defineProps({
    tabs: {
        type: Array,
        required: true,
        default: () => [],
    },
    initialTab: {
        type: Number,
        default: 0,
    },
});

// Emits 定義
const emit = defineEmits(['tab-changed']);

// 當前 Tab
const currentTab = ref(props.initialTab);

/**
 * 切換 Tab
 * @param {number} index - Tab 索引
 */
const changeTab = (index) => {
    currentTab.value = index;
    emit('tab-changed', index);
};
</script>

<style scoped>
/* Tab 樣式優化（參考首頁深色背景設計）*/
:deep(.nav-tabs-block) {
    margin-bottom: 0;
    background-color: #3a3f51;
    border-bottom: none;
    padding: 0;
}

:deep(.nav-tabs-block .nav-link) {
    border: none;
    border-bottom: 3px solid transparent;
    padding: 1rem 1.5rem;
    font-weight: 500;
    color: rgba(255, 255, 255, 0.7);
    transition: all 0.2s ease;
    background-color: transparent;
}

:deep(.nav-tabs-block .nav-link:hover) {
    color: #fff;
    background-color: rgba(255, 255, 255, 0.1);
    border-bottom-color: transparent;
}

:deep(.nav-tabs-block .nav-link.active) {
    color: #fff !important;
    background-color: #2c3142 !important;
    border-bottom-color: #f97316 !important;
    font-weight: 600;
}

:deep(.tab-content) {
    padding: 0;
    background-color: transparent;
}
</style>
