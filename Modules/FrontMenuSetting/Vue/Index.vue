<!-- Modules/FrontMenuSetting/Vue/Index.vue -->
<!-- 前台選單管理 - 列表頁（WordPress 風格拖曳排序） -->
<template>
    <div class="content">
        <BreadcrumbItem />

        <div class="block block-rounded">
            <div class="block-header block-header-default">
                <h3 class="block-title">前台選單管理</h3>
                <div class="block-options">
                    <button
                        v-if="hasChanges"
                        class="btn btn-sm btn-success me-2"
                        @click="saveSort"
                        :disabled="isSaving"
                    >
                        <i class="fa fa-save me-1"></i>
                        {{ isSaving ? '儲存中...' : '儲存排序' }}
                    </button>
                    <button
                        v-if="hasChanges"
                        class="btn btn-sm btn-outline-secondary me-2"
                        @click="resetSort"
                    >
                        <i class="fa fa-undo me-1"></i>
                        還原
                    </button>
                    <Link 
                        :href="route('admin.front-menu-settings.add')" 
                        class="btn btn-sm btn-primary"
                    >
                        <i class="fa fa-plus me-1"></i>
                        新增選單
                    </Link>
                </div>
            </div>

            <div class="block-content block-content-full">
                <!-- 提示 -->
                <div class="mb-3">
                    <div class="text-muted small">
                        <i class="fa fa-info-circle me-1"></i>
                        上下拖曳調整順序，左右拖曳調整層級
                    </div>
                </div>

                <!-- WordPress 風格排序檢視 -->
                <div class="wp-menu-view">
                    <div v-if="flatList.length === 0" class="text-center text-muted py-4">
                        <i class="fa fa-info-circle me-1"></i>
                        尚無選單資料，請點擊「新增選單」開始建立
                    </div>
                    <div v-else class="wp-menu-container" ref="menuContainer">
                        <div
                            v-for="(item, index) in flatList"
                            :key="item.id"
                            class="wp-menu-item"
                            :class="{
                                'is-dragging': dragIndex === index,
                                'drag-over-top': dropTarget === index && dropPosition === 'top',
                                'drag-over-bottom': dropTarget === index && dropPosition === 'bottom',
                                'is-disabled': !item.status
                            }"
                            :data-index="index"
                            draggable="true"
                            @dragstart="onDragStart($event, index)"
                            @dragover="onDragOver($event, index)"
                            @dragleave="onDragLeave"
                            @drop="onDrop($event, index)"
                            @dragend="onDragEnd"
                        >
                            <!-- 縮排區域（層級指示） -->
                            <div class="wp-menu-indent" :style="{ width: item.depth * 40 + 'px' }">
                                <span v-for="d in item.depth" :key="d" class="indent-dash">—</span>
                            </div>

                            <!-- 卡片主體 -->
                            <div class="wp-menu-card">
                                <div class="wp-menu-drag-handle">
                                    <i class="fa fa-grip-vertical"></i>
                                </div>
                                <div class="wp-menu-info">
                                    <div class="wp-menu-title">
                                        <i v-if="item.icon" :class="item.icon + ' me-1 text-primary'" style="font-size:0.85em;"></i>
                                        <strong>{{ item.title_primary }}</strong>
                                    </div>
                                    <div class="wp-menu-meta">
                                        <span :class="item.status ? 'badge bg-success' : 'badge bg-secondary'">
                                            {{ item.status ? '啟用' : '停用' }}
                                        </span>
                                        <span :class="'badge ms-1 ' + linkTypeClass(item.link_type)">
                                            {{ linkTypeLabel(item.link_type) }}
                                        </span>
                                        <span v-if="item.depth > 0" class="badge bg-light text-dark ms-1">
                                            第 {{ item.depth + 1 }} 層
                                        </span>
                                        <span v-if="item.link_url" class="text-muted ms-1 small text-truncate" style="max-width:180px; display:inline-block; vertical-align:middle;">
                                            {{ item.link_url }}
                                        </span>
                                    </div>
                                </div>
                                <div class="wp-menu-actions">
                                    <button
                                        class="btn btn-sm btn-outline-primary"
                                        title="編輯"
                                        @click="editItem(item.id)"
                                    >
                                        <i class="fa fa-pencil-alt"></i>
                                    </button>
                                    <button
                                        class="btn btn-sm btn-outline-danger"
                                        title="刪除"
                                        @click="deleteItem(item)"
                                    >
                                        <i class="fa fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- 拖曳時的層級調整提示 -->
                        <div v-if="isDragging" class="depth-hint text-center text-muted small mt-2">
                            <i class="fa fa-arrows-alt-h me-1"></i>
                            拖曳時左右移動可調整層級
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</template>

