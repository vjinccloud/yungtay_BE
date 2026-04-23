<!-- resources/js/InertiaPages/Admin/RadioThemes/Index.vue -->
<!-- 簡化版：使用共用的 Themes Index 組件 -->
<template>
    <ThemesIndex content-type="radio" />
</template>

<script setup>
import Layout from "@/Shared/Admin/Layout.vue"
import ThemesIndex from "@/InertiaPages/Admin/Shared/Themes/Index.vue"
</script>

<script>
export default {
    layout: Layout,
};
</script>