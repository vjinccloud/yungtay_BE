<!-- resources/js/InertiaPages/Admin/RadioThemes/Form.vue -->
<!-- 簡化版：使用共用的 Themes Form 組件 -->
<template>
    <ThemesForm
        content-type="radio"
        :theme="theme"
        :categories="categories"
        :related-items="relatedRadios"
        :radios="radios"
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
    relatedRadios: {
        type: Array,
        default: () => []
    },
    radios: {
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