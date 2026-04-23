<template>
    <div class="modal fade"
    id="modal-profile"
    tabindex="-1"
    role="dialog"
    aria-labelledby="modal-profile-title"
    data-bs-backdrop="static"
    data-bs-keyboard="false"
    ref="profileForm">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="block block-rounded shadow-none mb-0">
                    <div class="block-header block-header-default">
                        <h3 class="block-title" id="modal-profile-title">編輯個人資料</h3>
                        <div class="block-options">
                            <button
                                type="button"
                                class="btn-block-option"
                                data-bs-dismiss="modal"
                                aria-label="Close"
                            >
                            <i class="fa fa-times" aria-hidden="true"></i>
                            </button>
                        </div>
                    </div>
                    <div class="block-content fs-sm">
                        <form @submit.prevent="submitForm">
                            <div class="row push">
                                <div class="col-lg-12 col-xl-12">
                                    <div class="mb-4">
                                        <label class="form-label" for="example-text-input" >個人圖像<span class="text-danger"></span></label>
                                        <div class="push slim-img-profile"  wire:ignore>
                                            <Slim
                                            :label="'個人圖像'"
                                            class="slim"
                                            :width="128"
                                            :height="128"
                                            :initialImage="adminImg"
                                            :key="`slim-${slimKey}-${adminImg}`"
                                            ref="imgSlim"
                                            />
                                        </div>
                                    </div>
                                    <div class="mb-4">
                                        <label class="form-label" for="example-text-input" >帳號(Email)<span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" placeholder="帳號(Email)" autocomplete="email" disabled :value="form.email">
                                    </div>
                                    <div class="mb-4">
                                        <label class="form-label" for="example-text-input" >名稱<span class="text-danger">*</span></label>
                                        <input type="text" class="form-control"
                                            placeholder="管理員名稱"
                                            autocomplete="name"
                                            :class="form.errors.name?'parsley-error':''"
                                            @blur="validator.singleField('name')"
                                            v-model="form.name"
                                        >
                                        <div v-if="form.errors.name" class="text-danger  ml-2">
                                        {{ form.errors.name }}
                                        </div>
                                    </div>
                                    <div class="mb-4">
                                        <label class="form-label" for="password">密碼
                                            <span class="text-danger"></span>
                                        </label>
                                        <div class="input-group">
                                            <input
                                                :type="showPassword ? 'text' : 'password'"
                                                class="form-control"
                                                id="password"
                                                v-model="form.password"
                                                placeholder="密碼"
                                                autocomplete="new-password"
                                                :class="form.errors.password?'parsley-error':''"
                                                @blur="validator.singleField('password')"
                                            >
                                            <button class="btn btn-alt-secondary" type="button" @click="showPassword = !showPassword" tabindex="-1">
                                                <i :class="showPassword ? 'fa fa-eye-slash' : 'fa fa-eye'"></i>
                                            </button>
                                        </div>
                                        <div v-if="form.errors.password" class="text-danger ml-2">
                                            {{ form.errors.password }}
                                        </div>
                                    </div>
                                    <div class="mb-4">
                                        <label class="form-label" for="confirm-password">再次確認密碼
                                            <span class="text-danger"></span>
                                        </label>
                                        <div class="input-group">
                                            <input
                                                :type="showPasswordConfirm ? 'text' : 'password'"
                                                class="form-control"
                                                id="confirm-password"
                                                placeholder="再次確認密碼"
                                                autocomplete="new-password"
                                                v-model="form.password_confirmation"
                                                :class="form.errors.password_confirmation?'parsley-error':''"
                                                @blur="validator.singleField('password_confirmation')"
                                            >
                                            <button class="btn btn-alt-secondary" type="button" @click="showPasswordConfirm = !showPasswordConfirm" tabindex="-1">
                                                <i :class="showPasswordConfirm ? 'fa fa-eye-slash' : 'fa fa-eye'"></i>
                                            </button>
                                        </div>
                                        <div v-if="form.errors.password_confirmation" class="text-danger ml-2">
                                            {{ form.errors.password_confirmation }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="block-content block-content-full block-content-sm text-end border-top">
                        <button type="button" class="btn btn-alt-secondary me-2" data-bs-dismiss="modal">
                            關閉
                        </button>
                        <button type="button" class="btn btn-primary" @click="submitForm" :disabled="form.processing">
                                <i class="fa fa-save me-1"></i>
                                儲存
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
    import { useForm, usePage, router } from "@inertiajs/vue3";
    import { ref , nextTick, inject, onMounted } from "vue";
    import { Modal } from "bootstrap";
    import { useSubmitForm ,FormValidator } from '@/utils/index.js';
    import Slim from '@/Plugin/Slim.vue';
    const slimKey = ref(0);
    const showPassword = ref(false);
    const showPasswordConfirm = ref(false);
    const { submitForm: performSubmit } = useSubmitForm();


    const isLoading = inject('isLoading');

    const props = defineProps({
        auth: Object,
    });
    const imgSlim = ref(null);
    // 页面数据
    const page = usePage();

    // 使用 useForm 初始化表单
    const form = useForm('ProfileForm',{
        id: '',
        email: '',
        name: '',
        password: '',
        password_confirmation: '',
        slim:null,
        confirm:false,
        routeUrl:'',
        component:'',
    });



    // 定义验证规则
    const getRules = () => ({
      name: ['required', 'string', ['max', 255]],
      password: [
          '' ,
          form.password ?['min', 8]:'',
          form.password ?['regex', /^(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9]).{8,}$/, '密码必须包含大小写字母和数字']:'',
      ],
      password_confirmation: [
          form.password  ? 'required' : '', // 新增模式必须验证密码确认
          form.password_confirmation ?['confirmed', 'password', '密碼不對']:'',
      ],
    });

    const validator = new FormValidator(form, getRules);

    // 提交表单
    const submitForm = async () => {

        form.component = page.component;
        form.routeUrl = page.url;
        form.slim = imgSlim.value?.slimImg.$el.querySelector('input[name="slim"]').value;
        //isLoading.value = true;
        form.confirm = false;
        const url =  route('admin.edit-profile-form');
        const method = 'put';
        const hasErrors = await validator.hasErrors();
        if (!hasErrors) {
            performSubmit({ form, url, method });
        }

    };

    router.on('finish', () => {
        isLoading.value = false;
    });




    // 模态框的相关逻辑
    const profileForm = ref(null); // 使用 modal 作为模态框的 ref
    let myModal = null; // 存储模态框实例
    const adminImg = ref(null);
    const openModal = () => {
        adminImg.value = props.auth.user.img || '/media/avatars/avatar15.jpg';
        form.id =  props.auth.user.id || '';
        form.email = props.auth.user.email || '';
        form.name =  props.auth.user.name || '';
        form.password =  '';
        form.password_confirmation = '';
        form.component = '';
        form.routeUrl = '';
        form.slim = null;
        form.confirm = false;
        nextTick(() => {
          myModal.show();
        });
    };

    const reset = ()=>{
      form.id = '';
      form.email = '';
      form.name = '';
      form.password= '',
      form.password_confirmation= '',
      form.confirm = false;
      form.slim = '';
    }

    const closeModal = () => {
        //reset();
        form.clearErrors();
        // 確保移除焦點再隱藏Modal
        if (document.activeElement && document.activeElement.blur) {
            document.activeElement.blur();
        }
        if (myModal) {
            nextTick(() => {
                myModal.hide();
            });
        }
    };



    onMounted(() => {
        // 初始化模态框
        if (!myModal) {
            myModal = new Modal(profileForm.value, {
                backdrop: "static",
                keyboard: false,
            });
            
            // 監聽Modal隱藏前事件，確保焦點處理
            profileForm.value.addEventListener('hide.bs.modal', (event) => {
                // 移除任何可能的焦點
                if (document.activeElement && document.activeElement.blur) {
                    document.activeElement.blur();
                }
            });
        }
    });
    defineExpose({
        openModal,
        closeModal,
    });

    // 从页面获取角色
    const roles = page.props.roles;

</script>
