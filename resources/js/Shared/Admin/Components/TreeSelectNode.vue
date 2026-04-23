<!-- resources/js/Shared/Admin/Components/TreeSelectNode.vue -->
<!-- 遞迴樹節點 — 用於 TreeSelect -->
<template>
    <div>
        <div
            class="tree-select-item d-flex align-items-center px-3 py-1"
            :style="{ paddingLeft: (depth * 18 + 12) + 'px' }"
            :class="{
                'fw-bold text-primary': String(selectedId) === String(node.id),
            }"
            @click.stop="handleClick"
        >
            <!-- 展開收合箭頭 -->
            <span
                v-if="node.children && node.children.length"
                class="me-1"
                style="width:16px; display:inline-block; cursor:pointer;"
                @click.stop="expanded = !expanded"
            >
                <i :class="expanded ? 'fa fa-caret-down' : 'fa fa-caret-right'"></i>
            </span>
            <span v-else style="width:16px; display:inline-block;"></span>

            <span class="text-truncate">{{ node.label }}</span>
        </div>

        <template v-if="expanded && node.children && node.children.length">
            <TreeSelectNode
                v-for="child in node.children"
                :key="child.id"
                :node="child"
                :selected-id="selectedId"
                :depth="depth + 1"
                @select="$emit('select', $event)"
            />
        </template>
    </div>
</template>

<script>
import { ref } from 'vue';

export default {
    name: 'TreeSelectNode',
    props: {
        node:       { type: Object, required: true },
        selectedId: { type: [String, Number], default: '' },
        depth:      { type: Number, default: 0 },
    },
    emits: ['select'],
    setup(props, { emit }) {
        const expanded = ref(props.depth < 1); // 預設展開第一層

        const handleClick = () => {
            emit('select', props.node.id);
        };

        return { expanded, handleClick };
    },
};
</script>

<style scoped>
.tree-select-item {
    cursor: pointer;
    font-size: 0.85rem;
    border-bottom: 1px solid #f0f0f0;
}
.tree-select-item:hover {
    background: #f5f7fa;
}
</style>
