<!-- 選單樹狀節點（遞迴元件） -->
<template>
    <div class="menu-tree-node">
        <div 
            class="d-flex align-items-center menu-tree-item"
            :class="{ 'menu-tree-item--selected': selectedId === menu.id }"
            @click="$emit('select', menu)"
        >
            <!-- 展開/收合 -->
            <span 
                v-if="menu.children && menu.children.length > 0" 
                class="menu-tree-toggle"
                @click.stop="expanded = !expanded"
            >
                <i :class="expanded ? 'fa fa-caret-down' : 'fa fa-caret-right'"></i>
            </span>
            <span v-else class="menu-tree-toggle-placeholder"></span>

            <!-- 圖標 -->
            <span class="menu-tree-icon">
                <i v-if="menu.icon_image" :class="menu.icon_image" class="text-primary"></i>
                <i v-else class="fa fa-circle" style="font-size: 5px; color: #bbb;"></i>
            </span>

            <!-- 名稱 -->
            <span class="menu-tree-title flex-grow-1">
                <strong>{{ menu.title }}</strong>
                <small v-if="menu.url" class="text-muted ms-1">({{ menu.url }})</small>
            </span>

            <!-- 狀態 Badge -->
            <span 
                class="badge me-1" 
                :class="menu.status ? 'bg-success' : 'bg-danger'"
                style="cursor: pointer; font-size: 10px; min-width: 24px;"
                @click.stop="$emit('toggle-status', menu)"
            >
                {{ menu.status ? '啟' : '停' }}
            </span>

            <!-- 類型 Badge -->
            <span class="badge me-1" :class="menu.type === 1 ? 'bg-info' : 'bg-secondary'" style="font-size: 10px; min-width: 30px;">
                {{ menu.type === 1 ? '顯示' : '隱藏' }}
            </span>

            <!-- 刪除 -->
            <button 
                class="btn btn-sm p-0 menu-tree-delete" 
                @click.stop="$emit('delete', menu)"
                title="刪除"
            >
                <i class="fa fa-times"></i>
            </button>
        </div>

        <!-- 子節點 -->
        <div v-if="expanded && menu.children && menu.children.length > 0" class="menu-tree-children">
            <MenuTreeNode 
                v-for="child in menu.children" 
                :key="child.id"
                :menu="child"
                :selected-id="selectedId"
                @select="(m) => $emit('select', m)"
                @toggle-status="(m) => $emit('toggle-status', m)"
                @delete="(m) => $emit('delete', m)"
            />
        </div>
    </div>
</template>

<script>
import { ref, defineComponent } from 'vue';

export default defineComponent({
    name: 'MenuTreeNode',
    props: {
        menu: { type: Object, required: true },
        selectedId: { type: [Number, null], default: null },
    },
    emits: ['select', 'toggle-status', 'delete'],
    setup() {
        const expanded = ref(true);
        return { expanded };
    },
});
</script>

<style scoped>
.menu-tree-node {
    font-size: 13px;
}
.menu-tree-item {
    padding: 5px 8px;
    margin: 1px 0;
    border-radius: 4px;
    cursor: pointer;
    transition: background-color 0.15s;
    border: 1px solid transparent;
}
.menu-tree-item:hover {
    background-color: #f0f4f8;
    border-color: #e2e8f0;
}
.menu-tree-item--selected {
    background-color: #dbeafe !important;
    border-color: #93c5fd !important;
}
.menu-tree-toggle {
    width: 20px;
    text-align: center;
    cursor: pointer;
    color: #999;
    flex-shrink: 0;
}
.menu-tree-toggle-placeholder {
    width: 20px;
    flex-shrink: 0;
}
.menu-tree-icon {
    width: 20px;
    text-align: center;
    flex-shrink: 0;
    margin-right: 6px;
}
.menu-tree-title {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    min-width: 0;
}
.menu-tree-delete {
    color: #dc3545;
    opacity: 0.3;
    font-size: 11px;
    line-height: 1;
    flex-shrink: 0;
    width: 20px;
}
.menu-tree-delete:hover {
    opacity: 1;
}
.menu-tree-children {
    padding-left: 20px;
    border-left: 1px dashed #dee2e6;
    margin-left: 9px;
}
</style>
