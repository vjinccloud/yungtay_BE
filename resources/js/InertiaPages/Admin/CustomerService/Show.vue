<!-- resources/js/InertiaPages/Admin/CustomerService/Show.vue -->
<template>
    <div class="content">
      <BreadcrumbItem />

      <div class="row">
        <!-- 左側：信件詳情 -->
        <div class="col-lg-8">
          <div class="block block-rounded">
            <div class="block-header block-header-default">
              <h3 class="block-title">
                <Link class="btn btn-sm btn-alt-secondary" :href="route('admin.customer-services')">
                  <i class="fa fa-arrow-left me-1"></i>
                  返回列表
                </Link>
              </h3>
              <div class="block-options">
                <!-- 狀態標籤 -->
                <span
                  class="badge"
                  :class="customerService.is_replied ? 'bg-success' : 'bg-warning'"
                >
                  <i :class="customerService.is_replied ? 'fa fa-check' : 'fa fa-clock'" class="me-1"></i>
                  {{ customerService.is_replied ? '已處理' : '待處理' }}
                </span>
              </div>
            </div>

            <div class="block-content">
              <!-- 基本資訊 -->
              <div class="row mb-4">
                <div class="col-sm-4">
                  <strong>聯絡人姓名：</strong>
                </div>
                <div class="col-sm-8">
                  {{ customerService.name || '-' }}
                </div>
              </div>

              <div class="row mb-4">
                <div class="col-sm-4">
                  <strong>聯絡信箱：</strong>
                </div>
                <div class="col-sm-8">
                  <a :href="`mailto:${customerService.email}`" class="text-primary">
                    {{ customerService.email || '-' }}
                  </a>
                </div>
              </div>

              <div class="row mb-4">
                <div class="col-sm-4">
                  <strong>聯絡電話：</strong>
                </div>
                <div class="col-sm-8">
                  {{ customerService.phone || '-' }}
                </div>
              </div>

              <div class="row mb-4">
                <div class="col-sm-4">
                  <strong>主旨：</strong>
                </div>
                <div class="col-sm-8">
                  <span class="fw-bold">{{ customerService.subject || '-' }}</span>
                </div>
              </div>


              <div class="row mb-4">
                <div class="col-sm-4">
                  <strong>提交時間：</strong>
                </div>
                <div class="col-sm-8">
                  {{ formatDateTime(customerService.created_at) }}
                </div>
              </div>

              <div class="row mb-4" v-if="customerService.is_replied && customerService.replied_at">
                <div class="col-sm-4">
                  <strong>回覆時間：</strong>
                </div>
                <div class="col-sm-8">
                  {{ formatDateTime(customerService.replied_at) }}
                </div>
              </div>

              <div class="row mb-4" v-if="customerService.replied_by_admin">
                <div class="col-sm-4">
                  <strong>處理人員：</strong>
                </div>
                <div class="col-sm-8">
                  {{ customerService.replied_by_admin.name }}
                </div>
              </div>

              <!-- 信件內容 -->
              <div class="mb-4">
                <strong class="d-block mb-2">信件內容：</strong>
                <div class="bg-dark-op p-3 rounded" style="min-height: 150px; background-color: #1e2328; color: #adbac7; border: 1px solid #3d444d;">
                  <div v-html="formatContent(customerService.message)" class="text-wrap"></div>
                </div>
              </div>
            </div>

            <!-- 標記為已處理按鈕（放在 block-content 外，右下角） -->
            <div class="block-content block-content-full text-end" v-if="!customerService.is_replied && can('admin.customer-services.update')">
              <button
                type="button"
                class="btn btn-success"
                @click="markAsProcessed"
                :disabled="isLoading"
              >
                <i class="fa fa-check-circle me-1"></i>標記為已處理
              </button>
            </div>
          </div>
        </div>

        <!-- 右側：操作面板 -->
        <div class="col-lg-4">
          <!-- 回覆功能 -->
          <div class="block block-rounded">
            <div class="block-header block-header-default">
              <h3 class="block-title">
                <i class="fa fa-reply me-1"></i>{{ customerService.is_replied ? '回覆內容' : '回覆信件' }}
              </h3>
            </div>
            <div class="block-content">
              <!-- 未回覆狀態：顯示回覆表單 -->
              <div v-if="!customerService.is_replied && can('admin.customer-services.reply')">
                <form @submit.prevent="sendReply">
                  <div class="mb-3">
                    <textarea
                      v-model="replyContent"
                      class="form-control"
                      rows="6"
                      placeholder="請輸入回覆內容..."
                      required
                    ></textarea>
                  </div>
                  <button
                    type="submit"
                    class="btn btn-primary w-100 mb-3"
                    :disabled="isLoading || !replyContent.trim()"
                  >
                    <i class="fa fa-paper-plane me-1"></i>送出回覆
                  </button>
                </form>
              </div>

              <!-- 無回覆權限時的提示 -->
              <div v-else-if="!customerService.is_replied && !can('admin.customer-services.reply')" class="text-muted text-center p-3">
                <i class="fa fa-lock me-1"></i>您沒有回覆權限
              </div>

              <!-- 已回覆狀態：顯示回覆內容 -->
              <div v-else>
                <div class="bg-dark-op p-3 rounded mb-3" style="background-color: #1e2328; color: #adbac7; border: 1px solid #3d444d;">
                  <div v-html="formatContent(customerService.reply_content || '回覆內容尚未載入')" class="text-wrap"></div>
                </div>
                <div class="text-muted small mb-3">
                  回覆時間：{{ formatDateTime(customerService.replied_at) }}
                </div>
              </div>

            </div>
          </div>

          <!-- 管理員備註 -->
          <div class="block block-rounded">
            <div class="block-header block-header-default">
              <h3 class="block-title">
                <i class="fa fa-sticky-note me-1"></i>管理員備註
              </h3>
              <div class="block-options" v-if="can('admin.customer-services.update')">
                <button
                  type="button"
                  class="btn btn-sm btn-primary"
                  @click="saveNote"
                  :disabled="isLoading"
                >
                  <i class="fa fa-save me-1"></i>儲存
                </button>
              </div>
            </div>
            <div class="block-content">
              <div v-if="can('admin.customer-services.update')">
                <form @submit.prevent="saveNote">
                  <div class="mb-3">
                    <textarea
                      v-model="adminNote"
                      class="form-control"
                      rows="6"
                      placeholder="請輸入管理員備註..."
                    ></textarea>
                  </div>
                </form>
              </div>

              <!-- 無更新權限時的唯讀顯示 -->
              <div v-else>
                <div class="mb-3">
                  <textarea
                    :value="adminNote"
                    class="form-control"
                    rows="6"
                    placeholder="管理員備註"
                    readonly
                    disabled
                  ></textarea>
                </div>
                <div class="text-muted text-center">
                  <i class="fa fa-lock me-1"></i>您沒有編輯備註權限
                </div>
              </div>
            </div>
          </div>

        </div>
      </div>
    </div>
