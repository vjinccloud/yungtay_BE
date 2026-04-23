<!-- resources/js/InertiaPages/Admin/Programs/Index.vue -->
<!-- 簡化版：使用共用的 Index 組件 -->
<template>
    <ContentIndex
        content-type="program"
        :categories="categories"
        :subcategories="subcategories"
    />
</template>

<script setup>
import Layout from "@/Shared/Admin/Layout.vue"
import ContentIndex from "@/InertiaPages/Admin/Shared/Content/Index.vue"

// 定義 props
const props = defineProps({
    programs: Object,
    categories: {
        type: Array,
        default: () => []
    },
    subcategories: {
        type: Array,
        default: () => []
    }
});

// 解構 props 以便傳遞給子組件
const { categories, subcategories } = props;
</script>

<script>
export default {
    layout: Layout,
};
</script>