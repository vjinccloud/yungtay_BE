<template>
    <div class="form-keyin-div">
        <form id="forgot-password-form" @submit.prevent="onSubmit">
            <div class="item">                                    
                <div class="controller">
                    <input 
                        type="email" 
                        v-model="form.email"
                        name="email"
                        :placeholder="props.texts.placeholderEmail"
                        :disabled="isLoading"
                        required
                    >
                    <div class="errorTxt"></div>
                </div>
            </div>
            
                                         
            <div class="item action had-two-btns">
                <button 
                    class="btn-black" 
                    type="button"
                    @click="handleCancel"
                    :disabled="isLoading"
                >
                    {{ props.texts.cancel }}
                </button>
                <button 
                    class="btn-blue" 
                    type="submit"
                    :disabled="isLoading"
                >
                    {{ isLoading ? props.texts.processing : props.texts.confirm }}
                </button>
            </div>                                 
        </form>
    </div>        
</template>

<script setup>
import { ref, inject, onMounted } from 'vue';
import { useFormValidation } from '@/composables/frontend/useFormValidation';

// Props
const props = defineProps({
    texts: {
        type: Object,
        default: () => ({
            placeholderEmail: '請輸入 Email',
            cancel: '取消',
            confirm: '確認',
            processing: '處理中...',
            sending: '發送中...',
            sendFailed: '發送失敗，請稍後再試',
            checkFormFields: '請檢查表單欄位',
            validationRequired: '此欄位必填',
            validationEmail: '請輸入有效的Email格式'
        })
    }
});

// 注入服務
const $http = inject('$http');
const $loading = inject('$loading');
const $sweetAlert = inject('$sweetAlert');

// 使用表單驗證
const { setupFormValidation } = useFormValidation();

// 表單資料
const form = ref({
    email: ''
});

// 狀態
const isLoading = ref(false);

// 設定表單驗證規則
const initFormValidation = async () => {
    await setupFormValidation('#forgot-password-form', {
        rules: {
            email: {
                required: true,
                email: true
            }
        },
        messages: {
            email: {
                required: props.texts.validationRequired,
                email: props.texts.validationEmail
            }
        },
        submitHandler: handleSubmit
    });
};

// Vue 表單提交處理
const onSubmit = (event) => {
    event.preventDefault();
    if (window.$ && $('#forgot-password-form').data('validator')) {
        // 觸發 jQuery Validation 驗證，驗證通過會自動調用 submitHandler
        if ($('#forgot-password-form').valid()) {
            // 驗證通過，submitHandler 會自動執行 handleSubmit
        }
    } else {
        // 沒載到 jQuery Validate 就直接送
        handleSubmit();
    }
};

// 實際提交處理
const handleSubmit = async () => {
    
    isLoading.value = true;
    $loading.showLoading(props.texts.sending);
    
    try {
        const response = await $http.post('/member/forgot-password', {
            email: form.value.email
        });
        
        // 使用 resultData 統一處理結果
        const result = response.data || {
            status: false,
            msg: props.texts.sendFailed
        }
        
        $sweetAlert.resultData(result, null, () => {
            if (result.redirect) {
                window.location.href = result.redirect
            }
        })
    } catch (error) {
        if (error.response?.status === 422) {
            // Laravel 驗證錯誤格式 - 使用jQuery顯示錯誤
            if (error.response.data.errors && window.$) {
                const backendErrors = error.response.data.errors
                Object.keys(backendErrors).forEach(field => {
                    const element = $(`[name="${field}"]`)
                    if (element.length > 0) {
                        element.closest('.controller').find('.errorTxt').html(`<span>${backendErrors[field][0]}</span>`)
                        element.addClass('error')
                    }
                })
                $sweetAlert.showToast(props.texts.checkFormFields, 'error')
            } else {
                // 處理其他 422 錯誤（非欄位驗證錯誤）
                $sweetAlert.resultData({
                    status: false,
                    msg: error.response.data.message || props.texts.sendFailed
                })
            }
        } else if (error.response?.status === 404) {
            // Email 不存在錯誤 - 顯示在欄位下方
            if (window.$) {
                const element = $('[name="email"]');
                element.closest('.controller').find('.errorTxt').html(`<span>${error.response.data.msg}</span>`);
                element.addClass('error');
            }
        } else {
            $sweetAlert.resultData({
                status: false,
                msg: error.response?.data?.msg || props.texts.sendFailed
            })
        }
    } finally {
        isLoading.value = false;
        $loading.hideLoading();
    }
};

// 取消
const handleCancel = () => {
    // 導回登入頁面
    window.location.href = '/member/login';
};

// 組件掛載時初始化驗證
onMounted(() => {
    initFormValidation();
});
</script>


