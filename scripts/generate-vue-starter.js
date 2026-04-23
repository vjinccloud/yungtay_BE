#!/usr/bin/env node

import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

// 取得命令列參數
const args = process.argv.slice(2);
const componentType = args[0]; // 'form' 或 'index'
const componentName = args[1]; // 組件名稱，如 'ModuleDescription'

if (!componentType || !componentName) {
    console.error('用法: node generate-vue-starter.js <form|index> <ComponentName>');
    console.error('範例: node generate-vue-starter.js form ModuleDescription');
    process.exit(1);
}

// 組件目錄
const componentDir = path.join(__dirname, '..', 'resources', 'js', 'InertiaPages', 'Admin', componentName);

// 確保目錄存在
if (!fs.existsSync(componentDir)) {
    fs.mkdirSync(componentDir, { recursive: true });
}

// Form.vue 起手式模板
const formTemplate = `<!-- resources/js/InertiaPages/Admin/${componentName}/Form.vue -->
<template>
    <div class="content">
        <BreadcrumbItem />

        <div class="block block-rounded">
            <div class="block-content block-content-full">

                <form @submit.prevent="submit">
                    
                    <!-- 表單欄位區域 - 請在此處添加欄位 -->
                    

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
import { ref, inject } from 'vue'
import { useForm } from '@inertiajs/vue3'
import Layout from "@/Shared/Admin/Layout.vue";
import BreadcrumbItem from "@/Shared/Admin/Partials/BreadcrumbItem.vue";
import { FormValidator, useSubmitForm } from '@/utils';

// 接收 props
const props = defineProps({
    data: {
        type: Object,
        default: null
    }
});

// 引入 submitForm 方法
const { submitForm: performSubmit } = useSubmitForm();

// 表單資料
const form = useForm({
    // 請在此處定義表單欄位
    
});

// 定義驗證規則
const getRules = () => ({
    // 請在此處定義驗證規則
    
});

// 建立驗證器
const validator = new FormValidator(form, getRules);
const sweetAlert = inject('$sweetAlert');

// 提交表單
const submit = async () => {
    try {
        form.clearErrors();
        
        // 設定提交參數
        const url = props.data?.id
            ? route('admin.${componentName.toLowerCase()}.update', props.data.id)
            : route('admin.${componentName.toLowerCase()}.store');
        const method = props.data?.id ? 'put' : 'post';

        // 驗證表單
        const hasErrors = await validator.hasErrors();
        if (!hasErrors) {
            performSubmit({ form, url, method });
        } else {
            sweetAlert.error({
                msg: '提交失敗，請檢查是否有欄位錯誤！'
            });
        }
    } catch (error) {
        console.error('提交表單時發生錯誤:', error);
        sweetAlert.error({
            msg: '系統錯誤，請稍後再試！'
        });
    }
};

// 返回上一頁
const back = () => {
    window.history.back();
};
</script>

<script>
export default {
    layout: Layout,
};
</script>
`;

// Index.vue 起手式模板
const indexTemplate = `<!-- resources/js/InertiaPages/Admin/${componentName}/Index.vue -->
<template>
    <div class="content">
      <BreadcrumbItem />

      <div class="block block-rounded">
        <div class="block-header block-header-default">
          <h3 class="block-title">功能列表</h3>
           <Link
                class="btn btn-primary"
                :href="route('admin.${componentName.toLowerCase()}.add')"
                v-if="can('admin.${componentName.toLowerCase()}.add')"
                >
                    <i class="fa-solid fa-plus opacity-50 me-1"></i>新增
            </Link>
        </div>

        <div class="block-content block-content-full">
            <!-- 列表內容區域 - 請在此處添加 DataTable 或其他列表組件 -->
            
        </div>
      </div>
    </div>
</template>

<script setup>
import Layout from "@/Shared/Admin/Layout.vue";
import BreadcrumbItem from "@/Shared/Admin/Partials/BreadcrumbItem.vue";
import { Link } from "@inertiajs/vue3";
import { ref, inject, onMounted } from "vue";
import { router } from "@inertiajs/vue3";

const can = inject("can");
const sweetAlert = inject('$sweetAlert');

// 在此處添加列表相關的邏輯

</script>

<script>
export default {
    layout: Layout,
};
</script>
`;

// 根據類型生成對應的檔案
let template, fileName;

if (componentType === 'form') {
    template = formTemplate;
    fileName = 'Form.vue';
} else if (componentType === 'index') {
    template = indexTemplate;
    fileName = 'Index.vue';
} else {
    console.error('不支援的組件類型，請使用 "form" 或 "index"');
    process.exit(1);
}

// 寫入檔案
const filePath = path.join(componentDir, fileName);

if (fs.existsSync(filePath)) {
    console.log(`⚠️  檔案已存在: ${filePath}`);
    console.log('是否要覆蓋？請手動確認後再執行。');
} else {
    fs.writeFileSync(filePath, template);
    console.log(`✅ 成功生成: ${filePath}`);
    console.log(`📁 目錄: ${componentDir}`);
    
    if (componentType === 'form') {
        console.log(`\n📝 接下來請：`);
        console.log(`1. 在表單資料區域添加欄位`);
        console.log(`2. 在驗證規則區域添加驗證`);
        console.log(`3. 在模板區域添加表單欄位`);
    } else {
        console.log(`\n📝 接下來請：`);
        console.log(`1. 在列表內容區域添加 DataTable`);
        console.log(`2. 添加編輯、刪除等功能`);
    }
}