<script>
import { ref, computed, onMounted, inject, watch } from "vue";
import { Link, router } from "@inertiajs/vue3";
import Layout from "@/Shared/Admin/Layout.vue";
import BreadcrumbItem from "@/Shared/Admin/Partials/BreadcrumbItem.vue";

export default {
    components: { BreadcrumbItem, Link },
    props: {
        tree: { type: Array, default: () => [] },
    },
    setup(props) {
        const sweetAlert = inject('$sweetAlert');
        const menuContainer = ref(null);

        // ========= 扁平化樹 =========
        const flattenTreeToList = (nodes, depth = 0) => {
            const result = [];
            for (const node of nodes) {
                result.push({
                    id: node.id,
                    title_primary: node.title_primary || node.title_zh || node.title,
                    icon: node.icon,
                    link_type: node.link_type,
                    link_url: node.link_url,
                    status: node.status,
                    depth: depth,
                });
                if (node.children && node.children.length) {
                    result.push(...flattenTreeToList(node.children, depth + 1));
                }
            }
            return result;
        };

        const flatList = ref(flattenTreeToList(props.tree || []));
        const originalFlat = ref(JSON.stringify(flatList.value));
        const hasChanges = ref(false);
        const isSaving = ref(false);

        // ========= 拖曳狀態 =========
        const isDragging = ref(false);
        const dragIndex = ref(null);
        const dropTarget = ref(null);
        const dropPosition = ref(null); // 'top' | 'bottom'
        const dragStartX = ref(0);

        const onDragStart = (e, index) => {
            isDragging.value = true;
            dragIndex.value = index;
            dragStartX.value = e.clientX;
            e.dataTransfer.effectAllowed = 'move';
            // 需要設一個 data 否則 Firefox 不會觸發 drag
            e.dataTransfer.setData('text/plain', index.toString());
        };

        const onDragOver = (e, index) => {
            e.preventDefault();
            e.dataTransfer.dropEffect = 'move';

            if (dragIndex.value === null || dragIndex.value === index) {
                dropTarget.value = null;
                return;
            }

            // 判斷要插入上方還是下方
            const rect = e.currentTarget.getBoundingClientRect();
            const midY = rect.top + rect.height / 2;
            dropPosition.value = e.clientY < midY ? 'top' : 'bottom';
            dropTarget.value = index;
        };

        const onDragLeave = (e) => {
            // 只有真正離開元素時才清除
            if (!e.currentTarget.contains(e.relatedTarget)) {
                dropTarget.value = null;
                dropPosition.value = null;
            }
        };

        const onDrop = (e, targetIndex) => {
            e.preventDefault();
            if (dragIndex.value === null || dragIndex.value === targetIndex) return;

            const sourceIndex = dragIndex.value;

            // 計算水平偏移量來決定層級變更
            const deltaX = e.clientX - dragStartX.value;
            const depthChange = Math.round(deltaX / 40); // 每 40px 一個層級

            // 取出被拖曳的項目
            const draggedItem = { ...flatList.value[sourceIndex] };

            // 計算新層級
            let insertIndex = dropPosition.value === 'top' ? targetIndex : targetIndex + 1;
            if (sourceIndex < insertIndex) insertIndex--; // 因為移除後 index 會偏移

            // 先移除
            flatList.value.splice(sourceIndex, 1);

            // 算出合理的新 depth
            let newDepth = draggedItem.depth + depthChange;

            // 限制：新位置前一個項目的 depth + 1 為最大深度
            const prevIndex = insertIndex > 0 ? insertIndex - 1 : -1;
            const maxDepth = prevIndex >= 0 ? flatList.value[prevIndex].depth + 1 : 0;
            newDepth = Math.max(0, Math.min(newDepth, maxDepth));

            draggedItem.depth = newDepth;

            // 插入新位置
            flatList.value.splice(insertIndex, 0, draggedItem);

            // 修正被拖曳項目原來的子孫的層級（如果有的話）
            // 簡化：不追蹤子孫移動，只移動單一項目

            hasChanges.value = true;

            // 清除拖曳狀態
            resetDragState();
        };

        const onDragEnd = () => {
            resetDragState();
        };

        const resetDragState = () => {
            isDragging.value = false;
            dragIndex.value = null;
            dropTarget.value = null;
            dropPosition.value = null;
        };

        // ========= 將扁平列表轉回排序資料 =========
        const flatListToSortData = () => {
            // 根據 depth 重建 parent 關係
            const result = [];
            const parentStack = [{ id: 0, depth: -1 }]; // 虛擬根節點

            for (let i = 0; i < flatList.value.length; i++) {
                const item = flatList.value[i];

                // 回溯到正確的父層
                while (parentStack.length > 1 && parentStack[parentStack.length - 1].depth >= item.depth) {
                    parentStack.pop();
                }

                const parentId = parentStack[parentStack.length - 1].id;

                // 計算同層的 seq
                const sameLevelCount = result.filter(r => r.parent_id === parentId).length;

                result.push({
                    id: item.id,
                    parent_id: parentId,
                    seq: sameLevelCount,
                });

                parentStack.push({ id: item.id, depth: item.depth });
            }

            return result;
        };

        // ========= 儲存排序 =========
        const saveSort = () => {
            isSaving.value = true;
            const items = flatListToSortData();

            axios.post(route('admin.front-menu-settings.sort'), { items })
                .then(res => {
                    if (res.data.status) {
                        sweetAlert.success({ msg: res.data.msg || '排序更新成功' });
                        hasChanges.value = false;
                        originalFlat.value = JSON.stringify(flatList.value);
                        router.reload({ only: ['tree', 'list'] });
                    }
                })
                .catch(() => {
                    sweetAlert.error({ msg: '排序更新失敗' });
                })
                .finally(() => {
                    isSaving.value = false;
                });
        };

        const resetSort = () => {
            flatList.value = JSON.parse(originalFlat.value);
            hasChanges.value = false;
        };

        // ========= 工具函式 =========
        const linkTypeClass = (type) => {
            return { url: 'bg-info', route: 'bg-primary', page: 'bg-warning', none: 'bg-secondary' }[type] || 'bg-secondary';
        };
        const linkTypeLabel = (type) => {
            return { url: '外部連結', route: '內部路由', page: '頁面', none: '無連結' }[type] || '無連結';
        };

        // ========= CRUD 操作 =========
        const editItem = (id) => {
            router.get(route('admin.front-menu-settings.edit', id));
        };

        const toggleItem = (id) => {
            axios.put(route('admin.front-menu-settings.toggle-active'), { id })
                .then(res => {
                    if (res.data.status) {
                        sweetAlert.success({ msg: res.data.msg });
                        router.reload({ only: ['tree', 'list'] });
                    }
                })
                .catch(() => { sweetAlert.error({ msg: '操作失敗' }); });
        };

        const deleteItem = (node) => {
            axios.get(route('admin.api.front-menu-settings.delete-info', node.id))
                .then(res => {
                    const info = res.data;
                    let msg = `確定要刪除「${info.title}」嗎？`;
                    if (info.has_children) {
                        msg = `「${info.title}」底下還有 ${info.descendant_count} 個子選單，刪除後一併移除：\n${info.descendant_titles.join('、')}\n\n確定要刪除嗎？`;
                    }
                    sweetAlert.confirm(msg, () => {
                        router.delete(route('admin.front-menu-settings.destroy', node.id), {
                            preserveScroll: true,
                            onSuccess: () => { sweetAlert.success({ msg: '刪除成功' }); },
                            onError: () => { sweetAlert.error({ msg: '刪除失敗' }); }
                        });
                    });
                })
                .catch(() => {
                    sweetAlert.confirm(`確定要刪除「${node.title_primary}」嗎？`, () => {
                        router.delete(route('admin.front-menu-settings.destroy', node.id), {
                            preserveScroll: true,
                            onSuccess: () => { sweetAlert.success({ msg: '刪除成功' }); },
                        });
                    });
                });
        };

        // 當 tree prop 更新時同步
        watch(() => props.tree, (newTree) => {
            flatList.value = flattenTreeToList(newTree || []);
            originalFlat.value = JSON.stringify(flatList.value);
            hasChanges.value = false;
        }, { deep: true });

        return {
            menuContainer,
            flatList, hasChanges, isSaving, isDragging,
            dragIndex, dropTarget, dropPosition,
            onDragStart, onDragOver, onDragLeave, onDrop, onDragEnd,
            editItem, deleteItem, toggleItem,
            saveSort, resetSort,
            linkTypeClass, linkTypeLabel,
        };
    },
    layout: Layout,
};
</script>