</template>

<script setup>
import Layout from "@/Shared/Admin/Layout.vue";
import BreadcrumbItem from "@/Shared/Admin/Partials/BreadcrumbItem.vue";
import { ref, reactive, inject, onMounted } from "vue";
import { router, Link } from "@inertiajs/vue3";

// Props
const props = defineProps({
  customerService: {
    type: Object,
    required: true
  },
  mailTypes: {
    type: Array,
    default: () => []
  }
});

// Inject
const can = inject("can");
const sweetAlert = inject('$sweetAlert');
const isLoading = inject('isLoading');

// 管理員備註
const adminNote = ref(props.customerService.admin_note || '');

// 回覆內容
const replyContent = ref('');


// 標記為已處理
const markAsProcessed = () => {
  sweetAlert.confirm('確認要將此信件標記為「已處理」嗎？', () => {
    isLoading.value = true;
    router.patch(route('admin.customer-services.toggle-status', props.customerService.id), {
      is_replied: true
    }, {
      onSuccess: (finalRes) => {
        try {
          const res = finalRes.props.flash?.result || finalRes.props.result;
          if (res && res.status) {
            sweetAlert.resultData(res);
            // 更新本地狀態
            props.customerService.is_replied = true;
            props.customerService.replied_at = new Date().toISOString();
          } else {
            sweetAlert.error({ msg: '狀態切換失敗，請重試！' });
          }
        } catch (error) {
          console.error('處理狀態切換回應時發生錯誤:', error);
          sweetAlert.error({ msg: '處理回應時發生錯誤' });
        }
      },
      onError: (errors) => {
        console.error('狀態切換請求失敗:', errors);
        sweetAlert.error({ msg: '狀態切換失敗，請重試！' });
      },
      onFinish: () => {
        isLoading.value = false;
      }
    });
  });
};

