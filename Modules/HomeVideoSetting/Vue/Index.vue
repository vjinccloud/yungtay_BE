<!-- Modules/HomeVideoSetting/Vue/Index.vue -->
<!-- 首頁影片管理 - 列表頁 -->
<template>
    <div class="content">
        <BreadcrumbItem />

        <div class="block block-rounded">
            <div class="block-header block-header-default">
                <h3 class="block-title">首頁影片管理</h3>
                <div class="block-options">
                    <!-- 數量提示 -->
                    <span class="badge me-2" :class="props.listCount >= props.maxLimit ? 'bg-danger' : 'bg-secondary'">
                        {{ props.listCount }} / {{ props.maxLimit }}
                    </span>
                    <!-- 排序模式切換 -->
                    <button 
                        type="button"
                        class="btn btn-sm me-2"
                        :class="isSortMode ? 'btn-warning' : 'btn-secondary'"
                        @click="toggleSortMode"
                    >
                        <i class="fa fa-sort me-1"></i>
                        {{ isSortMode ? '完成排序' : '排序模式' }}
                    </button>
                    <Link 
                        v-if="props.listCount < props.maxLimit"
                        :href="route('admin.home-video-settings.add')" 
                        class="btn btn-sm btn-primary"
                    >
                        <i class="fa fa-plus me-1"></i>
                        新增影片
                    </Link>
                    <button 
                        v-else
                        type="button"
                        class="btn btn-sm btn-secondary"
                        disabled
                        title="已達上限 6 筆"
                    >
                        <i class="fa fa-plus me-1"></i>
                        新增影片
                    </button>
                </div>
            </div>

            <div class="block-content block-content-full">
                <!-- 排序提示 -->
                <div v-if="isSortMode" class="alert alert-info mb-3">
                    <i class="fa fa-info-circle me-1"></i>
                    拖曳列表項目進行排序，完成後點擊「完成排序」按鈕儲存。
                </div>

                <!-- 資料表格 -->
                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-vcenter">
                        <thead>
                            <tr>
                                <th style="width: 60px;" class="text-center">
                                    {{ isSortMode ? '拖曳' : '排序' }}
                                </th>
                                <th>標題（中文）</th>
                                <th>標題（英文）</th>
                                <th style="width: 80px;" class="text-center">中文影片</th>
                                <th style="width: 80px;" class="text-center">英文影片</th>
                                <th style="width: 80px;" class="text-center">狀態</th>
                                <th style="width: 120px;" class="text-center">操作</th>
                            </tr>
                        </thead>
                        <tbody ref="sortableTable">
                            <tr v-if="sortedList.length === 0">
                                <td colspan="7" class="text-center text-muted py-4">
                                    尚無資料
                                </td>
                            </tr>
                            <tr 
                                v-for="item in sortedList" 
                                :key="item.id"
                                :data-id="item.id"
                            >
                                <td class="text-center">
                                    <template v-if="isSortMode">
                                        <i class="fa fa-grip-vertical text-muted sort-handle" style="cursor: move;"></i>
                                    </template>
                                    <template v-else>
                                        {{ item.sort }}
                                    </template>
                                </td>
                                <td>{{ item.title_zh }}</td>
                                <td>{{ item.title_en }}</td>
                                <td class="text-center">
                                    <span v-if="item.video_zh" class="badge bg-success">
                                        <i class="fa fa-check"></i>
                                    </span>
                                    <span v-else class="badge bg-secondary">
                                        <i class="fa fa-times"></i>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <span v-if="item.video_en" class="badge bg-success">
                                        <i class="fa fa-check"></i>
                                    </span>
                                    <span v-else class="badge bg-secondary">
                                        <i class="fa fa-times"></i>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <span v-if="item.is_enabled" class="badge bg-success">啟用</span>
                                    <span v-else class="badge bg-secondary">停用</span>
                                </td>
                                <td class="text-center">
                                    <Link 
                                        :href="route('admin.home-video-settings.edit', item.id)"
                                        class="btn btn-sm btn-info me-1"
                                        title="編輯"
                                    >
                                        <i class="fa fa-pencil-alt"></i>
                                    </Link>
                                    <button 
                                        type="button"
                                        class="btn btn-sm btn-danger"
                                        title="刪除"
                                        @click="confirmDelete(item)"
                                    >
                                        <i class="fa fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, inject, onMounted, onBeforeUnmount, computed, nextTick } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import Layout from "@/Shared/Admin/Layout.vue";
