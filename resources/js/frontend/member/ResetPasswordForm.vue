<template>
    <div class="form-keyin-div">
        <form id="reset-password-form" @submit.prevent="onSubmit">
            <!-- 隱藏欄位 -->
            <input type="hidden" v-model="form.token" name="token">
            <input type="hidden" v-model="form.email" name="email">
            <!-- 無障礙用戶名欄位（隱藏） -->
            <input type="email" v-model="form.email" name="username" autocomplete="username" style="display: none;" tabindex="-1">
            
            <div class="item">                                    
                <div class="controller">
                    <div class="password-toggle">
                        <input 
                            :type="showPassword ? 'text' : 'password'" 
                            v-model="form.password"
                            name="password"
                            :placeholder="props.texts.placeholderNewPassword"
                            autocomplete="new-password"
                            :disabled="isLoading"
                            required
                        >
                        <i @click="togglePassword"></i>
                    </div>
                    <div class="errorTxt"></div>
                </div>
            </div>
            
            <div class="item">                                    
                <div class="controller">
                    <div class="password-toggle">
                        <input 
                            :type="showConfirmPassword ? 'text' : 'password'" 
                            v-model="form.password_confirmation"
                            name="password_confirmation"
                            :placeholder="props.texts.placeholderConfirmPassword"
                            autocomplete="new-password"
                            :disabled="isLoading"
                            required
                        >
                        <i @click="toggleConfirmPassword"></i>
                    </div>
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
                    {{ isLoading ? props.texts.resetProcessing : props.texts.resetBtn }}
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
    token: {
        type: String,
        required: true
    },
    email: {
        type: String,
        required: true
    },
    texts: {
        type: Object,
        default: () => ({
            placeholderNewPassword: '請輸入新密碼',
            placeholderConfirmPassword: '請再次輸入新密碼',
            cancel: '取消',
            resetBtn: '確認重設',
            resetProcessing: '重設中...',
            confirmTitle: '確認重設密碼?',
            loadingMessage: '重設中...',
            checkFormFields: '請檢查表單欄位',
            tooManyRequests: '請求次數過多，請稍後再試',
            resetFailed: '重設失敗，請稍後再試',
            checkNetwork: '請檢查您的網路連線或稍後再試',
            validationRequired: '密碼為必填欄位',
            validationPasswordFormat: '請輸入6-16位英數碼',
            validationConfirmRequired: '請確認密碼',
            validationPasswordMatch: '兩次密碼輸入不一致'
        })
    }
});

// 服務注入
const $http = inject('$http');
const $loading = inject('$loading');
const $sweetAlert = inject('$sweetAlert');

// 使用表單驗證 composable
const { setupFormValidation } = useFormValidation();

// 表單資料
const form = ref({
    token: props.token,
    email: props.email,
    password: '',
    password_confirmation: ''
});

// 狀態管理
const isLoading = ref(false);
const showPassword = ref(false);
const showConfirmPassword = ref(false);

// 表單驗證設定
const initFormValidation = async () => {
    await setupFormValidation('#reset-password-form', {
        customMethods: {
            passwordFormat: {
                validator: function(value, element) {
                    if (this.optional(element)) return true;
                    if (value.length < 6 || value.length > 16) return false;
                    return /[a-zA-Z]/.test(value) && /[0-9]/.test(value);
                },
                message: props.texts.validationPasswordFormat
            }
        },
        rules: {
            password: {
                required: true,
                passwordFormat: true
            },
            password_confirmation: {
                required: true,
                equalTo: '[name="password"]'
            }
        },
        messages: {
            password: {
                required: props.texts.validationRequired,
                passwordFormat: props.texts.validationPasswordFormat
            },
            password_confirmation: {
                required: props.texts.validationConfirmRequired,
                equalTo: props.texts.validationPasswordMatch
            }
        },
        submitHandler: handleSubmit // 驗證通過後執行
    });
};

// 表單提交處理
const onSubmit = (event) => {
    event.preventDefault();
    if (window.$ && $('#reset-password-form').data('validator')) {
        // 觸發驗證，通過後會自動調用 submitHandler
        $('#reset-password-form').valid();
    } else {
        // Fallback：直接提交
        handleSubmit();
    }
};

// 實際提交邏輯
const handleSubmit = async () => {
    // 顯示確認對話框
    $sweetAlert.confirm(
        props.texts.confirmTitle,
        async () => {
            // 確認後才執行密碼重設
            isLoading.value = true;
            $loading.showLoading(props.texts.loadingMessage);
            
            try {
                const response = await $http.post('/member/reset-password', form.value);
                
                // 使用 SweetAlert2 處理結果
                $sweetAlert.resultData(response.data, null, () => {
                    if (response.data.redirect) {
                        window.location.href = response.data.redirect;
                    }
                });
                
            } catch (error) {
                handleError(error);
            } finally {
                isLoading.value = false;
                $loading.hideLoading();
            }
        }
    );
};

// 錯誤處理
const handleError = (error) => {
    if (error.response?.status === 422) {
        // 後端驗證錯誤 - 顯示在欄位下方
        const backendErrors = error.response.data.errors || {};
        
        // 處理一般驗證錯誤
        Object.keys(backendErrors).forEach(field => {
            const element = $(`[name="${field}"]`);
            if (element.length > 0) {
                element.closest('.controller').find('.errorTxt')
                    .html(`<span>${backendErrors[field][0]}</span>`);
                element.addClass('error');
            }
        });
        
        // 處理自訂驗證錯誤（如密碼重複檢查）
        if (error.response.data.message) {
            // 如果是密碼相關錯誤，顯示在密碼欄位
            const passwordElement = $('[name="password"]');
            if (passwordElement.length > 0) {
                passwordElement.closest('.controller').find('.errorTxt')
                    .html(`<span>${error.response.data.message}</span>`);
                passwordElement.addClass('error');
            }
        }
        
        $sweetAlert.showToast(props.texts.checkFormFields, 'error');
        
    } else if (error.response?.status === 429) {
        // 頻率限制
        $sweetAlert.showToast(props.texts.tooManyRequests, 'warning');
        
    } else {
        // 其他系統錯誤 - 使用彈窗
        $sweetAlert.resultData({
            status: false,
            msg: error.response?.data?.msg || props.texts.resetFailed
        });
    }
};

// 取消
const handleCancel = () => {
    window.location.href = '/member/login';
};

// 密碼顯示切換
const togglePassword = () => {
    showPassword.value = !showPassword.value;
};

const toggleConfirmPassword = () => {
    showConfirmPassword.value = !showConfirmPassword.value;
};

// 組件掛載
onMounted(() => {
    initFormValidation();
});
</script>

