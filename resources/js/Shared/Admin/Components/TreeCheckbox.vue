<!-- resources/js/Shared/Admin/Components/TreeCheckbox.vue -->
<!-- 樹狀勾選元件 — 支援級聯勾選（父子連動） -->
<template>
    <div class="tree-checkbox">
        <div class="tree-checkbox-body border rounded">
            <div v-if="!nodes || nodes.length === 0" class="text-muted small py-3 px-3">
                <i class="fa fa-info-circle me-1"></i> 無可選項目
            </div>
            <TreeNode
                v-for="node in nodes"
                :key="node.id"
                :node="node"
                :selected="modelValue || []"
                :indeterminate-ids="indeterminateIds"
                :depth="0"
                @toggle="onToggle"
            />
        </div>

        <!-- 已選標籤 -->
        <div v-if="selectedLeafPaths.length > 0" class="mt-2 d-flex flex-wrap gap-1">
            <span
                v-for="item in selectedLeafPaths"
                :key="item.id"
                class="tree-selected-tag"
            >
                {{ item.path }}
                <i class="fa fa-times tree-tag-close" @click="onToggle(item.id)"></i>
            </span>
        </div>
    </div>
</template>

<script>
import { computed } from 'vue';
import TreeNode from './TreeNode.vue';

export default {
    components: { TreeNode },
    props: {
        modelValue: { type: Array, default: () => [] },
        nodes:      { type: Array, default: () => [] },
    },
    emits: ['update:modelValue'],
    setup(props, { emit }) {
        // ===== 建立 id → { node, parentId, childIds, fullPath } 查找表 =====
        const nodeInfo = computed(() => {
            const map = {};
            const walk = (nodes, parentId, pathParts) => {
                for (const n of nodes) {
                    const currentPath = [...pathParts, n.label];
                    const childIds = [];
                    if (n.children?.length) {
                        for (const c of n.children) childIds.push(c.id);
                    }
                    map[n.id] = {
                        id: n.id,
                        label: n.label,
                        parentId,
                        childIds,
                        fullPath: currentPath.join(' / '),
                        isLeaf: !n.children || n.children.length === 0,
                    };
                    if (n.children?.length) walk(n.children, n.id, currentPath);
                }
            };
            walk(props.nodes || [], null, []);
            return map;
        });

        // 取得所有後代 ID（遞迴）
        const getAllDescendants = (id) => {
            const info = nodeInfo.value[id];
            if (!info) return [];
            let ids = [];
            for (const cid of info.childIds) {
                ids.push(cid);
                ids = ids.concat(getAllDescendants(cid));
            }
            return ids;
        };

        // 取得所有祖先 ID（往上）
        const getAncestors = (id) => {
            const ids = [];
            let current = nodeInfo.value[id];
            while (current?.parentId != null) {
                ids.push(current.parentId);
                current = nodeInfo.value[current.parentId];
            }
            return ids;
        };

        // 半選狀態（部分子層被勾選，但非全部）
        const indeterminateIds = computed(() => {
            const set = new Set();
            const selected = new Set(props.modelValue || []);
            const check = (nodes) => {
                for (const n of nodes) {
                    if (n.children?.length) {
                        check(n.children);
                        const allDesc = getAllDescendants(n.id);
                        const checkedCount = allDesc.filter(d => selected.has(d)).length;
                        if (checkedCount > 0 && checkedCount < allDesc.length) {
                            set.add(n.id);
                        }
                    }
                }
            };
            check(props.nodes || []);
            return set;
        });

        // 只顯示葉子節點 or 沒有子層的已選項目路徑
        const selectedLeafPaths = computed(() => {
            const selected = props.modelValue || [];
            return selected
                .filter(id => {
                    const info = nodeInfo.value[id];
                    return info && info.isLeaf;
                })
                .map(id => ({
                    id,
                    path: nodeInfo.value[id]?.fullPath || id,
                }));
        });

        // ===== 級聯勾選邏輯 =====
        const onToggle = (id) => {
            const current = new Set(props.modelValue || []);
            const wasChecked = current.has(id);

            if (wasChecked) {
                // 取消：移除自己 + 所有後代
                current.delete(id);
                for (const desc of getAllDescendants(id)) {
                    current.delete(desc);
                }
                // 檢查祖先：如果同層都沒被勾，取消祖先
                uncheckAncestorsIfNeeded(id, current);
            } else {
                // 勾選：加入自己 + 所有後代
                current.add(id);
                for (const desc of getAllDescendants(id)) {
                    current.add(desc);
                }
                // 勾選祖先
                for (const anc of getAncestors(id)) {
                    current.add(anc);
                }
            }

            emit('update:modelValue', [...current]);
        };

        // 取消時往上檢查：若某祖先的所有子層都沒被勾，則取消該祖先
        const uncheckAncestorsIfNeeded = (id, currentSet) => {
            const info = nodeInfo.value[id];
            if (!info || info.parentId == null) return;

            const parent = nodeInfo.value[info.parentId];
            if (!parent) return;

            // 檢查此父層底下是否還有任何後代被勾
            const descendants = getAllDescendants(parent.id);
            const anyChecked = descendants.some(d => currentSet.has(d));

            if (!anyChecked) {
                currentSet.delete(parent.id);
                uncheckAncestorsIfNeeded(parent.id, currentSet);
            }
        };

        return { indeterminateIds, selectedLeafPaths, onToggle };
    },
};
</script>

<style scoped>
.tree-checkbox-body {
    max-height: 300px;
    overflow-y: auto;
    background: #fafbfc;
    padding: 8px 4px;
}
.tree-selected-tag {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    background: #ecf5ff;
    color: #409eff;
    border: 1px solid #d9ecff;
    border-radius: 4px;
    padding: 2px 8px;
    font-size: 0.8rem;
    line-height: 1.4;
}
.tree-tag-close {
    cursor: pointer;
    font-size: 0.6rem;
    margin-left: 2px;
    opacity: .7;
}
.tree-tag-close:hover {
    opacity: 1;
}
</style>
