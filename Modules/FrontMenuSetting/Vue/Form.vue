<!-- Modules/FrontMenuSetting/Vue/Form.vue -->
<!-- 前台選單管理 - 新增/編輯頁面 -->
<template>
    <div class="content">
        <BreadcrumbItem />

        <div class="block block-rounded">
            <div class="block-header block-header-default">
                <h3 class="block-title">{{ isEdit ? '編輯' : '新增' }}前台選單</h3>
                <div class="block-options">
                    <Link 
                        :href="route('admin.front-menu-settings.index')" 
                        class="btn btn-sm btn-secondary"
                    >
                        <i class="fa fa-arrow-left me-1"></i>
                        返回列表
                    </Link>
                </div>
            </div>

            <div class="block-content block-content-full">
                <form @submit.prevent="submit">

                    <!-- ========== 父層選單 ========== -->
                    <div class="mb-4">
                        <label class="form-label">
                            父層選單
                            <span class="text-danger">*</span>
                        </label>
                        <div class="parent-select-wrapper" ref="dropdownRef">
                            <div
                                class="form-select d-flex align-items-center"
                                :class="{ 'is-invalid': form.errors.parent_id, 'show': showDropdown }"
                                @click="showDropdown = !showDropdown"
                                style="cursor: pointer; min-height: 38px;"
                            >
                                <template v-if="selectedParentOption">
                                    <i v-if="selectedParentOption.value === 0" class="fa fa-home text-muted me-2"></i>
                                    <i v-else class="fa fa-folder text-primary me-2"></i>
                                    <span>{{ selectedParentOption.labelText }}</span>
                                </template>
                                <span v-else class="text-muted">請選擇父層選單</span>
                            </div>
                            <div v-if="showDropdown" class="parent-dropdown shadow-sm">
                                <div
                                    v-for="option in parentOptions"
                                    :key="option.value"
                                    class="parent-dropdown-item d-flex align-items-center"
                                    :class="{ 'active': form.parent_id === option.value }"
                                    :style="{ paddingLeft: (12 + (option.level || 0) * 20) + 'px' }"
                                    @click="selectParent(option.value)"
                                >
                                    <i v-if="option.value === 0" class="fa fa-home text-muted me-2"></i>
                                    <template v-else>
                                        <span v-if="option.level > 0" class="tree-line me-1">└</span>
                                        <i class="fa fa-folder text-warning me-2" style="font-size: 0.85em;"></i>
                                    </template>
                                    <span>{{ option.labelText }}</span>
                                </div>
                            </div>
                        </div>
                        <div v-if="form.errors.parent_id" class="invalid-feedback d-block">
                            {{ form.errors.parent_id }}
                        </div>
                    </div>

                    <!-- ========== 選單名稱（多語系） ========== -->
                    <div class="mb-4">
                        <TranslatableInput
                            v-model="form.title"
                            label="選單名稱"
                            placeholder="請輸入名稱"
                            :required="true"
                            :errors="form.errors"
                            errorPrefix="title"
                        />
                    </div>

                    <!-- ========== 連結類型 ========== -->
                    <div class="mb-4">
                        <label class="form-label">
                            連結類型
                            <span class="text-danger">*</span>
                        </label>
                        <select
                            v-model="form.link_type"
                            class="form-select"
                            :class="{ 'is-invalid': form.errors.link_type }"
                        >
                            <option value="none">無連結（純分類）</option>
                            <option value="url">外部連結</option>
                            <option value="route">內部路由</option>
                            <option value="page">頁面</option>
                        </select>
                        <div v-if="form.errors.link_type" class="invalid-feedback">
                            {{ form.errors.link_type }}
                        </div>
                        <small class="text-muted">
                            <template v-if="form.link_type === 'none'">純分類項目，不產生連結</template>
                            <template v-else-if="form.link_type === 'url'">輸入完整的外部網址（含 https://）</template>
                            <template v-else-if="form.link_type === 'route'">輸入前台的路由路徑</template>
                            <template v-else-if="form.link_type === 'page'">輸入頁面路徑</template>
                        </small>
                    </div>

                    <!-- ========== 連結網址 ========== -->
                    <div class="mb-4" v-if="form.link_type !== 'none'">
                        <label class="form-label">
                            連結網址
                        </label>
                        <input
                            v-model="form.link_url"
                            type="text"
                            class="form-control"
                            :class="{ 'is-invalid': form.errors.link_url }"
                            :placeholder="linkUrlPlaceholder"
                        >
                        <div v-if="form.errors.link_url" class="invalid-feedback">
                            {{ form.errors.link_url }}
                        </div>
                    </div>

                    <!-- ========== 開啟方式 ========== -->
                    <div class="mb-4" v-if="form.link_type !== 'none'">
                        <label class="form-label">
                            開啟方式
                            <span class="text-danger">*</span>
                        </label>
                        <select
                            v-model="form.link_target"
                            class="form-select"
                            :class="{ 'is-invalid': form.errors.link_target }"
                        >
                            <option value="_self">同分頁開啟</option>
                            <option value="_blank">新分頁開啟</option>
                        </select>
                        <div v-if="form.errors.link_target" class="invalid-feedback">
                            {{ form.errors.link_target }}
                        </div>
                    </div>

                    <!-- ========== 圖標 ========== -->
                    <div class="mb-4">
                        <label class="form-label">圖標</label>
                        <div class="input-group">
                            <input
                                v-model="form.icon"
                                type="text"
                                class="form-control"
                                :class="{ 'is-invalid': form.errors.icon }"
                                placeholder="例如：fa fa-home"
                            >
                            <span class="input-group-text" v-if="form.icon">
                                <i :class="form.icon"></i>
                            </span>
                        </div>
                        <div v-if="form.errors.icon" class="invalid-feedback">
                            {{ form.errors.icon }}
                        </div>
                        <small class="text-muted">FontAwesome 圖標 class，例如 fa fa-home</small>
                    </div>

                    <!-- ========== 排序 ========== -->
                    <div class="mb-4">
                        <label class="form-label">排序</label>
                        <input
                            v-model.number="form.seq"
                            type="number"
                            class="form-control"
                            style="max-width: 150px;"
                            min="0"
                        >
                        <small class="text-muted">數字越小越前面</small>
                    </div>

                    <!-- ========== 啟用狀態 ========== -->
                    <div class="mb-4">
                        <div class="form-check form-switch">
                            <input
                                v-model="form.status"
                                type="checkbox"
                                class="form-check-input"
                                id="status"
                            >
                            <label class="form-check-label" for="status">
                                啟用
                            </label>
                        </div>
                    </div>

                    <!-- ========== 送出按鈕 ========== -->
                    <div class="text-end">
                        <Link 
                            :href="route('admin.front-menu-settings.index')" 
                            class="btn btn-secondary me-2"
                        >
                            取消
                        </Link>
                        <button
                            type="submit"
                            class="btn btn-primary"
                            :disabled="form.processing"
                        >
                            <span v-if="form.processing">
                                <i class="fa fa-spinner fa-spin me-1"></i>
                                處理中...
                            </span>
                            <span v-else>
                                <i class="fa fa-save me-1"></i>
                                儲存
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, inject, onMounted, onUnmounted } from 'vue'
import { useForm, Link, usePage } from '@inertiajs/vue3'
import Layout from "@/Shared/Admin/Layout.vue";
import BreadcrumbItem from "@/Shared/Admin/Partials/BreadcrumbItem.vue";
import TranslatableInput from "@/Shared/Admin/Components/TranslatableInput.vue";

