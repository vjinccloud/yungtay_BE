<template>
    <div class="content">
        <Head>
            <link rel="stylesheet" href="/plugins/fileuploader2.2/dist/font/font-fileuploader.css">
            <link rel="stylesheet" href="/plugins/fileuploader2.2/dist/jquery.fileuploader.min.css">
            <link rel="stylesheet" href="/plugins/bootstrap-tagsinput/css/bootstrap-tagsinput.css">
        </Head>
        <BreadcrumbItem />
        <div class="block block-rounded">
            <div class="block-content">
                <form class="mb-5"  @keydown.enter="handleEnterKey" @submit.prevent="submitForm">
                    <!-- 網站標誌上傳 -->
                    <div class="row mb-3 d-flex align-items-center">
                        <label class="col-sm-2 col-form-label fw-semibold">網站標誌</label>
                        <div class="col-sm-4">
                           <FileUploader
                           v-if="shouldRenderUploader"
                            ref="uploaderRef"
                            name="favicon"
                            accept=".ico"
                            upload-mode="form"
                            :limit="1"
                            extensions="ico"
                            :max-size="2"
                            :files="fileUpLoaderData"
                            />
                        </div>
                    </div>

                    <!-- 網站圖示（使用 Slim 圖片裁切） -->
                    <div class="row mb-3">
                        <label class="col-sm-2 col-form-label fw-semibold">網站圖示</label>
                        <div class="col-sm-4">
                            <Slim
                                ref="slimIcon"
                                :label="'點擊或拖曳圖片至此，建議尺寸 200×200'"
                                :width="200"
                                :height="200"
                                :ratio="'1:1'"
                                :initialImage="props.defaultSettings?.website_icon || ''"
                                @cleared="form.slimIconCleared = true"
                            />
                            <small class="text-muted d-block mt-1" style="font-size: 0.75rem;">點擊或拖曳圖片至此，建議尺寸 200×200</small>
                        </div>
                    </div>

                    <!-- 網站標題（多語言） -->
                    <div class="row mb-3">
                        <label class="col-sm-2 col-form-label fw-semibold">網站標題</label>
                        <div class="col-sm-10">
                            <!-- 中文標題 -->
                            <div class="mb-2">
                                <label class="form-label">中文</label>
                                <editable-text
                                    v-model="form.title.zh_TW"
                                    :empty-text="'點擊編輯中文標題'"
                                    @update="handleMultiLangUpdate('title', 'zh_TW', $event)"
                                />
                            </div>
                            <!-- 英文標題 -->
                            <div class="mb-2">
                                <label class="form-label">English</label>
                                <editable-text
                                    v-model="form.title.en"
                                    :empty-text="'Click to edit English title'"
                                    @update="handleMultiLangUpdate('title', 'en', $event)"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- 網站描述（多語言） -->
                    <div class="row mb-3">
                        <label class="col-sm-2 col-form-label fw-semibold">網站描述</label>
                        <div class="col-sm-10">
                            <!-- 中文描述 -->
                            <div class="mb-2">
                                <label class="form-label">中文</label>
                                <editable-textarea
                                    v-model="form.description.zh_TW"
                                    :empty-text="'點擊編輯中文描述'"
                                    :rows="3"
                                    @update="handleMultiLangUpdate('description', 'zh_TW', $event)"
                                />
                            </div>
                            <!-- 英文描述 -->
                            <div class="mb-2">
                                <label class="form-label">English</label>
                                <editable-textarea
                                    v-model="form.description.en"
                                    :empty-text="'Click to edit English description'"
                                    :rows="3"
                                    @update="handleMultiLangUpdate('description', 'en', $event)"
                                />
                            </div>
                        </div>
                    </div>


                    <!-- 聯絡電話 -->
                    <div class="row mb-3 d-flex align-items-center">
                        <label class="col-sm-2 col-form-label fw-semibold">聯絡電話</label>
                        <div class="col-sm-10 d-flex align-items-center">
                            <editable-text
                                v-model="form.tel"
                                :empty-text="'點擊編輯聯絡電話'"
                                type="tel"
                                @update="handleUpdate('tel', $event)"
                            />
                        </div>
                    </div>

                    <!-- 電子郵件 -->
                    <div class="row mb-3 d-flex align-items-center">
                        <label class="col-sm-2 col-form-label fw-semibold">電子郵件</label>
                        <div class="col-sm-10 d-flex align-items-center">
                            <editable-text
                                v-model="form.email"
                                :empty-text="'點擊編輯電子郵件'"
                                type="email"
                                @update="handleUpdate('email', $event)"
                            />
                        </div>
                    </div>

                    <!-- Line連結 -->
                    <div class="row mb-3 d-flex align-items-center">
                        <label class="col-sm-2 col-form-label fw-semibold">Line連結</label>
                        <div class="col-sm-10 d-flex align-items-center">
                            <editable-text
                                v-model="form.line"
                                :empty-text="'點擊編輯Line連結'"
                                type="url"
                                @update="handleUpdate('line', $event)"
                            />
                        </div>
                    </div>

                    <!-- Instagram連結 -->
                    <div class="row mb-3 d-flex align-items-center">
                        <label class="col-sm-2 col-form-label fw-semibold">Instagram連結</label>
                        <div class="col-sm-10 d-flex align-items-center">
                            <editable-text
                                v-model="form.ig"
                                :empty-text="'點擊編輯Instagram連結'"
                                type="url"
                                @update="handleUpdate('ig', $event)"
                            />
                        </div>
                    </div>

                    <!-- Facebook連結 -->
                    <div class="row mb-3 d-flex align-items-center">
                        <label class="col-sm-2 col-form-label fw-semibold">Facebook連結</label>
                        <div class="col-sm-10 d-flex align-items-center">
                            <editable-text
                                v-model="form.fb"
                                :empty-text="'點擊編輯Facebook連結'"
                                type="url"
                                @update="handleUpdate('fb', $event)"
                            />
                        </div>
                    </div>

                    <!-- YouTube連結 -->
                    <div class="row mb-3 d-flex align-items-center">
                        <label class="col-sm-2 col-form-label fw-semibold">YouTube連結</label>
                        <div class="col-sm-10 d-flex align-items-center">
                            <editable-text
                                v-model="form.youtube"
                                :empty-text="'點擊編輯YouTube連結'"
                                type="url"
                                @update="handleUpdate('youtube', $event)"
                            />
                        </div>
                    </div>

                    <!-- Google Play下載連結 -->
                    <div class="row mb-3 d-flex align-items-center">
                        <label class="col-sm-2 col-form-label fw-semibold">Google Play下載連結</label>
                        <div class="col-sm-10 d-flex align-items-center">
                            <editable-text
                                v-model="form.app_google_play"
                                :empty-text="'點擊編輯Google Play下載連結'"
                                type="url"
                                @update="handleUpdate('app_google_play', $event)"
                            />
                        </div>
                    </div>

                    <!-- Apple Store下載連結 -->
                    <div class="row mb-3 d-flex align-items-center">
                        <label class="col-sm-2 col-form-label fw-semibold">Apple Store下載連結</label>
                        <div class="col-sm-10 d-flex align-items-center">
                            <editable-text
                                v-model="form.app_apple_store"
                                :empty-text="'點擊編輯Apple Store下載連結'"
                                type="url"
                                @update="handleUpdate('app_apple_store', $event)"
                            />
                        </div>
                    </div>

                    <!-- Google Analytics -->
                    <div class="row mb-3 d-flex align-items-center">
                        <label class="col-sm-2 col-form-label fw-semibold">Google Analytics</label>
                        <div class="col-sm-10 d-flex align-items-center">
                            <editable-textarea
                                v-model="form.ga_code"
                                :empty-text="'點擊編輯Google Analytics代碼'"
                                :rows="4"
                                @update="handleUpdate('ga_code', $event)"
                            />
                        </div>
                    </div>

                    <!-- 關鍵字（多語言） -->
                    <div class="row mb-3">
                        <label class="col-sm-2 col-form-label fw-semibold">關鍵字</label>
                        <div class="col-sm-10">
                            <!-- 中文關鍵字 -->
                            <div class="mb-2">
                                <label class="form-label">中文</label>
                                <editable-tags
                                    v-model="form.keyword.zh_TW"
                                    :empty-text="'點擊添加中文關鍵字'"
                                    placeholder="輸入後按Enter"
                                    @update="handleMultiLangUpdate('keyword', 'zh_TW', $event)"
                                />
                            </div>
                            <!-- 英文關鍵字 -->
                            <div class="mb-2">
                                <label class="form-label">English</label>
                                <editable-tags
                                    v-model="form.keyword.en"
                                    :empty-text="'Click to add English keywords'"
                                    placeholder="Enter and press Enter"
                                    @update="handleMultiLangUpdate('keyword', 'en', $event)"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- 提交按鈕 -->
                    <div class="text-end">
                        <button
                            type="submit"
                            class="btn btn-primary mt-3"
                            :disabled="form.processing"
                        >
                            <i v-if="form.processing" class="fa fa-spinner fa-spin me-1"></i>
                            <i v-else class="fa fa-save me-1"></i>
                            {{ form.processing ? '處理中...' : '儲存' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup>
import Layout from "@/Shared/Admin/Layout.vue";
// 可編輯文本組件
import EditableText from '@/Plugin/EditableText.vue';
import EditableTextarea from '@/Plugin/EditableTextarea.vue';
import EditableTags from '@/Plugin/EditableTags.vue';
// 導入檔案上傳組件
import FileUploader from '@/Plugin/FileUploader.vue'
// 導入 Slim 圖片裁切組件
import Slim from "@/Plugin/Slim.vue";
import BreadcrumbItem from "@/Shared/Admin/Partials/BreadcrumbItem.vue";
import { useForm, usePage, router, Head } from "@inertiajs/vue3";
import { ref, computed, inject ,watch, toRefs, onMounted, onBeforeUnmount } from 'vue';
import { getSlimValue, destroySlim } from '@/utils';



const props = defineProps({
  defaultSettings: Object,
  fileUpLoaderData: {
    type: Array,
    default: () => []
  }
})

const shouldRenderUploader = ref(false)

onMounted(() => {
    // 延遲渲染 FileUploader，避免重複初始化問題
    setTimeout(() => {
        shouldRenderUploader.value = true
    }, 200) // 增加延遲時間，確保DOM完全載入
})


const isLoading = inject('isLoading');
const sweetAlert = inject('$sweetAlert');

// 建立 fileUpLoaderData 的可寫副本
const fileUpLoaderData = ref([...(props.fileUpLoaderData || [])])

// FileUploader 的引用
const uploaderRef = ref(null)

// Slim 圖片裁切組件的引用
const slimIcon = ref(null)

// 表單數據
const form = useForm({
    // 多語言欄位
    title: {
        zh_TW: props.defaultSettings?.title?.zh_TW || "",
        en: props.defaultSettings?.title?.en || ""
    },
    description: {
        zh_TW: props.defaultSettings?.description?.zh_TW || "",
        en: props.defaultSettings?.description?.en || ""
    },
    keyword: {
        zh_TW: props.defaultSettings?.keyword?.zh_TW || "",
        en: props.defaultSettings?.keyword?.en || ""
    },
    // 單語言欄位
    tel: props.defaultSettings?.tel || "",
    line: props.defaultSettings?.line || "",
    fb: props.defaultSettings?.fb || "",
    ig: props.defaultSettings?.ig || "",
    youtube: props.defaultSettings?.youtube || "",
    app_google_play: props.defaultSettings?.app_google_play || "",
    app_apple_store: props.defaultSettings?.app_apple_store || "",
    email: props.defaultSettings?.email || "",
    ga_code: props.defaultSettings?.ga_code || "",
    
    // Slim 圖片相關
    slimIcon: null,
    slimIconCleared: false,
});


// 處理更新事件
const handleUpdate = (field, value) => {
    form[field] = value;
    console.log(`Updated ${field}:`, value);
};

// 處理多語言更新事件
const handleMultiLangUpdate = (field, lang, value) => {
    if (!form[field]) {
        form[field] = {};
    }
    form[field][lang] = value;
    console.log(`Updated ${field}.${lang}:`, value);
};


// 提交表單
const submitForm = () => {
  sweetAlert.confirm('是否確認送出？', () => {
    isLoading.value = true;
    
    // 獲取 Slim 圖片值
    form.slimIcon = getSlimValue(slimIcon.value);

    // 使用 transform 方法
    form.transform((data) => {
        const formData = new FormData();
        // 要跳過的欄位
        const skip = ['favicon', 'faviconFiles', 'processing', 'errors', 'slimIconCleared'];

        // 1. 處理表單欄位
        Object.keys(data).forEach(key => {
            if (skip.includes(key)) return;
            
            const value = data[key];
            
            // 如果是多語言欄位（物件格式），且不是 Slim 圖片
            if (typeof value === 'object' && value !== null && key !== 'slimIcon') {
                Object.keys(value).forEach(lang => {
                    formData.append(`${key}[${lang}]`, value[lang] ?? '');
                });
            } else {
                // 單語言欄位
                formData.append(key, value ?? '');
            }
        });

        // 2. 再把檔案塞進來
        try {
            console.log('shouldRenderUploader:', shouldRenderUploader.value);
            console.log('uploaderRef.value:', uploaderRef.value);
            console.log('fileUpLoaderData:', fileUpLoaderData.value);
            
            if (uploaderRef.value && typeof uploaderRef.value.getFiles === 'function') {
                const files = uploaderRef.value.getFiles() || [];
                console.log('FileUploader files:', files);
                if (files.length > 0) {
                    const f = files[0];
                    console.log('Appending favicon file:', f.name, f.size, f.type);
                    formData.append('favicon', f, f.name);
                    console.log('✅ Favicon file successfully added to FormData');
                } else {
                    console.log('No new files selected - using existing favicon');
                }
            } else {
                console.error('FileUploader not properly initialized or getFiles method not available');
            }
        } catch (error) {
            console.error('Error getting files from uploader:', error);
        }
        return formData;
    }).post(route('admin.basic-website-settings.update'), {
      forceFormData: true,
      preserveScroll: true,
      onSuccess: (response) => {
        const result = response.props.result;
        if (result?.status) {
          const r = result.redirect ? router : null;
          sweetAlert.success(result, r);
        }
        isLoading.value = false;
      },
      onError: (errors) => {
        console.error('Submit errors:', errors);
        sweetAlert.error({ msg: '提交失敗，請檢查是否有欄位錯誤！' });
        isLoading.value = false;
      },
      onFinish: () => {
        isLoading.value = false;
      }
    });
  });
};

//防止 Enter 鍵提交表單
const handleEnterKey = (e) => {
    // 如果是在提交按鈕上按 Enter，允許提交
    if (e.target.type === 'submit') {
        submitForm();
        return;
    }
    // 若你想讓 textarea 仍能換行，就加這段判斷
    if (e.target.tagName.toLowerCase() === 'textarea') {
        console.log(e.target.tagName.toLowerCase() );
        return;
    }
    // 其他情況都阻止表單提交
    e.preventDefault();
};

// 在組件銷毀前清理 Slim
onBeforeUnmount(async () => {
    await destroySlim(slimIcon.value);
});
</script>

<script>

export default {
    layout: Layout,
};
</script>

<style scoped>
/* 可以添加自定義樣式 */
.editable-display {
    min-height: 38px;
    padding: 6px 12px;
    border: 1px solid transparent;
    border-radius: 0.375rem;
    transition: all 0.15s ease-in-out;
    cursor: pointer;
    display: flex;
    align-items: center;
}

.editable-display:hover {
    background-color: #f8f9fa;
    border-color: #dee2e6;
}

.editable-display.empty {
    color: #6c757d;
    font-style: italic;
}

.editable-display.editing {
    background-color: #fff;
    border-color: #86b7fe;
    box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
}
</style>
