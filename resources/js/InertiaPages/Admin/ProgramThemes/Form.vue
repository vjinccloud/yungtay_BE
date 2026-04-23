<!-- resources/js/InertiaPages/Admin/ProgramThemes/Form.vue -->
<!-- 簡化版：使用共用的 Themes Form 組件 -->
<template>
    <ThemesForm
        content-type="program"
        :categories="categories"
        :related-items="relatedPrograms"
        :programs="programs"
        :theme-id="themeId"
        :theme-name="themeName"
    />
</template>

<script setup>
import Layout from "@/Shared/Admin/Layout.vue"
import ThemesForm from "@/InertiaPages/Admin/Shared/Themes/Form.vue"

// Props - 對應後端傳遞的資料結構
const props = defineProps({
    themeId: {
        type: [Number, String],
        default: null
    },
    themeName: {
        type: Object,
        default: null
    },
    categories: {
        type: Object,
        default: () => ({ main: [], sub: [] })
    },
    relatedPrograms: {
        type: Array,
        default: () => []
    },
    programs: {
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