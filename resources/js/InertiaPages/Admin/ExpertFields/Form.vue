<!-- resources/js/InertiaPages/Admin/ExpertFields/Form.vue -->
<template>
    <div class="content">
        <BreadcrumbItem />

        <div class="block block-rounded">
            <div class="block-header block-header-default">
                <h3 class="block-title">
                    <Link class="btn btn-sm btn-alt-secondary" :href="route('admin.expert-fields')">
                        <i class="fa fa-arrow-left me-1"></i>
                        返回列表
                    </Link>
                </h3>
            </div>

            <div class="block-content block-content-full">
                <form @submit.prevent="submit">
                    <!-- 領域名稱 -->
                    <div class="mb-4">
                        <label class="form-label">領域名稱 <span class="text-danger">*</span></label>
                        <input
                            v-model="form.name.zh_TW"
                            type="text"
                            class="form-control"
                            :class="{'is-invalid': form.errors['name.zh_TW']}"
                            placeholder="請輸入領域名稱"
                            required
                        >
                        <div v-if="form.errors['name.zh_TW']" class="invalid-feedback">
                            {{ form.errors['name.zh_TW'] }}
                        </div>
                    </div>

                    <!-- 啟用狀態 -->
                    <div class="mb-4">
                        <div class="form-check form-switch">
                            <input
                                v-model="form.is_active"
                                class="form-check-input"
                                type="checkbox"
                                id="is_active"
                            >
                            <label class="form-check-label" for="is_active">
                                啟用狀態
                            </label>
                        </div>
                    </div>

                    <!-- 送出按鈕 -->
                    <div class="text-end">
                        <button type="button" class="btn btn-secondary me-2" @click="back">
                            回上一頁
                        </button>
                        <button type="submit" class="btn btn-primary" :disabled="form.processing">
                            <span v-if="form.processing">
                                <i class="fa fa-spinner fa-spin me-1"></i>處理中...
                            </span>
                            <span v-else>
                                <i class="fa fa-save me-1"></i>儲存
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup>
import { inject } from 'vue'
import { useForm, Link } from '@inertiajs/vue3'
import Layout from "@/Shared/Admin/Layout.vue";
import BreadcrumbItem from "@/Shared/Admin/Partials/BreadcrumbItem.vue";

const props = defineProps({
    field: {
        type: Object,
        default: null
    }
})

const sweetAlert = inject('$sweetAlert');

const form = useForm({
    name: {
        zh_TW: props.field?.name?.zh_TW || '',
    },
    is_active: props.field?.is_active ?? true,
});

const submit = () => {
    const url = props.field?.id
        ? route('admin.expert-fields.update', props.field.id)
        : route('admin.expert-fields.store');
    const method = props.field?.id ? 'put' : 'post';

    form[method](url, {
        preserveScroll: true,
        onSuccess: () => {
            sweetAlert.showToast('儲存成功', 'success');
        },
        onError: () => {
            sweetAlert.error({ msg: '儲存失敗，請檢查欄位' });
        }
    });
};

const back = () => window.history.back();
</script>

<script>
export default { layout: Layout };
</script>