const props = defineProps({
    data: {
        type: Object,
        default: null
    },
    isEdit: {
        type: Boolean,
        default: false
    },
    parentOptions: {
        type: Array,
        default: () => []
    }
});

const sweetAlert = inject('$sweetAlert');

// 多語系設定
const page = usePage();
const locales = page.props.translatableLocales || { zh_TW: { label: '中文' } };
const localeKeys = Object.keys(locales);

const buildTranslatable = (source) => {
    const obj = {};
    localeKeys.forEach(k => { obj[k] = source?.[k] || ''; });
    return obj;
};

// 父層選單下拉
const showDropdown = ref(false);
const dropdownRef = ref(null);

const selectedParentOption = computed(() => {
    return props.parentOptions.find(o => o.value === form.parent_id) || null;
});

const selectParent = (value) => {
    form.parent_id = value;
    showDropdown.value = false;
};

// 點擊外部關閉下拉
const handleClickOutside = (e) => {
    if (dropdownRef.value && !dropdownRef.value.contains(e.target)) {
        showDropdown.value = false;
    }
};
onMounted(() => document.addEventListener('click', handleClickOutside));
onUnmounted(() => document.removeEventListener('click', handleClickOutside));

// 表單資料
const form = useForm({
    parent_id: props.data?.parent_id ?? 0,
    title: buildTranslatable(props.data?.title),
    link_type: props.data?.link_type || 'none',
    link_url: props.data?.link_url || '',
    link_target: props.data?.link_target || '_self',
    icon: props.data?.icon || '',
    seq: props.data?.seq ?? 0,
    status: props.data?.status ?? true,
});

// 連結網址的 placeholder
const linkUrlPlaceholder = computed(() => {
    switch (form.link_type) {
        case 'url':
            return 'https://www.example.com';
        case 'route':
            return '/about';
        case 'page':
            return '/pages/about';
        default:
            return '';
    }
});

// 提交表單
const submit = () => {
    const url = props.isEdit 
        ? route('admin.front-menu-settings.update', props.data.id)
        : route('admin.front-menu-settings.store');
    const method = props.isEdit ? 'put' : 'post';

    form[method](url, {
        onSuccess: () => {
            sweetAlert.success({ msg: props.isEdit ? '更新成功' : '新增成功' });
        },
        onError: () => {
            sweetAlert.error({ msg: '儲存失敗，請檢查欄位' });
        }
    });
};

defineOptions({
    layout: Layout
});
</script>

<style scoped>
.parent-select-wrapper {
    position: relative;
}

.parent-dropdown {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    z-index: 1050;
    background: #fff;
    border: 1px solid #ced4da;
    border-top: none;
    border-radius: 0 0 6px 6px;
    max-height: 280px;
    overflow-y: auto;
}

.parent-dropdown-item {
    padding: 8px 12px;
    cursor: pointer;
    font-size: 0.9rem;
    transition: background 0.1s;
    border-bottom: 1px solid #f0f0f0;
}

.parent-dropdown-item:last-child {
    border-bottom: none;
}

.parent-dropdown-item:hover {
    background: #e9ecef;
}

.parent-dropdown-item.active {
    background: #0d6efd;
    color: #fff;
}

.parent-dropdown-item.active i {
    color: #fff !important;
}

.parent-dropdown-item.active .tree-line {
    color: #fff !important;
}

.tree-line {
    color: #adb5bd;
    font-family: monospace;
    font-size: 0.85em;
}
</style>