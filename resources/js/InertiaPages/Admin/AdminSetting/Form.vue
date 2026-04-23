<template>
  <div class="content">
    <BreadcrumbItem />

    <div class="block block-rounded">
      <div class="block-header block-header-default">
        <h3 class="block-title">
          <Link class="btn btn-sm btn-alt-secondary" :href="route('admin.admin-settings')">
            <i class="fa fa-arrow-left me-1"></i>
            上一頁
          </Link>
        </h3>
      </div>

      <div class="block-content block-content-full">
        <form>
                    <!-- Email -->
                    <div class="mb-4">
                        <label class="form-label" for="admin-email">
                        員工帳號<span class="text-danger">*</span>
                        </label>
                        <input
                        type="email"
                        class="form-control"
                        id="admin-email"
                        v-model="form.email"
                        placeholder="請輸入員工Email"
                        autocomplete="username"
                        :disabled="!!props.adminUser"
                        :class="{ 'parsley-error': form.errors.email }"

                        />
                        <div v-if="form.errors.email" class="text-danger  ml-2">
                        {{ form.errors.email }}
                        </div>
                    </div>
                    <!-- Name -->
                    <div class="mb-4">
                        <label class="form-label" for="admin-name">
                        員工名稱<span class="text-danger">*</span>
                        </label>
                        <input
                        type="text"
                        class="form-control"
                        id="admin-name"
                        v-model="form.name"
                        placeholder="請輸入員工名稱"
                        :class="form.errors.name?'parsley-error':''"
                         @blur="validator.singleField('name')"
                        />
                        <div v-if="form.errors.name" class="text-danger">
                        {{ form.errors.name }}
                        </div>
                    </div>
                    <!-- Password -->
                    <div class="mb-4">
                        <label class="form-label" for="admin-password">密碼<span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input
                            :type="showPassword ? 'text' : 'password'"
                            class="form-control"
                            id="admin-password"
                            v-model="form.password"
                            placeholder="輸入密碼"
                            autocomplete="new-password"
                            :class="form.errors.password?'parsley-error':''"
                            @blur="validator.singleField('password')"
                            />
                            <button class="btn btn-alt-secondary" type="button" @click="showPassword = !showPassword" tabindex="-1">
                                <i :class="showPassword ? 'fa fa-eye-slash' : 'fa fa-eye'"></i>
                            </button>
                        </div>
                        <div v-if="form.errors.password" class="text-danger">
                        {{ form.errors.password }}
                        </div>
                    </div>
                    <!-- Confirm Password -->
                    <div class="mb-4">
                        <label class="form-label" for="admin-confirm-password">
                        再次確認密碼<span class="text-danger">*</span>
                        </label>
                        <div class="input-group">
                            <input
                            :type="showPasswordConfirm ? 'text' : 'password'"
                            class="form-control"
                            id="admin-confirm-password"
                            v-model="form.password_confirmation"
                            placeholder="再次確認密碼"
                            autocomplete="new-password"
                            :class="form.errors.password_confirmation?'parsley-error':''"
                            @blur="validator.singleField('password_confirmation')"
                            />
                            <button class="btn btn-alt-secondary" type="button" @click="showPasswordConfirm = !showPasswordConfirm" tabindex="-1">
                                <i :class="showPasswordConfirm ? 'fa fa-eye-slash' : 'fa fa-eye'"></i>
                            </button>
                        </div>
                        <div v-if="form.errors.password_confirmation" class="text-danger">
                        {{ form.errors.password_confirmation }}
                        </div>
                    </div>
                    <!-- Role -->
                    <div class="mb-4">
                        <label class="form-label" for="role">角色權限<span class="text-danger">*</span></label>
                        <select class="form-select" id="role" v-model="form.role_id"
                         :class="form.errors.role_id?'parsley-error':''"
                        @blur="validator.singleField('role_id')"
                        >
                        <option value="">請選擇角色權限</option>
                        <option
                            v-for="role in roles"
                            :key="role.id"
                            :value="role.id"
                        >
                            {{ role.name }}
                        </option>
                        </select>
                        <div v-if="form.errors.role_id" class="text-danger">
                        {{ form.errors.role_id }}
                        </div>
                    </div>

                    <!-- 送出按鈕 -->
                    <div class="text-end">
                        <button
                            type="button"
                            class="btn btn-secondary me-2"
                            @click="back"
                        >
                            回上一頁
                        </button>
                        <button
                            type="button"
                            class="btn btn-primary"
                            :disabled="form.processing"
                            @click="submit"
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
    import { ref, inject } from "vue";
    import { useForm, Link } from "@inertiajs/vue3";
    import Layout from "@/Shared/Admin/Layout.vue";
    import BreadcrumbItem from "@/Shared/Admin/Partials/BreadcrumbItem.vue";
    import { FormValidator, useSubmitForm } from '@/utils/index.js';

    const sweetAlert = inject('$sweetAlert');
    // 編輯模式時接收 props
    const props = defineProps({
        adminUser: {
            type: Object,
            default: null
        },
        roles: {
            type: Array,
            default: () => []
        }
    });

    // 引入 submitForm 方法
    const { submitForm: performSubmit } = useSubmitForm();

    const showPassword = ref(false); // 控制密碼顯示/隱藏
    const showPasswordConfirm = ref(false); // 控制確認密碼顯示/隱藏

    // 使用 useForm 初始化表单
    const form = useForm({
        id: props.adminUser?.id || '',
        email: props.adminUser?.email || '',
        name: props.adminUser?.name || '',
        password: '',
        password_confirmation: '',
        role_id: props.adminUser?.role_id || '',
        confirm: false
    });



    // 定义验证规则
    const getRules = () => ({
        email: ['required', 'email'],
        name: ['required', 'string', ['max', 255]],
        password: [
            props.adminUser ? 'nullable' : 'required',
            ['min', 8],
            ['regex', /^(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9]).{8,}$/, '密碼需包含大小寫與數字'],
        ],
        password_confirmation: [
            props.adminUser ? 'nullable' : 'required',
            ['confirmed', 'password', '密碼確認不匹配'],
        ],
        role_id: ['required'],
    });


    const validator = new FormValidator(form, getRules);
    // 提交表单
    const submit = async () => {
        try {
            form.clearErrors();
            form.confirm = false;

            const url = props.adminUser?.id
                ? route('admin.admin-settings.update', props.adminUser.id)
                : route('admin.admin-settings.store');
            const method = props.adminUser?.id ? 'put' : 'post';

            const hasErrors = await validator.hasErrors();
            if (!hasErrors) {
                performSubmit({ form, url, method });
            } else {
                sweetAlert.error({
                    msg: '提交失敗，請檢查是否有欄位錯誤！'
                });
            }
        } catch (error) {
            console.error('提交失敗:', error);
            sweetAlert.error({
                msg: '提交時發生錯誤，請重試！'
            });
        }
    };

    // 回上一頁
    const back = () => {
        window.history.back();
    };

</script>

<script>
export default {
    layout: Layout,
};
</script>



<style scoped>
/* Custom Modal Styles (if needed) */
</style>
