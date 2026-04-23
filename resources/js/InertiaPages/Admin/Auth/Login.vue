<template>
    <div class="hitachi-form-wrapper">
        <!-- 載入中元件 -->
        <Loading v-if="form.processing" />

        <form @submit.prevent="submit">
            <!-- 電子郵件欄位 -->
            <div class="hitachi-field mb-3">
                <div class="hitachi-input-group" :class="{ 'has-error': form.errors.username }">
                    <span class="hitachi-input-icon">
                        <i class="fas fa-envelope"></i>
                    </span>
                    <input 
                        v-model="form.username" 
                        type="email" 
                        class="hitachi-input"
                        id="login-username" 
                        name="login-username" 
                        placeholder="name.wang@hitachi.com"
                        autocomplete="username"
                        required
                        autofocus
                        :disabled="form.processing"
                        @keyup.enter="focusPassword"
                        @blur="validator.singleField('username')"
                        @input="form.errors.username && form.clearErrors('username')">
                </div>
                <div v-if="form.errors.username" class="hitachi-error">
                    {{ form.errors.username }}
                </div>
            </div>
            
            <!-- 密碼欄位 -->
            <div class="hitachi-field mb-4">
                <div class="hitachi-input-group" :class="{ 'has-error': form.errors.password }">
                    <span class="hitachi-input-icon">
                        <i class="fas fa-lock"></i>
                    </span>
                    <input 
                        ref="passwordInput"
                        v-model="form.password" 
                        :type="showPassword ? 'text' : 'password'" 
                        class="hitachi-input"
                        id="login-password" 
                        name="login-password" 
                        placeholder="••••••••"
                        autocomplete="current-password"
                        required
                        :disabled="form.processing"
                        @keyup.enter="submit"
                        @blur="validator.singleField('password')"
                        @input="form.errors.password && form.clearErrors('password')">
                    <button 
                        type="button" 
                        class="hitachi-eye-btn"
                        @click="showPassword = !showPassword"
                        :disabled="form.processing">
                        <i :class="showPassword ? 'fas fa-eye-slash' : 'fas fa-eye'"></i>
                    </button>
                </div>
                <div v-if="form.errors.password" class="hitachi-error">
                    {{ form.errors.password }}
                </div>
            </div>
            
            <!-- 登入按鈕 -->
            <div>
                <button 
                    type="submit" 
                    :disabled="form.processing" 
                    class="hitachi-login-btn"
                    :class="{ 'hitachi-login-btn-active': isFormValid }">
                    <span v-if="form.processing">
                        <i class="fas fa-spinner fa-spin me-2"></i>正在登入中...
                    </span>
                    <span v-else>登入</span>
                </button>
            </div>
        </form>
    </div>
</template>

<script setup>
    import { useForm } from '@inertiajs/vue3';
    import { ref, computed, onMounted, inject } from 'vue';
    import Loading from '@/Shared/Admin/Partials/Loading.vue';
    import { FormValidator } from '@/utils/index.js';
    
    // Props
    const props = defineProps({
        captchaUrl: String,
        websiteName: String,
        pageTitle: String,
    });
    
    // 注入 sweetAlert
    const sweetAlert = inject('$sweetAlert');
    
    // 響應式變數
    const showPassword = ref(false);
    const passwordInput = ref(null);
    
    // 表單初始化
    const form = useForm({
        'username': localStorage.getItem('admin_username') || '',
        'password': '',
        'remember': false,
    });
    
    // 驗證規則
    const getRules = () => ({
        username: ['required', 'email'],
        password: ['required', ['min', 8]],
    });
    
    // 表單驗證器
    const validator = new FormValidator(form, getRules);
    
    // 表單驗證狀態
    const isFormValid = computed(() => {
        return form.username && form.password;
    });
    
    // 聚焦輔助函數
    const focusPassword = () => {
        if (passwordInput.value) {
            passwordInput.value.focus();
        }
    };
    
    // 表單提交處理
    const submit = async () => {
        
        // 儲存帳號
        localStorage.setItem('admin_username', form.username);
        
        // 驗證表單
        const hasErrors = await validator.hasErrors();
        if (hasErrors) {
            setTimeout(() => {
                if (form.errors.username) {
                    document.getElementById('login-username')?.focus();
                } else if (form.errors.password && passwordInput.value) {
                    passwordInput.value.focus();
                }
            }, 100);
            return;
        }
        
        const url = '/admin/login';
        
        form.post(url, {
            preserveScroll: true,
            onError: (errors) => {
                setTimeout(() => {
                    if (errors.password && passwordInput.value) {
                        passwordInput.value.focus();
                    } else if (errors.username) {
                        document.getElementById('login-username')?.focus();
                    }
                }, 100);
            },
            onSuccess: () => {
                sweetAlert.showToast('登入成功，正在為您跳轉...', 'success');
            }
        });
    };
    
    // 自動聚焦到第一個空欄位
    onMounted(() => {
        if (!form.username) {
            document.getElementById('login-username')?.focus();
        } else if (!form.password) {
            passwordInput.value?.focus();
        }
    });
</script>

<style scoped>
.hitachi-form-wrapper {
    width: 100%;
}

.hitachi-field {
    width: 100%;
}

.hitachi-input-group {
    display: flex;
    align-items: center;
    background: #444;
    border-radius: 4px;
    overflow: hidden;
    border: 1px solid transparent;
    transition: border-color 0.2s;
}

.hitachi-input-group.has-error {
    border-color: #e74c3c;
}

.hitachi-input-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 44px;
    min-width: 44px;
    height: 44px;
    color: #aaa;
    font-size: 16px;
}

.hitachi-input {
    flex: 1;
    background: transparent;
    border: none;
    outline: none;
    color: #fff;
    font-size: 15px;
    padding: 10px 12px 10px 0;
    height: 44px;
}

.hitachi-input::placeholder {
    color: #888;
}

.hitachi-input:-webkit-autofill,
.hitachi-input:-webkit-autofill:hover,
.hitachi-input:-webkit-autofill:focus {
    -webkit-box-shadow: 0 0 0 30px #444 inset !important;
    -webkit-text-fill-color: #fff !important;
    transition: background-color 5000s ease-in-out 0s !important;
}

.hitachi-eye-btn {
    background: transparent;
    border: none;
    color: #aaa;
    cursor: pointer;
    padding: 0 14px;
    height: 44px;
    display: flex;
    align-items: center;
    font-size: 15px;
    transition: color 0.2s;
}

.hitachi-eye-btn:hover {
    color: #fff;
}

.hitachi-error {
    color: #e74c3c;
    font-size: 13px;
    margin-top: 6px;
    padding-left: 4px;
}

.hitachi-login-btn {
    width: 100%;
    background: #555;
    color: #fff;
    border: none;
    border-radius: 4px;
    padding: 12px 0;
    font-size: 16px;
    font-weight: 600;
    letter-spacing: 4px;
    cursor: pointer;
    transition: background 0.2s;
    margin-top: 8px;
}

.hitachi-login-btn:hover:not(:disabled) {
    background: #666;
}

.hitachi-login-btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.hitachi-login-btn-active {
    background: #8B2332 !important;
}

.hitachi-login-btn-active:hover:not(:disabled) {
    background: #a12a3c !important;
}
</style>
