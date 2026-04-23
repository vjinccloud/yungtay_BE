<!-- resources/js/InertiaPages/Admin/MemberNotification/Form.vue -->
<template>
    <div class="content">
        <BreadcrumbItem />

        <div class="block block-rounded">
            <div class="block-header block-header-default">
                <h3 class="block-title">
                    <Link class="btn btn-sm btn-alt-secondary" :href="route('admin.member-notifications')">
                        <i class="fa fa-arrow-left me-1"></i>
                        返回列表
                    </Link>
                </h3>
            </div>
            <div class="block-content block-content-full">

                <form @submit.prevent="submit">

                    <!-- 基本資訊區塊 -->
                    <div class="row push">
                        <div class="col-lg-8 col-xl-6">
                            <h4 class="fw-bold text-primary mb-3">基本資訊</h4>

                            <!-- 通知主題（支援多語系） -->
                            <div class="mb-4">
                                <div class="mb-3">
                                    <label class="form-label">通知主旨（中文） <span class="text-danger">*</span></label>
                                    <input
                                        type="text"
                                        v-model="form.title.zh_TW"
                                        class="form-control"
                                        :class="{ 'is-invalid': form.errors.title?.zh_TW }"
                                        placeholder="請輸入中文通知主旨"
                                        maxlength="255"
                                        @blur="validator.singleField('title.zh_TW')"
                                        required
                                    />
                                    <div class="form-text">最多 255 個字元</div>
                                    <div v-if="form.errors.title?.zh_TW" class="invalid-feedback">{{ form.errors.title?.zh_TW }}</div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">通知主旨（英文） <span class="text-danger">*</span></label>
                                    <input
                                        type="text"
                                        v-model="form.title.en"
                                        class="form-control"
                                        :class="{ 'is-invalid': form.errors.title?.en }"
                                        placeholder="Please enter English notification subject"
                                        maxlength="255"
                                        @blur="validator.singleField('title.en')"
                                        required
                                    />
                                    <div class="form-text">Maximum 255 characters</div>
                                    <div v-if="form.errors.title?.en" class="invalid-feedback">{{ form.errors.title?.en }}</div>
                                </div>
                            </div>

                            <!-- 通知內容（支援多語系） -->
                            <div class="mb-4">
                                <div class="mb-3">
                                    <label class="form-label">通知內容（中文） <span class="text-danger">*</span></label>
                                    <textarea
                                        v-model="form.message.zh_TW"
                                        class="form-control"
                                        :class="{ 'is-invalid': form.errors.message?.zh_TW }"
                                        rows="6"
                                        placeholder="請輸入中文通知內容"
                                        maxlength="1000"
                                        @blur="validator.singleField('message.zh_TW')"
                                        required
                                    ></textarea>
                                    <div class="form-text">
                                        已輸入 {{ form.message.zh_TW.length }} / 1000 個字元
                                    </div>
                                    <div v-if="form.errors.message?.zh_TW" class="invalid-feedback">{{ form.errors.message?.zh_TW }}</div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">通知內容（英文） <span class="text-danger">*</span></label>
                                    <textarea
                                        v-model="form.message.en"
                                        class="form-control"
                                        :class="{ 'is-invalid': form.errors.message?.en }"
                                        rows="6"
                                        placeholder="Please enter English notification content"
                                        maxlength="1000"
                                        @blur="validator.singleField('message.en')"
                                        required
                                    ></textarea>
                                    <div class="form-text">
                                        Character count: {{ form.message.en.length }} / 1000
                                    </div>
                                    <div v-if="form.errors.message?.en" class="invalid-feedback">{{ form.errors.message?.en }}</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 發送對象區塊 -->
                    <div class="row push">
                        <div class="col-12">
                            <h4 class="fw-bold text-primary mb-3">發送對象</h4>

                            <!-- 對象選擇 -->
                            <div class="mb-4">
                                <div class="form-check form-check-inline">
                                    <input
                                        id="target-all"
                                        v-model="form.target_type"
                                        type="radio"
                                        class="form-check-input"
                                        value="all"
                                        @change="handleTargetTypeChange"
                                    />
                                    <label for="target-all" class="form-check-label">全體會員</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input
                                        id="target-specific"
                                        v-model="form.target_type"
                                        type="radio"
                                        class="form-check-input"
                                        value="specific"
                                        @change="handleTargetTypeChange"
                                    />
                                    <label for="target-specific" class="form-check-label">指定會員</label>
                                </div>
                            </div>

                            <!-- 全體會員提示 -->
                            <div v-if="form.target_type === 'all'" class="alert alert-info">
                                <i class="fa fa-info-circle me-2"></i>
                                將發送給所有已註冊且啟用的會員
                            </div>

                            <!-- 指定會員選擇 -->
                            <div v-if="form.target_type === 'specific'">
                                <label class="form-label">選擇會員 <span class="text-danger">*</span></label>
                                <Select2Input
                                    v-model="form.target_user_ids"
                                    :multiple="true"
                                    :ajax="memberAjaxConfig"
                                    :minimumInputLength="2"
                                    placeholder="輸入會員姓名或Email搜尋..."
                                    :class="{ 'is-invalid': hasTargetUserIdsError }"
                                    @update:modelValue="handleTargetUserChange"
                                />
                                <div v-if="form.errors.target_user_ids" class="invalid-feedback d-block">
                                    {{ form.errors.target_user_ids }}
                                </div>
                                <small class="form-text text-muted">
                                    請輸入至少2個字元開始搜尋會員
                                </small>
                            </div>
                        </div>
                    </div>

                    <!-- 發送時間區塊 -->
                    <div class="row push">
                        <div class="col-12">
                            <h4 class="fw-bold text-primary mb-3">發送時間</h4>

                            <!-- 時間選擇 -->
                            <div class="mb-4">
                                <div class="form-check form-check-inline">
                                    <input
                                        id="send-now"
                                        v-model="form.send_type"
                                        type="radio"
                                        class="form-check-input"
                                        value="now"
                                    />
                                    <label for="send-now" class="form-check-label">立即發送</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input
                                        id="send-scheduled"
                                        v-model="form.send_type"
                                        type="radio"
                                        class="form-check-input"
                                        value="scheduled"
                                    />
                                    <label for="send-scheduled" class="form-check-label">排程發送</label>
                                </div>
                            </div>

                            <!-- 排程時間選擇 -->
                            <div v-if="form.send_type === 'scheduled'" class="col-lg-6">
                                <label class="form-label">排程發送時間 <span class="text-danger">*</span></label>
                                <DatePicker
                                    v-model="form.scheduled_at"
                                    placeholder="選擇排程發送時間"
                                    :enable-time="true"
                                    date-format="Y-m-d H:i"
                                    :options="datePickerOptions"
                                    :has-error="!!form.errors.scheduled_at"
                                    :class="{ 'is-invalid': form.errors.scheduled_at }"
                                    icon-class="fa fa-calendar-alt"
                                    @update:modelValue="handleScheduledAtChange"
                                />
                                <div class="form-text">至少需要在 5 分鐘後</div>
                                <div v-if="form.errors.scheduled_at" class="invalid-feedback">{{ form.errors.scheduled_at }}</div>
                            </div>
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
                                <i class="fa fa-paper-plane me-1"></i>
                                發送通知
                            </span>
                        </button>
                    </div>
                </form>

            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, inject, nextTick, watch } from 'vue'
