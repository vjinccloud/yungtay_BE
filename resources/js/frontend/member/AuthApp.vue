<template>
  <div>
    <LoginForm v-show="currentForm === 'login'" />
    <RegisterForm v-show="currentForm === 'register'" />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import LoginForm from './LoginForm.vue'
import RegisterForm from './RegisterForm.vue'

const currentForm = ref('login')

onMounted(() => {
  // 根據當前網址設定初始表單
  const path = window.location.pathname
  currentForm.value = path.includes('/register') ? 'register' : 'login'
  
  // 暴露給全域使用
  window.vueApp = {
    get currentForm() { return currentForm.value },
    set currentForm(value) { currentForm.value = value }
  }
})
</script>