<!-- resources/js/Shared/Admin/Components/TreeNode.vue -->
<!-- 樹狀節點（遞迴）— 支援級聯勾選 + 半選狀態 -->
<template>
    <div class="tree-node" :style="{ paddingLeft: depth * 20 + 'px' }">
        <div class="tree-node-row d-flex align-items-center py-1">
            <!-- 展開/收合按鈕 -->
            <button
                v-if="node.children && node.children.length > 0"
                type="button"
                class="btn btn-sm p-0 me-1 tree-toggle-btn"
                @click="expanded = !expanded"
            >
                <i class="fa" :class="expanded ? 'fa-chevron-down' : 'fa-chevron-right'" style="font-size: .65rem; width: 14px;"></i>
            </button>
            <span v-else style="width: 22px; display: inline-block;"></span>

            <!-- Checkbox -->
            <div class="form-check mb-0">
                <input
                    type="checkbox"
                    class="form-check-input"
                    :id="'tree-node-' + node.id"
                    :checked="isChecked"
                    ref="cbRef"
                    @change="$emit('toggle', node.id)"
                />
                <label class="form-check-label" :for="'tree-node-' + node.id">
                    {{ node.label }}
                </label>
            </div>
        </div>

        <!-- 子節點 -->
        <div v-if="expanded && node.children && node.children.length > 0">
            <TreeNode
                v-for="child in node.children"
                :key="child.id"
                :node="child"
                :selected="selected"
                :indeterminate-ids="indeterminateIds"
                :depth="depth + 1"
                @toggle="(id) => $emit('toggle', id)"
            />
        </div>
    </div>
</template>

<script>
import { ref, computed, watch } from 'vue';

export default {
    name: 'TreeNode',
    props: {
        node:             { type: Object, required: true },
        selected:         { type: Array, default: () => [] },
        indeterminateIds: { type: Set, default: () => new Set() },
        depth:            { type: Number, default: 0 },
    },
    emits: ['toggle'],
    setup(props) {
        const expanded = ref(true);
        const cbRef = ref(null);

        const isChecked = computed(() => props.selected.includes(props.node.id));
        const isIndeterminate = computed(() => props.indeterminateIds.has(props.node.id));

        // 監聽 indeterminate 變化，同步到 DOM
        watch([isIndeterminate, cbRef], ([val, el]) => {
            if (el) el.indeterminate = val;
        }, { immediate: true });

        return { expanded, isChecked, cbRef };
    },
};
</script>

<style scoped>
.tree-node-row:hover {
    background-color: rgba(59, 130, 246, 0.04);
    border-radius: 4px;
}
.tree-toggle-btn {
    background: none;
    border: none;
    color: #6c757d;
    line-height: 1;
}
.tree-toggle-btn:hover {
    color: #333;
}
.form-check-label {
    cursor: pointer;
    user-select: none;
    font-size: 0.9rem;
}
</style>