import { useForm, Link } from '@inertiajs/vue3'
import Layout from "@/Shared/Admin/Layout.vue";
import BreadcrumbItem from "@/Shared/Admin/Partials/BreadcrumbItem.vue";
import Select2Input from "@/Plugin/Select2Input.vue";
import DatePicker from "@/Plugin/DatePicker.vue";
import { FormValidator, useSubmitForm } from '@/utils';

// 接收 props
const props = defineProps({
    data: {
        type: Object,
        default: null
    }
});

// 定義 emits（避免 Vue 警告）
const emit = defineEmits([]);


// 引入 submitForm 方法
const { submitForm: performSubmit } = useSubmitForm();

// 注入全域功能
const sweetAlert = inject('$sweetAlert');
const can = inject('can');

// 表單資料（支援多語系）
const form = useForm({
    title: {
        zh_TW: '',
        en: ''
    },
    message: {
        zh_TW: '',
        en: ''
    },
    target_type: 'all', // all, specific
    target_user_ids: [],
    send_type: 'now', // now, scheduled
    scheduled_at: ''
});

// Select2 AJAX 配置
const memberAjaxConfig = {
    url: '/api/v1/members/search',
    dataType: 'json',
    delay: 250,
    data: function (params) {
        return {
            q: params.term,
            page: params.page
        };
    },
    processResults: function (data, params) {
        params.page = params.page || 1;
        return {
            results: data.data.map(member => ({
                id: member.id,
                text: `${member.name} (${member.email})`
            })),
            pagination: {
                more: data.current_page < data.last_page
            }
        };
    },
    cache: true
};


