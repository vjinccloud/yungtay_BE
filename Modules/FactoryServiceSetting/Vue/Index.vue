<!-- Modules/FactoryServiceSetting/Vue/Index.vue -->
<!-- 工廠服務設定 - 矩陣頁面 -->
<template>
    <div class="content">
        <BreadcrumbItem />

        <div class="block block-rounded">
            <div class="block-header block-header-default">
                <h3 class="block-title">工廠服務設定</h3>
                <div class="block-options">
                    <span class="text-muted me-2">點擊格子即時更新</span>
                </div>
            </div>

            <div class="block-content block-content-full">
                <div class="table-responsive matrix-container">
                    <table class="table table-bordered table-sm matrix-table">
                        <thead class="table-light sticky-header">
                            <tr>
                                <th class="sticky-col sticky-header-col bg-light" style="min-width: 150px;">據點 / 工廠</th>
                                <th 
                                    v-for="service in productServices" 
                                    :key="service.id"
                                    class="text-center"
                                    style="min-width: 80px; font-size: 12px;"
                                >
                                    {{ service.name }}
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <template v-for="region in regions" :key="region.id">
                                <!-- 據點標題行 -->
                                <tr class="table-secondary">
                                    <td colspan="100%" class="fw-bold">
                                        <i class="fa fa-globe me-1"></i>
                                        {{ region.name }} ({{ region.factories.length }})
                                    </td>
                                </tr>
                                <!-- 工廠行 -->
                                <tr v-for="factory in region.factories" :key="factory.id">
                                    <td class="sticky-col bg-white">
                                        <small>{{ factory.name }}</small>
                                    </td>
                                    <td 
                                        v-for="service in productServices" 
                                        :key="`${factory.id}-${service.id}`"
                                        class="text-center p-0"
                                        style="cursor: pointer;"
                                        @click="toggleRelation(factory.id, service.id)"
                                    >
                                        <div 
                                            class="check-cell"
                                            :class="{ 'checked': isChecked(factory.id, service.id) }"
                                        >
                                            <i v-if="isChecked(factory.id, service.id)" class="fa fa-check text-success"></i>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, inject } from 'vue'
import { Link } from '@inertiajs/vue3'
import Layout from "@/Shared/Admin/Layout.vue";
import BreadcrumbItem from "@/Shared/Admin/Partials/BreadcrumbItem.vue";
import axios from 'axios';

// 使用全域 Loading
const isLoading = inject('isLoading');

const props = defineProps({
    regions: {
        type: Array,
        default: () => []
    },
    productServices: {
        type: Array,
        default: () => []
    },
    relations: {
        type: Array,
        default: () => []
    }
});

const sweetAlert = inject('$sweetAlert');

// 關聯資料（響應式）
const localRelations = ref([...props.relations]);

// 檢查是否有關聯
const isChecked = (factoryId, productServiceId) => {
    return localRelations.value.some(
        r => r.factory_id === factoryId && r.product_service_id === productServiceId
    );
};

// 切換關聯
const toggleRelation = async (factoryId, productServiceId) => {
    // 如果正在 loading，不處理
    if (isLoading.value) {
        return;
    }
    
    // 設定全域 loading 狀態
    isLoading.value = true;
    
    try {
        const response = await axios.post(route('admin.factory-service-settings.toggle'), {
            factory_id: factoryId,
            product_service_id: productServiceId,
        });

        if (response.data.status) {
            if (response.data.checked) {
                // 新增關聯
                localRelations.value.push({
                    factory_id: factoryId,
                    product_service_id: productServiceId,
                });
            } else {
                // 移除關聯
                localRelations.value = localRelations.value.filter(
                    r => !(r.factory_id === factoryId && r.product_service_id === productServiceId)
                );
            }
        } else {
            sweetAlert.error({ msg: response.data.msg || '操作失敗' });
        }
    } catch (error) {
        console.error('Toggle error:', error);
        sweetAlert.error({ msg: '操作失敗' });
    } finally {
        // 移除全域 loading 狀態
        isLoading.value = false;
    }
};

defineOptions({
    layout: Layout
});
</script>

<style scoped>
.matrix-table {
    font-size: 13px;
}

.matrix-container {
    max-height: calc(100vh - 250px);
    overflow: auto;
}

.sticky-header {
    position: sticky;
    top: 0;
    z-index: 10;
}

.sticky-header th {
    background-color: #f8f9fa !important;
}

.sticky-col {
    position: sticky;
    left: 0;
    z-index: 5;
}

.sticky-header-col {
    z-index: 15 !important;
}

.check-cell {
    width: 100%;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: background-color 0.2s;
}

.check-cell:hover {
    background-color: #e9ecef;
}

.check-cell.checked {
    background-color: #d4edda;
}

.check-cell.checked:hover {
    background-color: #c3e6cb;
}
</style>