// 切換回覆狀態
const toggleReplyStatus = () => {
  const newStatus = !props.customerService.is_replied;
  const actionText = newStatus ? '已處理' : '待處理';

  sweetAlert.confirm(`確認要將此信件標記為「${actionText}」嗎？`, () => {
    isLoading.value = true;
    router.patch(route('admin.customer-services.toggle-status', props.customerService.id), {
      is_replied: newStatus
    }, {
      onSuccess: (finalRes) => {
        try {
          const res = finalRes.props.flash?.result || finalRes.props.result;
          if (res && res.status) {
            sweetAlert.resultData(res);
            // 更新本地狀態
            props.customerService.is_replied = newStatus;
            if (newStatus) {
              props.customerService.replied_at = new Date().toISOString();
            } else {
              props.customerService.replied_at = null;
            }
          } else {
            sweetAlert.error({ msg: '狀態切換失敗，請重試！' });
          }
        } catch (error) {
          console.error('處理狀態切換回應時發生錯誤:', error);
          sweetAlert.error({ msg: '處理回應時發生錯誤' });
        }
      },
      onError: (errors) => {
        console.error('狀態切換請求失敗:', errors);
        sweetAlert.error({ msg: '狀態切換失敗，請重試！' });
      },
      onFinish: () => {
        isLoading.value = false;
      }
    });
  });
};



// 儲存管理員備註
const saveNote = () => {
  sweetAlert.confirm('確認要儲存此備註嗎？', () => {
    isLoading.value = true;
    router.put(route('admin.customer-services.update-note', props.customerService.id), {
      admin_note: adminNote.value
    }, {
      onSuccess: (finalRes) => {
        try {
          const res = finalRes.props.flash?.result || finalRes.props.result;
          if (res && res.status) {
            sweetAlert.resultData(res);
            // 更新本地狀態
            props.customerService.admin_note = adminNote.value;
          } else {
            sweetAlert.error({ msg: '備註儲存失敗，請重試！' });
          }
        } catch (error) {
          console.error('處理備註儲存回應時發生錯誤:', error);
          sweetAlert.error({ msg: '處理回應時發生錯誤' });
        }
      },
      onError: (errors) => {
        console.error('備註儲存請求失敗:', errors);
        sweetAlert.error({ msg: '備註儲存失敗，請重試！' });
      },
      onFinish: () => {
        isLoading.value = false;
      }
    });
  });
};

// 發送回覆
const sendReply = () => {
  if (!replyContent.value.trim()) {
    sweetAlert.warning({ msg: '請輸入回覆內容' });
    return;
  }

  sweetAlert.confirm('確認要發送此回覆嗎？發送後會以信件寄出', () => {
    isLoading.value = true;
    router.put(route('admin.customer-services.reply', props.customerService.id), {
      reply_subject: `Re: ${props.customerService.subject}`,
      reply_content: replyContent.value
    }, {
      onSuccess: (finalRes) => {
        try {
          const res = finalRes.props.flash?.result || finalRes.props.result;
          if (res && res.status) {
            sweetAlert.resultData(res);
            // 更新本地狀態
            props.customerService.is_replied = true;
            props.customerService.reply_content = replyContent.value;
            props.customerService.replied_at = new Date().toISOString();
            // 清空回覆內容
            replyContent.value = '';
          } else {
            sweetAlert.error({ msg: '回覆發送失敗，請重試！' });
          }
        } catch (error) {
          console.error('處理回覆發送回應時發生錯誤:', error);
          sweetAlert.error({ msg: '處理回應時發生錯誤' });
        }
      },
      onError: (errors) => {
        console.error('回覆發送請求失敗:', errors);
        sweetAlert.error({ msg: '回覆發送失敗，請重試！' });
      },
      onFinish: () => {
        isLoading.value = false;
      }
    });
  });
};

// 輔助方法
const getMailTypeName = (typeId) => {
  if (!typeId || !props.mailTypes?.length) return '一般諮詢';
  const mailType = props.mailTypes.find(type => type.id === typeId);
  return mailType ? mailType.name : '一般諮詢';
};

const formatDateTime = (dateTime) => {
  if (!dateTime) return '-';
  return new Date(dateTime).toLocaleString('zh-TW');
};

const formatContent = (content) => {
  if (!content) return '';
  // 將換行符轉換為 HTML 換行
  return content.replace(/\n/g, '<br>');
};

const formatUserAgent = (userAgent) => {
  if (!userAgent) return '';
  // 簡化 User Agent 顯示
  if (userAgent.includes('Chrome')) return 'Chrome';
  if (userAgent.includes('Firefox')) return 'Firefox';
  if (userAgent.includes('Safari')) return 'Safari';
  if (userAgent.includes('Edge')) return 'Edge';
  return 'Other';
};

// 組件掛載時初始化
onMounted(() => {
  console.log('客服信件詳情頁面已載入:', props.message);
});
</script>

<script>
export default {
    layout: Layout,
};
</script>

<style scoped>
.text-wrap {
  word-wrap: break-word;
  white-space: pre-wrap;
}

code {
  font-size: 0.875em;
}
</style>