// 計算預設時間的函數
const getDefaultDateTime = () => {
    const now = new Date();
    const futureTime = new Date(now.getTime() + 5 * 60 * 1000); // 5分鐘後
    const roundedMinutes = Math.ceil(futureTime.getMinutes() / 5) * 5;

    let defaultHour = futureTime.getHours();
    let defaultMinute = roundedMinutes;

    if (defaultMinute >= 60) {
        defaultHour += 1;
        defaultMinute = 0;
    }

    const defaultDateTime = new Date(futureTime);
    defaultDateTime.setHours(defaultHour);
    defaultDateTime.setMinutes(defaultMinute);
    defaultDateTime.setSeconds(0);

    return defaultDateTime;
};

// DatePicker 的選項設定
const datePickerOptions = computed(() => {
    const now = new Date();
    const futureTime = new Date(now.getTime() + 5 * 60 * 1000); // 5分鐘後

    return {
        minDate: futureTime,
        minuteIncrement: 5, // 分鐘間隔5分鐘
        time_24hr: true // 使用24小時制
    };
});



// 定義驗證規則（支援多語系）- 使用 computed 確保每次重新計算
const rules = computed(() => ({
    title: {
        zh_TW: ['required', 'string', ['max', 255]],
        en: ['required', 'string', ['max', 255]],
    },
    message: {
        zh_TW: ['required', 'string', ['max', 1000]],
        en: ['required', 'string', ['max', 1000]],
    },
    target_user_ids: form.target_type === 'specific'
        ? ['required', value => Array.isArray(value) && value.length > 0 || '請選擇至少一個會員']
        : [],
    scheduled_at: form.send_type === 'scheduled' ? ['required', 'date'] : []
}));

// 建立驗證器
const validator = new FormValidator(form, () => rules.value);

// 計算屬性：檢查是否有 target_user_ids 錯誤
const hasTargetUserIdsError = computed(() => {
    const hasError = !!(form.errors && form.errors.target_user_ids);
    return hasError;
});

// 計算屬性：檢查是否有 scheduled_at 錯誤
const hasScheduledAtError = computed(() => {
    return !!(form.errors && form.errors.scheduled_at);
});

// 提交表單（參考 News 模式簡化）
const submit = async () => {
    try {
        form.clearErrors();

        // 驗證表單
        const hasErrors = await validator.hasErrors();
        if (hasErrors) {
            sweetAlert.error({
                msg: '提交失敗，請檢查是否有欄位錯誤！'
            });
            return;
        }

        // 額外的排程時間驗證
        if (form.send_type === 'scheduled' && form.scheduled_at) {
            const scheduledTime = new Date(form.scheduled_at);
            const minTime = new Date(Date.now() + 5 * 60 * 1000); // 5分鐘後

            if (scheduledTime <= minTime) {
                form.setError('scheduled_at', '排程時間必須在 5 分鐘後');
                sweetAlert.error({
                    msg: '排程時間設定錯誤，請檢查並修正！'
                });
                return;
            }
        }

        // 設定提交參數
        const url = route('admin.member-notifications.store');
        const method = 'post';

        // 執行提交（performSubmit 內部已有確認機制）
        performSubmit({ form, url, method });

    } catch (error) {
        console.error('提交表單時發生錯誤:', error);
        sweetAlert.error({
            msg: '系統錯誤，請稍後再試！'
        });
    }
};


// 處理發送對象類型變更
const handleTargetTypeChange = () => {
    // 清除目標用戶相關錯誤
    form.clearErrors('target_user_ids');

    // 如果切換到全體會員，清空已選的特定會員
    if (form.target_type === 'all') {
        form.target_user_ids = [];
    }
};

// 處理目標用戶變更（避免循環引用）
const handleTargetUserChange = (value) => {
    if (Array.isArray(value)) {
        // 只保留 id，轉換成純數字陣列
        form.target_user_ids = value.map(v => (typeof v === 'object' ? v.id : v));
    }

    // 如果是特定會員模式且有選擇會員，清除錯誤
    if (form.target_type === 'specific' && form.target_user_ids && form.target_user_ids.length > 0) {
        form.clearErrors('target_user_ids');
    }
};

// 處理排程時間變更（避免循環引用）
const handleScheduledAtChange = (value) => {
    // 清除可能的錯誤
    if (form.errors.scheduled_at) {
        delete form.errors.scheduled_at;
    }
};

// 返回上一頁
const back = () => {
    window.history.back();
};

// 監聽 send_type 變化，自動設定預設時間
watch(() => form.send_type, (newValue) => {
    if (newValue === 'scheduled' && !form.scheduled_at) {
        const defaultDateTime = getDefaultDateTime();
        const defaultStr = defaultDateTime.getFullYear() + '-' +
            String(defaultDateTime.getMonth() + 1).padStart(2, '0') + '-' +
            String(defaultDateTime.getDate()).padStart(2, '0') + ' ' +
            String(defaultDateTime.getHours()).padStart(2, '0') + ':' +
            String(defaultDateTime.getMinutes()).padStart(2, '0');

        form.scheduled_at = defaultStr;
    }
});

