<!-- 選單管理 Modal（Ctrl+F11 觸發） -->
<template>
    <!-- Modal -->
    <div class="modal fade menu-setting-modal" ref="modalRef" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content" style="background: #fff;">
                <!-- Header -->
                <div class="modal-header" style="background: #0284c7; border-bottom: none;">
                    <h5 class="modal-title text-white">
                        <i class="fa fa-bars me-2"></i>選單管理
                        <small class="ms-2" style="opacity: 0.7;">(Ctrl+F11)</small>
                    </h5>
                    <button type="button" class="btn-close btn-close-white" @click="closeModal"></button>
                </div>

                <!-- Body -->
                <div class="modal-body p-0" style="min-height: 500px; background: #fff;">
                    <!-- 工具列 -->
                    <div class="px-3 py-2 border-bottom d-flex justify-content-between align-items-center" style="background: #f8fafc;">
                        <div>
                            <button class="btn btn-sm btn-primary" @click="openAddForm">
                                <i class="fa fa-plus me-1"></i>新增選單
                            </button>
                            <button class="btn btn-sm btn-outline-secondary ms-2" @click="loadMenus">
                                <i class="fa fa-sync me-1"></i>重新整理
                            </button>
                        </div>
                        <div>
                            <button class="btn btn-sm" :class="viewMode === 'tree' ? 'btn-info' : 'btn-outline-secondary'" @click="viewMode = 'tree'">
                                <i class="fa fa-sitemap me-1"></i>樹狀
                            </button>
                            <button class="btn btn-sm ms-1" :class="viewMode === 'flat' ? 'btn-info' : 'btn-outline-secondary'" @click="viewMode = 'flat'">
                                <i class="fa fa-list me-1"></i>平面
                            </button>
                        </div>
                    </div>

                    <div class="row g-0" style="min-height: 450px;">
                        <!-- 左側：選單列表 -->
                        <div class="col-md-6" style="border-right: 1px solid #e2e8f0; background: #fff;">
                            <div v-if="loading" class="text-center py-5">
                                <i class="fa fa-spinner fa-spin fa-2x text-primary"></i>
                                <p class="mt-2 text-muted">載入中...</p>
                            </div>
                            <div v-else class="p-3" style="overflow-y: auto; max-height: 450px;">
                                <!-- 樹狀顯示 -->
                                <template v-if="viewMode === 'tree'">
                                    <div v-if="menuTree.length === 0" class="text-center py-4 text-muted">
                                        <i class="fa fa-inbox fa-2x mb-2"></i>
                                        <p>尚無選單資料</p>
                                    </div>
                                    <MenuTreeNode 
                                        v-for="menu in menuTree" 
                                        :key="menu.id" 
                                        :menu="menu"
                                        :selected-id="selectedId"
                                        @select="selectMenu"
                                        @toggle-status="toggleStatus"
                                        @delete="confirmDelete"
                                    />
                                </template>
                                <!-- 平面顯示 -->
                                <template v-else>
                                    <table class="table table-sm table-hover table-vcenter mb-0">
                                        <thead>
                                            <tr>
                                                <th style="width:40px">序</th>
                                                <th>名稱</th>
                                                <th style="width:60px" class="text-center">層級</th>
                                                <th style="width:60px" class="text-center">類型</th>
                                                <th style="width:60px" class="text-center">狀態</th>
                                                <th style="width:90px" class="text-center">操作</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr 
                                                v-for="menu in flatList" 
                                                :key="menu.id"
                                                :class="{ 'table-primary': selectedId === menu.id }"
                                                style="cursor: pointer;"
                                                @click="selectMenu(menu)"
                                            >
                                                <td class="text-center text-muted">{{ menu.seq }}</td>
                                                <td>
                                                    <span :style="{ paddingLeft: (menu.level * 20) + 'px' }">
                                                        <i v-if="menu.level > 0" class="fa fa-angle-right text-muted me-1"></i>
                                                        {{ menu.title }}
                                                    </span>
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge bg-secondary">L{{ menu.level }}</span>
                                                </td>
                                                <td class="text-center">
                                                    <span :class="menu.type === 1 ? 'badge bg-info' : 'badge bg-secondary'">
                                                        {{ menu.type === 1 ? '顯示' : '隱藏' }}
                                                    </span>
                                                </td>
                                                <td class="text-center">
                                                    <span 
                                                        :class="menu.status ? 'badge bg-success' : 'badge bg-danger'"
                                                        style="cursor: pointer;"
                                                        @click.stop="toggleStatus(menu)"
                                                    >
                                                        {{ menu.status ? '啟用' : '停用' }}
                                                    </span>
                                                </td>
                                                <td class="text-center">
                                                    <button class="btn btn-sm btn-info me-1" @click.stop="selectMenu(menu)" title="編輯">
                                                        <i class="fa fa-pencil-alt"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-danger" @click.stop="confirmDelete(menu)" title="刪除">
                                                        <i class="fa fa-trash"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </template>
                            </div>
                        </div>

                        <!-- 右側：編輯表單 -->
                        <div class="col-md-6" style="background: #fff;">
                            <div v-if="!showForm" class="text-center py-5 text-muted">
                                <i class="fa fa-hand-pointer fa-3x mb-3 opacity-50"></i>
                                <p>點擊左側選單進行編輯<br>或按「新增選單」建立新項目</p>
                            </div>
                            <div v-else class="p-3">
                                <h6 class="mb-3 border-bottom pb-2">
                                    <i :class="isEditMode ? 'fa fa-edit' : 'fa fa-plus'" class="me-1"></i>
                                    {{ isEditMode ? '編輯選單' : '新增選單' }}
                                </h6>
                                <form @submit.prevent="saveMenu">
                                    <!-- 選單名稱 -->
                                    <div class="mb-3">
                                        <label class="form-label">
                                            選單名稱 <span class="text-danger">*</span>
                                        </label>
                                        <input v-model="form.title" type="text" class="form-control form-control-sm"
                                            :class="{ 'is-invalid': errors.title }" placeholder="請輸入選單名稱">
                                        <div v-if="errors.title" class="invalid-feedback">{{ errors.title }}</div>
                                    </div>

                                    <!-- 父層選單 -->
                                    <div class="mb-3">
                                        <label class="form-label">
                                            父層選單 <span class="text-danger">*</span>
                                        </label>
                                        <select v-model="form.parent_id" class="form-select form-select-sm"
                                            :class="{ 'is-invalid': errors.parent_id }">
                                            <option v-for="opt in parentOptions" :key="opt.value" :value="opt.value">
                                                {{ opt.label }}
                                            </option>
                                        </select>
                                        <div v-if="errors.parent_id" class="invalid-feedback">{{ errors.parent_id }}</div>
                                    </div>

                                    <!-- 顯示類型 -->
                                    <div class="mb-3">
                                        <label class="form-label">
                                            顯示類型 <span class="text-danger">*</span>
                                        </label>
                                        <select v-model="form.type" class="form-select form-select-sm"
                                            :class="{ 'is-invalid': errors.type }">
                                            <option :value="1">顯示在選單</option>
                                            <option :value="0">不顯示</option>
                                        </select>
                                        <div v-if="errors.type" class="invalid-feedback">{{ errors.type }}</div>
                                    </div>

                                    <!-- 連結網址 -->
                                    <div class="mb-3">
                                        <label class="form-label">連結網址</label>
                                        <input v-model="form.url" type="text" class="form-control form-control-sm"
                                            placeholder="例如：admin/news">
                                        <small class="text-muted">選單對應的後台路徑</small>
                                    </div>

                                    <!-- 路由名稱 -->
                                    <div class="mb-3">
                                        <label class="form-label">路由名稱</label>
                                        <input v-model="form.url_name" type="text" class="form-control form-control-sm"
                                            placeholder="例如：admin.news">
                                        <small class="text-muted">Laravel 路由名稱</small>
                                    </div>

                                    <!-- 圖標 & 排序 -->
                                    <div class="row mb-3">
                                        <div class="col-8">
                                            <label class="form-label">圖標</label>
                                            <input v-model="form.icon_image" type="text" class="form-control form-control-sm"
                                                placeholder="例如：fa fa-bars">
                                            <small class="text-muted">
                                                <span v-if="form.icon_image" class="me-2">
                                                    預覽：<i :class="form.icon_image"></i>
                                                </span>
                                                FontAwesome class
                                            </small>
                                        </div>
                                        <div class="col-4">
                                            <label class="form-label">排序</label>
                                            <input v-model.number="form.seq" type="number" class="form-control form-control-sm" min="0">
                                        </div>
                                    </div>

                                    <!-- 啟用狀態 -->
                                    <div class="mb-3">
                                        <div class="form-check form-switch">
                                            <input v-model="form.status" type="checkbox" class="form-check-input" id="menuStatus">
                                            <label class="form-check-label" for="menuStatus">啟用</label>
                                        </div>
                                    </div>

                                    <!-- 按鈕 -->
                                    <div class="d-flex gap-2">
                                        <button type="submit" class="btn btn-sm btn-primary" :disabled="saving">
                                            <i :class="saving ? 'fa fa-spinner fa-spin' : 'fa fa-save'" class="me-1"></i>
                                            {{ saving ? '儲存中...' : '儲存' }}
                                        </button>
                                        <button type="button" class="btn btn-sm btn-secondary" @click="cancelForm">
                                            取消
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { ref, reactive, onMounted, onUnmounted, nextTick, inject, defineComponent } from 'vue';
import { createModal } from '@/utils/bootstrapModal.js';
import { router } from '@inertiajs/vue3';
import axios from 'axios';
import MenuTreeNode from './MenuTreeNode.vue';

