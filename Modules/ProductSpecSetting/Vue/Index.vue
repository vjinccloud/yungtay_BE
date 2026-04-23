<!-- Modules/ProductSpecSetting/Vue/Index.vue -->
<!-- 商品規格設定 - 主頁（規格群組 + 規格組合定義） -->
<template>
    <div class="content">
        <BreadcrumbItem />

        <!-- ===== 規格群組區塊 ===== -->
        <div class="block block-rounded">
            <div class="block-header block-header-default">
                <h3 class="block-title">規格群組管理</h3>
                <div class="block-options">
                    <Link
                        :href="route('admin.product-spec-settings.groups.add')"
                        class="btn btn-sm btn-primary"
                    >
                        <i class="fa fa-plus me-1"></i>
                        新增規格群組
                    </Link>
                </div>
            </div>

            <div class="block-content block-content-full">
                <div v-if="groupList.length === 0" class="text-center text-muted py-4">
                    <i class="fa fa-info-circle me-1"></i>
                    尚無規格群組，請點擊「新增規格群組」開始建立
                </div>

                <div v-else class="table-responsive">
                    <table class="table table-bordered table-striped table-vcenter">
                        <thead>
                            <tr>
                                <th style="width: 60px;" class="text-center">排序</th>
                                <th>群組名稱</th>
                                <th>規格值</th>
                                <th style="width: 80px;" class="text-center">狀態</th>
                                <th style="width: 140px;" class="text-center">操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="group in groupList" :key="group.id">
                                <td class="text-center text-muted">{{ group.seq }}</td>
                                <td>
                                    <strong>{{ group.name_primary }}</strong>
                                </td>
                                <td>
                                    <span
                                        v-for="val in group.values"
                                        :key="val.id"
                                        class="badge me-1 mb-1"
                                        :class="val.status ? 'bg-primary' : 'bg-secondary'"
                                    >
                                        {{ val.name_primary }}
                                    </span>
                                    <span v-if="group.values.length === 0" class="text-muted small">
                                        尚無規格值
                                    </span>
                                </td>
                                <td class="text-center">
                                    <span
                                        class="badge cursor-pointer"
                                        :class="group.status ? 'bg-success' : 'bg-secondary'"
                                        @click="toggleGroupStatus(group.id)"
                                    >
                                        {{ group.status ? '啟用' : '停用' }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm">
                                        <Link
                                            :href="route('admin.product-spec-settings.groups.edit', group.id)"
                                            class="btn btn-outline-primary"
                                            title="編輯"
                                        >
                                            <i class="fa fa-pencil-alt"></i>
                                        </Link>
                                        <button
                                            class="btn btn-outline-danger"
                                            title="刪除"
                                            @click="deleteGroup(group)"
                                        >
                                            <i class="fa fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ===== 規格組合區塊 ===== -->
        <div class="block block-rounded">
            <div class="block-header block-header-default">
                <h3 class="block-title">規格組合</h3>
                <div class="block-options">
                    <button
                        class="btn btn-sm btn-primary"
                        @click="openComboModal(null)"
                        :disabled="groupList.length === 0"
                    >
                        <i class="fa fa-plus me-1"></i>
                        新增組合
                    </button>
                </div>
            </div>

            <div class="block-content block-content-full">
                <div v-if="combinationList.length === 0" class="text-center text-muted py-4">
                    <i class="fa fa-info-circle me-1"></i>
                    尚無規格組合，請先建立規格群組，再點擊「新增組合」定義群組搭配
                </div>

                <div v-else class="table-responsive">
                    <table class="table table-bordered table-striped table-vcenter">
                        <thead>
                            <tr>
                                <th style="width: 60px;" class="text-center">排序</th>
                                <th>組合名稱</th>
                                <th>包含群組</th>
                                <th style="width: 80px;" class="text-center">狀態</th>
                                <th style="width: 140px;" class="text-center">操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="combo in combinationList" :key="combo.id">
                                <td class="text-center text-muted">{{ combo.seq }}</td>
                                <td>
                                    <strong>{{ combo.name_zh }}</strong>
                                </td>
                                <td>
                                    <span
                                        v-for="(g, gi) in combo.groups"
                                        :key="g.id"
                                    >
                                        <span v-if="gi > 0" class="text-muted mx-1">+</span>
                                        <span class="badge bg-info">{{ g.name_primary }}</span>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <span
                                        class="badge cursor-pointer"
                                        :class="combo.status ? 'bg-success' : 'bg-secondary'"
                                        @click="toggleComboStatus(combo.id)"
                                    >
                                        {{ combo.status ? '啟用' : '停用' }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm">
                                        <button
                                            class="btn btn-outline-primary"
                                            title="編輯"
                                            @click="openComboModal(combo)"
                                        >
                                            <i class="fa fa-pencil-alt"></i>
                                        </button>
                                        <button
                                            class="btn btn-outline-danger"
                                            title="刪除"
                                            @click="deleteCombination(combo)"
                                        >
                                            <i class="fa fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ===== 新增/編輯組合 Modal ===== -->
        <div
            class="modal fade"
            id="comboModal"
            tabindex="-1"
            aria-labelledby="comboModalLabel"
            aria-hidden="true"
            ref="comboModalRef"
        >
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content combo-modal">
                    <!-- Header -->
                    <div class="combo-modal-header">
                        <div class="combo-modal-header-inner">
                            <div class="combo-modal-icon">
                                <i class="fa fa-layer-group"></i>
                            </div>
                            <div>
                                <h5 class="mb-0">{{ isEditingCombo ? '編輯規格組合' : '新增規格組合' }}</h5>
                                <small class="opacity-75">勾選要搭配的規格群組</small>
                            </div>
                        </div>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <!-- Body -->
                    <div class="modal-body p-0">
                        <!-- 群組勾選列表 -->
                        <div class="combo-body-section">
                            <div v-if="groupList.length === 0" class="text-muted text-center py-4">
                                <i class="fa fa-inbox fa-2x mb-2 d-block opacity-50"></i>
                                請先建立規格群組
                            </div>
                            <div v-else class="combo-group-grid">
                                <div
                                    v-for="group in groupList"
                                    :key="group.id"
                                    class="combo-group-card"
                                    :class="{ 'is-selected': comboForm.group_ids.includes(group.id) }"
                                    @click="toggleGroupCheck(group.id)"
                                >
                                    <div class="combo-group-card-check">
                                        <i v-if="comboForm.group_ids.includes(group.id)" class="fa fa-check-circle text-primary"></i>
                                        <i v-else class="far fa-circle text-muted"></i>
                                    </div>
                                    <div class="combo-group-card-body">
                                        <div class="fw-semibold">{{ group.name_primary }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 組合預覽 + 名稱 -->
                        <div v-if="comboForm.group_ids.length > 0" class="combo-name-section">
                            <div class="combo-preview-bar">
                                <span
                                    v-for="(gid, i) in comboForm.group_ids"
                                    :key="gid"
                                    class="d-inline-flex align-items-center"
                                >
                                    <span v-if="i > 0" class="combo-plus">+</span>
                                    <span class="badge bg-primary rounded-pill">{{ getGroupNameById(gid) }}</span>
                                </span>
                            </div>
                            <div class="row g-2 mt-3">
                                <div class="col-12">
                                    <TranslatableInput
                                        v-model="comboForm.name"
                                        label="組合名稱"
                                        size="sm"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="modal-footer border-0 pt-0 px-4 pb-4">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">取消</button>
                        <button
                            type="button"
                            class="btn btn-primary"
                            @click="submitCombo"
                            :disabled="isSubmittingCombo || comboForm.group_ids.length === 0"
                        >
                            <i class="fa fa-check me-1"></i>
                            {{ isSubmittingCombo ? '處理中...' : (isEditingCombo ? '儲存變更' : '確認新增') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { ref, reactive, watch, inject } from "vue";
import { Link, router, usePage } from "@inertiajs/vue3";
import Layout from "@/Shared/Admin/Layout.vue";
import BreadcrumbItem from "@/Shared/Admin/Partials/BreadcrumbItem.vue";
import TranslatableInput from "@/Shared/Admin/Components/TranslatableInput.vue";

export default {
    components: { BreadcrumbItem, Link, TranslatableInput },
    props: {
        groups: { type: Array, default: () => [] },
        combinations: { type: Array, default: () => [] },
    },
    setup(props) {
        const sweetAlert = inject('$sweetAlert');
        const page = usePage();
        const locales = page.props.translatableLocales || { zh_TW: { label: '中文' } };
        const localeKeys = Object.keys(locales);
        const primaryLocale = page.props.translatablePrimary || 'zh_TW';

        const buildEmptyTranslatable = () => {
            const obj = {};
            localeKeys.forEach(k => { obj[k] = ''; });
            return obj;
        };

        // ===== 群組 =====
        const groupList = ref([...(props.groups || [])]);

        // ===== 組合 =====
        const combinationList = ref([...(props.combinations || [])]);

        // ===== 監聽 props 變化（Inertia reload 後同步） =====
        watch(() => props.groups, (val) => { groupList.value = [...(val || [])]; }, { deep: true });
        watch(() => props.combinations, (val) => { combinationList.value = [...(val || [])]; }, { deep: true });

        // ===== Modal =====
        const comboModalRef = ref(null);
        let bsModal = null;
        const isSubmittingCombo = ref(false);
        const isEditingCombo = ref(false);
        const editingComboId = ref(null);

        const comboForm = reactive({
            name: buildEmptyTranslatable(),
            group_ids: [],
        });

        const getGroupNameById = (id) => {
            const g = groupList.value.find(g => g.id === id);
            return g ? g.name_primary : '';
        };

        const autoFillName = () => {
            // 自動填入主要語系的名稱
            const primaryName = comboForm.group_ids.map(id => getGroupNameById(id)).filter(Boolean).join(' + ');
            comboForm.name = { ...comboForm.name, [primaryLocale]: primaryName };
        };

        const toggleGroupCheck = (groupId) => {
            const idx = comboForm.group_ids.indexOf(groupId);
            if (idx >= 0) {
                comboForm.group_ids.splice(idx, 1);
            } else {
                comboForm.group_ids.push(groupId);
            }
            autoFillName();
        };

        const resetComboForm = () => {
            comboForm.name = buildEmptyTranslatable();
            comboForm.group_ids = [];
            isEditingCombo.value = false;
            editingComboId.value = null;
        };

        const openComboModal = (combo) => {
            resetComboForm();

            if (combo) {
                isEditingCombo.value = true;
                editingComboId.value = combo.id;
                // 從 combo.name 物件取得各語系，或從 name_zh 向下相容
                if (combo.name && typeof combo.name === 'object') {
                    localeKeys.forEach(k => { comboForm.name[k] = combo.name[k] || ''; });
                } else {
                    comboForm.name[primaryLocale] = combo.name_zh || '';
                }
                comboForm.group_ids = [...(combo.group_ids || [])];
            }

            if (!bsModal && comboModalRef.value) {
                bsModal = new window.bootstrap.Modal(comboModalRef.value);
            }
            if (bsModal) {
                bsModal.show();
            }
        };

        const submitCombo = () => {
            if (!comboForm.name[primaryLocale]?.trim()) {
                sweetAlert.error({ msg: '請輸入組合名稱' });
                return;
            }
            if (comboForm.group_ids.length === 0) {
                sweetAlert.error({ msg: '請至少選擇一個規格群組' });
                return;
            }

            isSubmittingCombo.value = true;

            const payload = {
                name: comboForm.name,
                group_ids: comboForm.group_ids,
            };

            const request = isEditingCombo.value
                ? axios.put(route('admin.api.product-spec-settings.combinations.update', editingComboId.value), payload)
                : axios.post(route('admin.api.product-spec-settings.combinations.store'), payload);

            request
                .then(res => {
                    if (res.data.status) {
                        sweetAlert.success({ msg: res.data.msg });
                        if (bsModal) bsModal.hide();
                        router.reload({ only: ['groups', 'combinations'] });
                    } else {
                        sweetAlert.error({ msg: res.data.msg || '操作失敗' });
                    }
                })
                .catch(err => {
                    const msg = err.response?.data?.msg || err.response?.data?.message || '操作失敗';
                    sweetAlert.error({ msg });
                })
                .finally(() => { isSubmittingCombo.value = false; });
        };

        // ===== 群組操作 =====
        const toggleGroupStatus = (id) => {
            axios.put(route('admin.product-spec-settings.groups.toggle-active'), { id })
                .then(res => {
                    if (res.data.status) {
                        sweetAlert.success({ msg: res.data.msg });
                        router.reload({ only: ['groups'] });
                    }
                })
                .catch(() => { sweetAlert.error({ msg: '操作失敗' }); });
        };

        const deleteGroup = (group) => {
            const name = group.name_primary || group.name;
            sweetAlert.confirm(`確定要刪除規格群組「${name}」嗎？\n（底下的規格值將一併刪除）`, () => {
                router.delete(route('admin.product-spec-settings.groups.destroy', group.id), {
                    preserveScroll: true,
                    onSuccess: () => {
                        sweetAlert.success({ msg: '刪除成功' });
                    },
                    onError: () => {
                        sweetAlert.error({ msg: '刪除失敗' });
                    }
                });
            });
        };

        // ===== 組合操作 =====
        const toggleComboStatus = (id) => {
            axios.put(route('admin.product-spec-settings.combinations.toggle-active'), { id })
                .then(res => {
                    if (res.data.status) {
                        sweetAlert.success({ msg: res.data.msg });
                        router.reload({ only: ['combinations'] });
                    }
                })
                .catch(() => { sweetAlert.error({ msg: '操作失敗' }); });
        };

        const deleteCombination = (combo) => {
            const name = combo.name_zh || combo.name_primary || combo.label;
            sweetAlert.confirm(`確定要刪除規格組合「${name}」嗎？`, () => {
                axios.delete(route('admin.api.product-spec-settings.combinations.destroy', combo.id))
                    .then(res => {
                        if (res.data.status) {
                            sweetAlert.success({ msg: res.data.msg });
                            router.reload({ only: ['combinations'] });
                        }
                    })
                    .catch(() => { sweetAlert.error({ msg: '刪除失敗' }); });
            });
        };

        return {
            groupList,
            toggleGroupStatus,
            deleteGroup,
            combinationList,
            toggleComboStatus,
            deleteCombination,
            comboModalRef,
            comboForm,
            isSubmittingCombo,
            isEditingCombo,
            openComboModal,
            submitCombo,
            getGroupNameById,
            toggleGroupCheck,
        };
    },
    layout: Layout,
};
</script>

<style scoped>
.cursor-pointer {
    cursor: pointer;
}

/* ===== Modal 整體 ===== */
:deep(.combo-modal) {
    border: none !important;
    border-radius: 12px !important;
    overflow: hidden !important;
    box-shadow: 0 20px 60px rgba(0, 0, 0, .35) !important;
    background: #fff !important;
}

:deep(.combo-modal .modal-body) {
    background: #fff !important;
    padding: 0 !important;
}

:deep(.combo-modal .modal-footer) {
    background: #fff !important;
    border-top: none !important;
    padding: 12px 24px 20px !important;
}

.combo-modal-header {
    background: linear-gradient(135deg, #1e3a5f 0%, #2563eb 100%);
    color: #fff;
    padding: 20px 24px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    position: relative;
    z-index: 1;
}

.combo-modal-header-inner {
    display: flex;
    align-items: center;
    gap: 14px;
}

.combo-modal-icon {
    width: 42px;
    height: 42px;
    background: rgba(255, 255, 255, .15);
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
}

/* ===== 群組勾選格狀卡片 ===== */
.combo-body-section {
    padding: 20px 24px;
    background: #fff;
}

.combo-group-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 10px;
}

.combo-group-card {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 14px;
    border: 2px solid #e5e7eb;
    border-radius: 10px;
    cursor: pointer;
    transition: all .15s ease;
    background: #fff;
    user-select: none;
}

.combo-group-card:hover {
    border-color: #93c5fd;
    background: #f0f9ff;
}

.combo-group-card.is-selected {
    border-color: #3b82f6;
    background: #eff6ff;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, .15);
}

.combo-group-card-check {
    font-size: 1.3rem;
    line-height: 1;
    flex-shrink: 0;
}

.combo-group-card-body {
    min-width: 0;
}

/* ===== 組合預覽 + 名稱 ===== */
.combo-name-section {
    border-top: 1px solid #e5e7eb;
    background: #f8fafc;
    padding: 16px 24px;
}

.combo-preview-bar {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 6px;
}

.combo-preview-bar .badge {
    font-size: .82em;
    padding: 5px 12px;
    font-weight: 500;
}

.combo-plus {
    font-weight: 700;
    color: #9ca3af;
    margin: 0 2px;
    font-size: 1rem;
}

/* ===== 名稱欄位在 Modal 裡的深色主題相容 ===== */
.combo-name-section .form-label {
    color: #6b7280 !important;
}

.combo-name-section .form-control {
    background: #fff !important;
    color: #1f2937 !important;
    border-color: #d1d5db !important;
}
</style>