// 頁面載入時初始化
onMounted(() => {
    // 不需要載入縣市資料，因為改用 AJAX Select2
});
</script>

<script>
export default {
    layout: Layout,
};
</script>

<style >
.block-content {
    padding: 2rem;
}

.push {
    margin-bottom: 2rem;
}

.alert {
    border-radius: 0.5rem;
}

.bg-light {
    background-color: #f8f9fa !important;
}

.form-text {
    font-size: 0.875rem;
    color: #6c757d;
}

.badge {
    font-size: 0.75rem;
}

.btn-sm {
    padding: 0.25rem 0.5rem;
    font-size: 0.875rem;
}

.text-muted {
    color: #6c757d !important;
}
/* ========================
   Select2 Bootstrap5 Overrides
   ======================== */

/* 亮色模式 (default) */
.select2-container--bootstrap-5 .select2-selection {
  background-color: #fff;
  border: 1px solid #ced4da;
  color: #212529;
  min-height: 46px;              /* 外框高度 */
  padding: 0.4rem 0.6rem;        /* 內距撐開 */
  display: flex;
  align-items: center;           /* 垂直置中 */
  border-radius: 0.375rem;
}

/* 暗色模式 */
html.dark .select2-container--bootstrap-5 .select2-selection {
  color: #c5cdd8;
  background-color: #1b1f22;
  border-color: #383f45;
  min-height: 46px;              /* 外框高度 */
  padding: 0.4rem 0.6rem;
  display: flex;
  align-items: center;
  border-radius: 0.375rem;
}

/* 搜尋輸入框 (亮色 + 暗色) */
.select2-container--bootstrap-5 .select2-search__field {
  width: 100% !important;          /* 撐滿容器 */
  min-width: 160px !important;     /* 避免太小 */
  height: 2.25rem !important;      /* Bootstrap 5 標準輸入框高度 */
  line-height: 1.5 !important;
  padding: 0.375rem 0.75rem !important; /* 內距一致 */
  margin: 0.2rem 0 !important;     /* 上下間距 */
  font-size: 1rem;
  border: 1px solid #ced4da !important; /* 灰色邊框 */
  border-radius: 0.375rem;          /* 圓角 */
  background-color: #fff !important;
  color: #212529 !important;
  box-shadow: none !important;
  transition: border-color .15s ease-in-out, box-shadow .15s ease-in-out;
}

.select2-container--bootstrap-5 .select2-search__field:focus {

}

html.dark .select2-container--bootstrap-5 .select2-search__field {
  background-color: #1b1f22 !important;
  color: #fff !important;
  border: none !important;
   width: 100% !important;
  min-width: 120px !important;  /* 可調整，避免太小 */
  flex: 1 1 auto !important;    /* 讓它跟隨 flex 佈局 */
}

/* 已選擇項目 - 亮色模式 */
.select2-container--bootstrap-5 .select2-selection__choice {
  color: #212529;
  background-color: #e9ecef;
  border: 1px solid #ced4da;
   margin-top: 0.25rem !important;
  margin-bottom: 0.25rem !important;
  padding: 0.35rem 0.65rem !important;
  line-height: 1.4 !important;
  border-radius: 0.375rem !important;
}

/* 已選擇項目 - 暗色模式 */
html.dark .select2-container--bootstrap-5 .select2-selection__choice {
  color: #fff !important;
  background-color: #3a404a !important;
  border: 1px solid #565d68 !important;
  margin-top: 0.25rem !important;
  margin-bottom: 0.25rem !important;
  padding: 0.35rem 0.65rem !important;
  line-height: 1.4 !important;
  border-radius: 0.375rem !important;
}
.select2-container--bootstrap-5 .select2-selection--multiple {
  min-height: 60px !important;   /* 高度再高一點 */
  padding: 0.35rem 0.5rem !important; /* 上下留白 */
  display: flex;
  flex-wrap: wrap;
  align-items: center;
}

.select2-container--bootstrap-5 .select2-selection--multiple .select2-search {
  height: 2.8rem !important;
}
.select2-container--bootstrap-5 .select2-selection--multiple .select2-selection__clear {
    right: .75rem;
    top: 25px;
}

.select2-container--bootstrap-5 .select2-selection__choice__remove {
  color: #aaa !important;             /* x 顏色 */
  font-weight: bold;
  font-size: 1rem;                    /* 放大一點 */
  line-height: 1;
  margin-right: 0.35rem;
  cursor: pointer;
  background: none !important;        /* 移除背景 SVG */
  text-indent: 0 !important;          /* 移除縮排，避免殘影 */
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: auto;
  height: auto;
}


</style>
