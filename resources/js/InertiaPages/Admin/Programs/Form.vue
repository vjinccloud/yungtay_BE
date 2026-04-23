<!-- resources/js/InertiaPages/Admin/Programs/Form.vue -->
<!-- 簡化版：使用共用的 Form 組件 -->
<template>
    <ContentForm
        content-type="program"
        :content-data="program"
        :categories="categories"
        :subcategories="subcategories"
        :video-seasons="videoSeasons"
    />
</template>

<script setup>
import Layout from "@/Shared/Admin/Layout.vue"
import ContentForm from "@/InertiaPages/Admin/Shared/Content/Form.vue"

// 編輯模式時接收 props
const props = defineProps({
    program: {
        type: Object,
        default: null
    },
    categories: {
        type: Array,
        default: () => []
    },
    subcategories: {
        type: Array,
        default: () => []
    },
    videoSeasons: {
        type: Array,
        default: () => []
    }
})
</script>

<script>
export default {
    layout: Layout,
};
</script>