export default defineComponent({
    name: 'MenuSettingModal',
    components: { MenuTreeNode },
    setup(props, { expose }) {
        const sweetAlert = inject('$sweetAlert');

        // DOM ref
        const modalRef = ref(null);
        let modalInstance = null;

        // 狀態
        const loading = ref(false);
        const saving = ref(false);
        const menuTree = ref([]);
        const flatList = ref([]);
        const parentOptions = ref([{ value: 0, label: '頂層（無父層）' }]);
        const viewMode = ref('tree');
        const showForm = ref(false);
        const isEditMode = ref(false);
        const selectedId = ref(null);
        const errors = reactive({});

        // 表單
        const defaultForm = {
            title: '',
            parent_id: 0,
            type: 1,
            url: '',
            url_name: '',
            icon_image: '',
            seq: 0,
            status: true,
        };
        const form = reactive({ ...defaultForm });

        // ========== API ==========
        const apiBase = '/admin/api/menu-settings';

        const loadMenus = async () => {
            loading.value = true;
            try {
                const res = await axios.get(apiBase);
                menuTree.value = res.data.data;
                flatList.value = flattenTree(res.data.data);
            } catch (e) {
                console.error('載入選單失敗', e);
            } finally {
                loading.value = false;
            }
        };

        const loadParentOptions = async (excludeId = null) => {
            try {
                const params = excludeId ? { exclude_id: excludeId } : {};
                const res = await axios.get(`${apiBase}/parent-options`, { params });
                parentOptions.value = res.data.data;
            } catch (e) {
                console.error('載入父層選項失敗', e);
            }
        };

        const flattenTree = (tree, result = []) => {
            for (const item of tree) {
                result.push(item);
                if (item.children && item.children.length > 0) {
                    flattenTree(item.children, result);
                }
            }
            return result;
        };

        /**
         * 重新載入側邊欄選單（透過 Inertia reload shared props）
         */
        const refreshSidebar = () => {
            router.reload({ only: ['menuItems'] });
        };

        // ========== 表單操作 ==========
        const resetForm = () => {
            Object.assign(form, { ...defaultForm });
            Object.keys(errors).forEach(k => delete errors[k]);
            selectedId.value = null;
        };

        const openAddForm = async () => {
            resetForm();
            isEditMode.value = false;
            showForm.value = true;
            await loadParentOptions();
        };

        const selectMenu = async (menu) => {
            try {
                const res = await axios.get(`${apiBase}/${menu.id}`);
                const data = res.data.data;
                Object.assign(form, {
                    title: data.title || '',
                    parent_id: data.parent_id ?? 0,
                    type: data.type ?? 1,
                    url: data.url || '',
                    url_name: data.url_name || '',
                    icon_image: data.icon_image || '',
                    seq: data.seq ?? 0,
                    status: !!data.status,
                });
                parentOptions.value = res.data.parentOptions;
                selectedId.value = menu.id;
                isEditMode.value = true;
                showForm.value = true;
                Object.keys(errors).forEach(k => delete errors[k]);
            } catch (e) {
                console.error('載入選單資料失敗', e);
            }
        };

        const saveMenu = async () => {
            // 前端基礎驗證
            Object.keys(errors).forEach(k => delete errors[k]);
            if (!form.title?.trim()) {
                errors.title = '請輸入選單名稱';
                return;
            }

            saving.value = true;
            try {
                let res;
                if (isEditMode.value) {
                    res = await axios.put(`${apiBase}/${selectedId.value}`, { ...form });
                } else {
                    res = await axios.post(apiBase, { ...form });
                }

                if (res.data.status) {
                    sweetAlert.success({ msg: res.data.msg || '儲存成功' });
                    await loadMenus();
                    refreshSidebar();
                    // 如果是新增，儲存後切回無選取狀態
                    if (!isEditMode.value) {
                        cancelForm();
                    }
                } else {
                    sweetAlert.error({ msg: res.data.msg || '儲存失敗' });
                }
            } catch (e) {
                if (e.response?.status === 422 && e.response.data?.errors) {
                    const serverErrors = e.response.data.errors;
                    for (const [field, messages] of Object.entries(serverErrors)) {
                        errors[field] = Array.isArray(messages) ? messages[0] : messages;
                    }
                } else {
                    sweetAlert.error({ msg: '儲存失敗' });
                }
            } finally {
                saving.value = false;
            }
        };

        const toggleStatus = async (menu) => {
            try {
                const res = await axios.put('/admin/menu-settings/toggle-active', { id: menu.id });
                if (res.data.status) {
                    sweetAlert.success({ msg: res.data.msg });
                    await loadMenus();
                    refreshSidebar();
                }
            } catch (e) {
                sweetAlert.error({ msg: '操作失敗' });
            }
        };

        const confirmDelete = async (menu) => {
            try {
                // 先取得刪除資訊（含子孫數量）
                const infoRes = await axios.get(`${apiBase}/${menu.id}/delete-info`);
                const info = infoRes.data;

                let confirmMsg = `確定要刪除「${menu.title}」嗎？`;

                if (info.has_children) {
                    const childList = info.descendant_titles.slice(0, 10).join('、');
                    const moreText = info.descendant_count > 10 ? `...等共 ${info.descendant_count} 個` : '';
                    confirmMsg = `⚠️ 警告：「${menu.title}」底下還有 ${info.descendant_count} 個子選單：\n\n`
                        + `${childList}${moreText}\n\n`
                        + `刪除後將連同所有子選單及其對應權限一併刪除，此操作無法還原！\n\n確定要繼續嗎？`;
                }

                sweetAlert.confirm(confirmMsg, async () => {
                    try {
                        const res = await axios.delete(`${apiBase}/${menu.id}`);
                        if (res.data.status) {
                            sweetAlert.success({ msg: res.data.msg });
                            // 如果被刪的是正在編輯的或其子孫，取消表單
                            if (selectedId.value === menu.id || (info.has_children && info.descendant_titles.length > 0)) {
                                cancelForm();
                            }
                            await loadMenus();
                            refreshSidebar();
                        } else {
                            sweetAlert.error({ msg: res.data.msg });
                        }
                    } catch (e) {
                        sweetAlert.error({ msg: '刪除失敗' });
                    }
                });
            } catch (e) {
                // 取得資訊失敗時 fallback 為簡單確認
                sweetAlert.confirm(`確定要刪除「${menu.title}」嗎？`, async () => {
                    try {
                        const res = await axios.delete(`${apiBase}/${menu.id}`);
                        if (res.data.status) {
                            sweetAlert.success({ msg: res.data.msg });
                            if (selectedId.value === menu.id) {
                                cancelForm();
                            }
                            await loadMenus();
                            refreshSidebar();
                        } else {
                            sweetAlert.error({ msg: res.data.msg });
                        }
                    } catch (e2) {
                        sweetAlert.error({ msg: '刪除失敗' });
                    }
                });
            }
        };

        const cancelForm = () => {
            showForm.value = false;
            resetForm();
        };

        // ========== Modal 控制 ==========
        const openModal = async () => {
            if (!modalInstance && modalRef.value) {
                modalInstance = createModal(modalRef.value, { backdrop: 'static', keyboard: true });
            }
            modalInstance?.show();
            await loadMenus();
        };

        const closeModal = () => {
            modalInstance?.hide();
            cancelForm();
        };

        // Expose 給父元件呼叫
        expose({ openModal, closeModal });

        // 清理
        onUnmounted(() => {
            modalInstance?.dispose();
            modalInstance = null;
        });

        return {
            modalRef,
            loading,
            saving,
            menuTree,
            flatList,
            parentOptions,
            viewMode,
            showForm,
            isEditMode,
            selectedId,
            form,
            errors,
            loadMenus,
            openAddForm,
            selectMenu,
            saveMenu,
            toggleStatus,
            confirmDelete,
            cancelForm,
            closeModal,
        };
    },
});
</script>

<style>
/* MenuSettingModal - 確保 SweetAlert 顯示在 modal 之上 */
.menu-setting-modal {
    z-index: 1055 !important;
}
.swal2-container {
    z-index: 99999 !important;
}
</style>
