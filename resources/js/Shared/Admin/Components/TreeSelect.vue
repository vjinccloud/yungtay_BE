<!-- resources/js/Shared/Admin/Components/TreeSelect.vue -->
<!-- 樹狀下拉單選元件 — 用於篩選欄位 -->
<template>
    <div class="tree-select" ref="wrapperRef">
        <button
            type="button"
            class="form-select form-select-sm text-start"
            @click="open = !open"
        >
            <span :class="{ 'text-muted': !selectedLabel }">
                {{ selectedLabel || placeholder }}
            </span>
        </button>

        <div v-if="open" class="tree-select-dropdown border rounded shadow-sm">
            <!-- 全部選項 -->
            <div
                class="tree-select-item px-3 py-1"
                :class="{ 'fw-bold text-primary': !modelValue }"
                @click="selectItem('')"
            >
                全部
            </div>

            <TreeSelectNode
                v-for="node in nodes"
                :key="node.id"
                :node="node"
                :selected-id="modelValue"
                :depth="0"
                @select="selectItem"
            />
        </div>
    </div>
</template>

<script>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue';
import TreeSelectNode from './TreeSelectNode.vue';

export default {
    components: { TreeSelectNode },
    props: {
        modelValue:  { type: [String, Number], default: '' },
        nodes:       { type: Array, default: () => [] },
        placeholder: { type: String, default: '請選擇' },
    },
    emits: ['update:modelValue'],
    setup(props, { emit }) {
        const open = ref(false);
        const wrapperRef = ref(null);

        // 建立扁平 id → label 查找
        const flatMap = computed(() => {
            const map = {};
            const walk = (nodes, pathParts) => {
                for (const n of nodes) {
                    const path = [...pathParts, n.label];
                    map[n.id] = path.join(' / ');
                    if (n.children?.length) walk(n.children, path);
                }
            };
            walk(props.nodes || [], []);
            return map;
        });

        const selectedLabel = computed(() => {
            if (!props.modelValue && props.modelValue !== 0) return '';
            return flatMap.value[props.modelValue] || '';
        });

        const selectItem = (id) => {
            emit('update:modelValue', id);
            open.value = false;
        };

        // 點擊外部關閉
        const handleClickOutside = (e) => {
            if (wrapperRef.value && !wrapperRef.value.contains(e.target)) {
                open.value = false;
            }
        };

        onMounted(() => document.addEventListener('click', handleClickOutside));
        onBeforeUnmount(() => document.removeEventListener('click', handleClickOutside));

        return { open, wrapperRef, selectedLabel, selectItem };
    },
};
</script>

<style scoped>
.tree-select {
    position: relative;
}
.tree-select-dropdown {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    z-index: 1050;
    background: #fff;
    max-height: 280px;
    overflow-y: auto;
    margin-top: 2px;
    min-width: 220px;
}
.tree-select-item {
    cursor: pointer;
    font-size: 0.85rem;
    border-bottom: 1px solid #f0f0f0;
}
.tree-select-item:hover {
    background: #f5f7fa;
}
</style>