<style scoped>
@import 'bootstrap';

/* ===== WordPress 風格選單排序 ===== */
.wp-menu-container {
    border: 1px solid #e4e7ed;
    border-radius: 8px;
    padding: 8px;
    background: #f8f9fa;
}

.wp-menu-item {
    display: flex;
    align-items: stretch;
    margin-bottom: 4px;
    transition: transform 0.1s ease;
    position: relative;
}

.wp-menu-item.is-dragging {
    opacity: 0.4;
}

.wp-menu-item.drag-over-top::before {
    content: '';
    position: absolute;
    top: -3px;
    left: 0;
    right: 0;
    height: 3px;
    background: #0d6efd;
    border-radius: 2px;
    z-index: 10;
}

.wp-menu-item.drag-over-bottom::after {
    content: '';
    position: absolute;
    bottom: -3px;
    left: 0;
    right: 0;
    height: 3px;
    background: #0d6efd;
    border-radius: 2px;
    z-index: 10;
}

/* 縮排區 */
.wp-menu-indent {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    flex-shrink: 0;
    padding-right: 4px;
    min-height: 100%;
}

.indent-dash {
    color: #ced4da;
    font-size: 0.75em;
    margin-right: 2px;
    user-select: none;
}

/* 卡片 */
.wp-menu-card {
    display: flex;
    align-items: center;
    flex-grow: 1;
    background: #fff;
    border: 1px solid #dee2e6;
    border-radius: 6px;
    padding: 8px 12px;
    transition: border-color 0.15s, box-shadow 0.15s;
    gap: 12px;
    min-height: 52px;
}

