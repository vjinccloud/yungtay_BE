<!-- Modules/ProductSpecSetting/Vue/GroupForm.vue -->
<!-- 商品規格設定 - 規格群組 新增/編輯頁面 -->
<template>
    <div class="content">
        <BreadcrumbItem />

        <div class="block block-rounded">
            <div class="block-header block-header-default">
                <h3 class="block-title">{{ isEdit ? '編輯' : '新增' }}規格群組</h3>
                <div class="block-options">
                    <Link
                        :href="route('admin.product-spec-settings.index')"
                        class="btn btn-sm btn-secondary"
                    >
                        <i class="fa fa-arrow-left me-1"></i>
                        返回列表
                    </Link>
                </div>
            </div>

            <div class="block-content block-content-full">
                <form @submit.prevent="submit">

                    <!-- ========== 群組名稱（多語系） ========== -->
                    <div class="mb-4">
                        <TranslatableInput
                            v-model="form.name"
                            label="群組名稱"
                            placeholder="例如：顏色、大小、材質"
                            :required="true"
                            :errors="form.errors"
                            errorPrefix="name"
                        />
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
                                class="form-check-input"
                                type="checkbox"
                                id="statusSwitch"
                            >
                            <label class="form-check-label" for="statusSwitch">
                                {{ form.status ? '啟用' : '停用' }}
                            </label>
                        </div>
                    </div>

                    <hr>

                    <!-- ========== 規格值列表 ========== -->
                    <div class="mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <label class="form-label mb-0">
                                <i class="fa fa-tags me-1"></i>
                                規格值
                            </label>
                            <button
                                type="button"
                                class="btn btn-sm btn-alt-primary"
                                @click="addValue"
                            >
                                <i class="fa fa-plus me-1"></i>
                                新增規格值
                            </button>
                        </div>

                        <div v-if="form.values.length === 0" class="text-center text-muted py-3 bg-light rounded">
                            <i class="fa fa-info-circle me-1"></i>
                            尚無規格值，請點擊「新增規格值」
                        </div>

                        <div v-else>
                            <div
                                v-for="(val, index) in form.values"
                                :key="index"
                                class="spec-value-row mb-2 p-3 bg-light rounded"
                            >
                                <div class="row align-items-end">
                                    <div class="col-md-8">
                                        <TranslatableInput
                                            v-model="val.name"
                                            label="規格值"
                                            placeholder="例如：紅色、M、棉質"
                                            :required="true"
                                            :errors="form.errors"
                                            :errorPrefix="`values.${index}.name`"
                                            size="sm"
                                        />
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label small">排序</label>
                                        <input
                                            v-model.number="val.seq"
                                            type="number"
                                            class="form-control form-control-sm"
                                            min="0"
                                        >
                                    </div>
                                    <div class="col-md-2 text-end">
                                        <div class="d-flex align-items-center justify-content-end gap-2">
                                            <div class="form-check form-switch mb-0">
                                                <input
                                                    v-model="val.status"
                                                    class="form-check-input"
                                                    type="checkbox"
                                                    :id="'valStatus' + index"
                                                >
                                            </div>
                                            <button
                                                type="button"
                                                class="btn btn-sm btn-outline-danger"
                                                @click="removeValue(index)"
                                                title="移除"
                                            >
                                                <i class="fa fa-times"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <hr>

                    <!-- ========== 提交按鈕 ========== -->
                    <div class="mb-4">
                        <button
                            type="submit"
                            class="btn btn-primary"
                            :disabled="form.processing"
                        >
                            <i class="fa fa-save me-1"></i>
                            {{ form.processing ? '儲存中...' : (isEdit ? '更新' : '新增') }}
                        </button>
                        <Link
                            :href="route('admin.product-spec-settings.index')"
                            class="btn btn-secondary ms-2"
                        >
                            取消
                        </Link>
                    </div>

                </form>
            </div>
        </div>
    </div>
</template>

<script>
import { ref } from "vue";
import { Link, useForm, usePage } from "@inertiajs/vue3";
import Layout from "@/Shared/Admin/Layout.vue";
import BreadcrumbItem from "@/Shared/Admin/Partials/BreadcrumbItem.vue";
import TranslatableInput from "@/Shared/Admin/Components/TranslatableInput.vue";

export default {
    components: { BreadcrumbItem, Link, TranslatableInput },
    props: {
        data: { type: Object, default: null },
        isEdit: { type: Boolean, default: false },
    },
    setup(props) {
        const page = usePage();
        const locales = page.props.translatableLocales || { zh_TW: { label: '中文' } };
        const localeKeys = Object.keys(locales);

        // 根據 config 動態建立多語系物件
        const buildTranslatable = (source) => {
            const obj = {};
            localeKeys.forEach(k => { obj[k] = source?.[k] || ''; });
            return obj;
        };

        const formData = props.data || {};

        const form = useForm({
            name: buildTranslatable(formData.name),
            seq: formData.seq ?? 0,
            status: formData.status ?? true,
            values: (formData.values || []).map(v => ({
                id: v.id || null,
                name: buildTranslatable(v.name),
                seq: v.seq ?? 0,
                status: v.status ?? true,
            })),
        });

        const addValue = () => {
            form.values.push({
                id: null,
                name: buildTranslatable(null),
                seq: form.values.length,
                status: true,
            });
        };

        const removeValue = (index) => {
            form.values.splice(index, 1);
        };

        const submit = () => {
            if (props.isEdit) {
                form.put(route('admin.product-spec-settings.groups.update', props.data.id));
            } else {
                form.post(route('admin.product-spec-settings.groups.store'));
            }
        };

        return {
            form,
            addValue,
            removeValue,
            submit,
        };
    },
    layout: Layout,
};
</script>

<style scoped>
.spec-value-row {
    border: 1px solid #e4e7ed;
    transition: border-color 0.15s;
}
.spec-value-row:hover {
    border-color: #adb5bd;
}
</style>