import BreadcrumbItem from "@/Shared/Admin/Partials/BreadcrumbItem.vue";
import Sortable from 'sortablejs';
import axios from 'axios';

const props = defineProps({
    list: {
        type: Array,
        default: () => []
    },
    listCount: {
        type: Number,
        default: 0
    },
    maxLimit: {
        type: Number,
        default: 6
    }
});

const sweetAlert = inject('$sweetAlert');

// 排序模式
const isSortMode = ref(false);
const sortableTable = ref(null);
const sortableInstance = ref(null);
const localList = ref([]);

// 排序後的列表
const sortedList = computed(() => {
    if (localList.value.length > 0) {
        return localList.value;
    }
    return [...props.list].sort((a, b) => (a.sort || 0) - (b.sort || 0));
});

// 初始化本地列表
onMounted(() => {
    localList.value = [...props.list].sort((a, b) => (a.sort || 0) - (b.sort || 0));
});

// 切換排序模式
const toggleSortMode = async () => {
    if (isSortMode.value) {
        // 儲存排序
        await saveSort();
        destroySortable();
        isSortMode.value = false;
    } else {
        isSortMode.value = true;
        await nextTick();
        initSortable();
    }
};

// 初始化拖曳排序
const initSortable = () => {
    if (sortableInstance.value) {
        destroySortable();
    }

    if (sortableTable.value) {
        sortableInstance.value = Sortable.create(sortableTable.value, {
            animation: 150,
            handle: '.sort-handle',
            ghostClass: 'sortable-ghost',
            chosenClass: 'sortable-chosen',
            dragClass: 'sortable-drag',
            onEnd: (evt) => {
                // 更新本地列表順序
                const newList = [...localList.value];
                const movedItem = newList.splice(evt.oldIndex, 1)[0];
                newList.splice(evt.newIndex, 0, movedItem);
                localList.value = newList;
            }
        });
    }
};

// 銷毀排序實例
const destroySortable = () => {
    if (sortableInstance.value) {
        try {
            sortableInstance.value.destroy();
        } catch (e) {
            console.warn('銷毀 Sortable 實例時發生錯誤:', e);
        }
        sortableInstance.value = null;
    }
};

// 儲存排序
const saveSort = async () => {
    const items = localList.value.map((item, index) => ({
        id: item.id,
        sort: index + 1
    }));

    try {
        const response = await axios.post(route('admin.home-video-settings.sort'), { items });
        
        if (response.data.status) {
            sweetAlert.success({ msg: '排序已儲存' });
            // 更新本地列表的 sort 值
            localList.value = localList.value.map((item, index) => ({
                ...item,
                sort: index + 1
            }));
        } else {
            sweetAlert.error({ msg: response.data.msg || '排序儲存失敗' });
        }
    } catch (error) {
        console.error('儲存排序失敗:', error);
        sweetAlert.error({ msg: '排序儲存失敗' });
    }
};

// 確認刪除
const confirmDelete = (item) => {
    sweetAlert.confirm(`確定要刪除「${item.title_zh}」嗎？`, () => {
        router.delete(route('admin.home-video-settings.destroy', item.id), {
            preserveScroll: true,
            onSuccess: () => {
                sweetAlert.success({ msg: '刪除成功' });
                // 從本地列表移除
                localList.value = localList.value.filter(i => i.id !== item.id);
            },
            onError: () => {
                sweetAlert.error({ msg: '刪除失敗' });
            }
        });
    });
};

// 組件卸載時清理
onBeforeUnmount(() => {
    destroySortable();
});

defineOptions({
    layout: Layout
});
</script>

<style scoped>
.sort-handle {
    cursor: move;
}

.sortable-ghost {
    opacity: 0.4;
    background-color: #c8ebfb;
}

.sortable-chosen {
    background-color: #f0f9ff;
}

.sortable-drag {
    background-color: #fff;
    box-shadow: 0 2px 10px rgba(0,0,0,0.15);
}
</style>
