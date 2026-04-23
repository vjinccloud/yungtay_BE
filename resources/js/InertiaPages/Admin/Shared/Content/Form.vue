<!-- resources/js/InertiaPages/Admin/Shared/Content/Form.vue -->
<!-- 共用的內容表單頁面組件（影音/節目） -->
<template>
    <div class="content">
        <BreadcrumbItem />
        <div class="block block-rounded" :data-content-form="contentType">
            <div class="block-header block-header-default">
                <h3 class="block-title">
                    <Link class="btn btn-sm btn-alt-secondary" :href="getBackRoute()">
                        <i class="fa fa-arrow-left me-1"></i>
                        返回列表
                    </Link>
                </h3>
            </div>

            <div class="block-content block-content-full">
                <!-- 選項卡導航 -->
                <ul class="nav nav-tabs nav-tabs-block" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button
                            class="nav-link"
                            :class="{ active: activeTab === 'basic' }"
                            @click="setActiveTab('basic')"
                            type="button"
                        >
                            <i class="fa fa-info-circle me-1"></i>
                            基本設定
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button
                            class="nav-link"
                            :class="{ active: activeTab === 'video' }"
                            @click="setActiveTab('video')"
                            type="button"
                        >
                            <i class="fa fa-play me-1"></i>
                            集數管理
                        </button>
                    </li>
                </ul>

                <!-- 選項卡內容 -->
                <div class="tab-content">
                    <!-- 基本設定選項卡 -->
                    <div
                        class="tab-pane"
                        :class="{ active: activeTab === 'basic' }"
                    >
                        <component
                            :content-type="contentType"
                            :is="basicInfoComponent"
                            :categories="categories"
                            :subcategories="subcategories"
                            :initial-data="contentData"
                            :video-seasons="videoSeasons"
                            :is-editing="isEditing"
                            @season-change="handleSeasonChange"
                            @back="back"
                            ref="basicInfoRef"
                        />
                    </div>

                    <!-- 集數管理選項卡 -->
                    <div
                        class="tab-pane"
                        :class="{ active: activeTab === 'video' }"
                    >
                        <component
                            :is="videoUploadComponent"
                            v-bind="videoUploadProps"
                            @update:videoData="handleVideoDataUpdate"
                            @video-change="handleVideoChange"
                            ref="videoUploadRef"
                        />
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, reactive, watch } from 'vue'
import { Link } from '@inertiajs/vue3'
import BreadcrumbItem from "@/Shared/Admin/Partials/BreadcrumbItem.vue"

// 導入共用組件
import BaseBasicInfo from "@/InertiaPages/Admin/Shared/Content/Components/BaseBasicInfo.vue"
import BaseVideoUpload from "@/InertiaPages/Admin/Shared/Content/Components/BaseVideoUpload.vue"

// Props
const props = defineProps({
    contentType: {
        type: String,
        required: true,
        validator: (value) => ['drama', 'program'].includes(value)
    },
    contentData: {
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

// 判斷是否為編輯模式
const isEditing = computed(() => !!props.contentData?.id)

// 直接使用共用組件，不再透過包裝層
// 注意：這會讓未來模組特有欄位較不方便擴展
const basicInfoComponent = computed(() => BaseBasicInfo)
const videoUploadComponent = computed(() => BaseVideoUpload)

const pageTitle = computed(() => {
    const titles = {
        drama: isEditing.value ? '編輯影音' : '新增影音',
        program: isEditing.value ? '編輯節目' : '新增節目'
    }
    return titles[props.contentType]
})

const getBackRoute = () => {
    const routes = {
        drama: route('admin.dramas'),
        program: route('admin.programs')
    }
    return routes[props.contentType]
}

// 選項卡控制
const activeTab = ref('basic')

// 組件引用
const basicInfoRef = ref(null)
const videoUploadRef = ref(null)

// 當前季數追蹤（從基本設定組件傳來）
const currentSeasonNumber = ref(props.contentData?.season_number || 1)

// 影片資料 - 轉換為陣列格式以符合組件期望
const videoDataObject = reactive({
    1: props.contentData?.videos?.['1'] || [],
    2: props.contentData?.videos?.['2'] || [],
    // 根據需要添加更多季數
    ...props.contentData?.videos || {}
})

// 將物件格式轉換為陣列格式供組件使用
const videoData = computed(() => {
    // 將所有季的影片合併成一個陣列
    const allVideos = []
    Object.keys(videoDataObject).forEach(season => {
        const seasonVideos = videoDataObject[season] || []
        seasonVideos.forEach(video => {
            allVideos.push({
                ...video,
                season: parseInt(season)
            })
        })
    })
    return allVideos
})

// 動態設定 video upload 的 props（統一 BaseVideoUpload）
const videoUploadProps = computed(() => {
    return {
        contentType: props.contentType,
        contentId: props.contentData?.id || null,
        maxSeasons: currentSeasonNumber.value,
        videoData: videoData.value
    }
})

// 方法
const setActiveTab = (tab) => {
    console.log('切換到選項卡:', tab)
    activeTab.value = tab
}

const back = () => {
    window.history.back()
}

// 處理季數變更
const handleSeasonChange = (newSeasonNumber) => {
    console.log('季數變更:', newSeasonNumber)
    currentSeasonNumber.value = newSeasonNumber

    // 通知影片上傳組件更新
    if (videoUploadRef.value) {
        // 這裡可以添加更多邏輯
    }
}

// 處理影片資料更新
const handleVideoDataUpdate = (newVideoData) => {
    console.log('影片資料更新:', newVideoData)
    // 將陣列格式轉換回物件格式
    const newVideoDataObject = {}
    newVideoData.forEach(video => {
        const season = video.season || 1
        if (!newVideoDataObject[season]) {
            newVideoDataObject[season] = []
        }
        newVideoDataObject[season].push(video)
    })

    // 更新 reactive 物件
    Object.keys(videoDataObject).forEach(key => {
        delete videoDataObject[key]
    })
    Object.assign(videoDataObject, newVideoDataObject)
}

// 處理影片變更
const handleVideoChange = (data) => {
    console.log('影片變更事件:', data)

    // 更新影片季數列表
    const seasons = new Set()
    Object.keys(videoDataObject).forEach(season => {
        if (videoDataObject[season]?.length > 0) {
            seasons.add(parseInt(season))
        }
    })

    // 這裡可以更新 videoSeasons 或執行其他邏輯
}

// 暴露方法給父組件
defineExpose({
    activeTab,
    setActiveTab,
    basicInfoRef,
    videoUploadRef
})
</script>

<style scoped>
/* 共用樣式 */
.nav-tabs-block {
    background-color: #f5f5f5;
    border-bottom: 2px solid #dee2e6;
}

.nav-tabs-block .nav-link {
    color: #495057;
    border: none;
    border-radius: 0;
    padding: 0.75rem 1.25rem;
}

.nav-tabs-block .nav-link.active {
    background-color: #fff;
    color: #0665d0;
    border-bottom: 2px solid #0665d0;
}

.tab-content {
    padding-top: 1.5rem;
}
</style>
