<!-- resources/js/InertiaPages/Admin/DramaThemes/Form.vue -->
<!-- 簡化版：使用共用的 Themes Form 組件 -->
<template>
    <ThemesForm
        content-type="drama"
        :theme="theme"
        :categories="categories"
        :related-items="relatedDramas"
        :dramas="dramas"
        :theme-id="theme?.id"
        :theme-name="theme?.name"
    />
</template>

<script setup>
import Layout from "@/Shared/Admin/Layout.vue"
import ThemesForm from "@/InertiaPages/Admin/Shared/Themes/Form.vue"

// Props
const props = defineProps({
    theme: {
        type: Object,
        default: null
    },
    categories: {
        type: Object,
        default: () => ({ main: [], sub: [] })
    },
    relatedDramas: {
        type: Array,
        default: () => []
    },
    dramas: {
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