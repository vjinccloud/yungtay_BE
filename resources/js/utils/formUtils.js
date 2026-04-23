import { inject } from 'vue';
import { router } from '@inertiajs/vue3';

export function useSubmitForm() {
  const sweetAlert = inject('$sweetAlert');
  const isLoading = inject('isLoading');

  const submitForm = ({ form, url, method, useFormData, formData, ...rest }) => {
    form.confirm = false;
    sweetAlert.confirm('是否確認送出？', () => {
      isLoading.value = true;
      const handleSuccess = (response) => {
        const result = response.props.result
          ?? response.props.flash?.result;

        // ✅ redirect 統一由 sweetAlert.resultData 內部處理
        // 有 redirect 時不傳 callback，讓 sweetAlert 自動處理跳轉
        // 沒有 redirect 時才傳 callback，執行後續操作
        sweetAlert.resultData(result, null, result?.redirect ? null : () => {
          // 只在沒有 redirect 時才執行後續操作
          if (result?.status) {
            // ✅ 改進：支援自訂 reload 目標，避免無關 prop 警告
            if (rest.reloadTarget) {
              router.reload({ only: [rest.reloadTarget] });
            } else {
              // 預設只 reload 操作日誌，避免重載整個頁面
              router.reload({ only: ['logs'] });
            }

            window.dispatchEvent(new CustomEvent('operationLogUpdated'));
            rest.closeModal?.();
            rest.emit?.('reload');
            rest.callback?.();
          }
        });
      };

      const handleError = (errors) => {
        // 安全提取錯誤訊息（避免 circular 結構）
        const firstError =
          typeof errors === 'object'
            ? Object.values(errors)?.flat()?.[0] || '提交失敗，請檢查是否有欄位錯誤！'
            : String(errors);

        sweetAlert.error({ msg: firstError });
        isLoading.value = false;
      };

      const handleFinish = () => { isLoading.value = false; };

      if (useFormData && formData) {
        router[method](url, formData, {
          forceFormData: true, preserveState: true, preserveScroll: true,
          onSuccess: handleSuccess, onError: handleError, onFinish: handleFinish
        });
      } else {
        form.submit(method, url, {
          preserveState: true, replace: true,
          onSuccess: handleSuccess, onError: handleError, onFinish: handleFinish
        });
      }
    });
  };

  return { submitForm };
}