.wp-menu-card:hover {
    border-color: #adb5bd;
    box-shadow: 0 1px 4px rgba(0,0,0,0.06);
}

.wp-menu-item.is-disabled .wp-menu-card {
    opacity: 0.55;
}

/* 拖曳把手 */
.wp-menu-drag-handle {
    cursor: grab;
    color: #adb5bd;
    font-size: 1rem;
    padding: 4px;
    flex-shrink: 0;
    transition: color 0.15s;
}

.wp-menu-drag-handle:hover {
    color: #495057;
}

.wp-menu-item.is-dragging .wp-menu-drag-handle {
    cursor: grabbing;
}

/* 資訊區 */
.wp-menu-info {
    flex-grow: 1;
    min-width: 0;
}

.wp-menu-title {
    font-size: 0.9rem;
    line-height: 1.3;
    margin-bottom: 2px;
}

.wp-menu-meta {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 2px;
}

.wp-menu-meta .badge {
    font-size: 0.7em;
    font-weight: 500;
}

/* 操作按鈕 */
.wp-menu-actions {
    display: flex;
    gap: 4px;
    opacity: 0;
    transition: opacity 0.15s;
    flex-shrink: 0;
}

.wp-menu-card:hover .wp-menu-actions {
    opacity: 1;
}

.wp-menu-actions .btn {
    padding: 2px 6px;
    font-size: 0.8rem;
}

/* 層級提示 */
.depth-hint {
    padding: 8px;
    background: #e8f4fd;
    border-radius: 6px;
    animation: fadeIn 0.3s ease;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}
</style>
