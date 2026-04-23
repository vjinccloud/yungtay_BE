<?php

namespace App\Services;

use App\Repositories\CustomerServiceRepository;
use App\Services\BaseService;
use App\Events\CustomerServiceReplied;
use Illuminate\Support\Facades\Log;

class CustomerServiceService extends BaseService
{
    /**
     * 建構子
     */
    public function __construct(
        private CustomerServiceRepository $customerService
    ) {
        parent::__construct($customerService);
    }

    /**
     * 取得客服訊息分頁資料
     */
    public function getCustomerServices($perPage = 15, $sortColumn = 'created_at', $sortDirection = 'desc', $filters = [])
    {
        return $this->customerService->paginate($perPage, $sortColumn, $sortDirection, $filters);
    }

    /**
     * 儲存客服訊息（前台表單送出）
     */
    public function saveFromFrontend(array $attributes)
    {
        try {
            // 儲存訊息（Observer 會自動發送郵件通知）
            $customerService = $this->customerService->save($attributes);

            return $this->ReturnHandle(
                true,
                __('frontend.customer_service.message_sent'),
                route('customer-service',),
                ['id' => $customerService->id]
            );

        } catch (\Exception $e) {
            Log::error('客服中心：儲存訊息失敗', [
                'data' => $attributes,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->ReturnHandle(false, __('frontend.customer_service.message_failed'));
        }
    }

    /**
     * 取得會員的客服紀錄（分頁）
     */
    public function getUserCustomerServiceRecords($userId, $perPage = 10)
    {
        try {
            $records = $this->customerService->getUserRecords($userId, $perPage);

            return $this->ReturnHandle(true, '取得客服紀錄成功', null, $records);

        } catch (\Exception $e) {
            Log::error('取得客服紀錄失敗', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            return $this->ReturnHandle(false, '取得客服紀錄失敗');
        }
    }

    /**
     * 刪除客服訊息
     */
    public function delete($id)
    {
        try {
            $customerService = $this->customerService->find($id);
            if (!$customerService) {
                return $this->ReturnHandle(false, '客服訊息不存在');
            }

            $this->customerService->delete($id);

            return $this->ReturnHandle(true, '客服訊息刪除成功');

        } catch (\Exception $e) {
            Log::error('客服中心：刪除失敗', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);

            return $this->ReturnHandle(false, '刪除失敗，請稍後再試');
        }
    }


    /**
     * 回覆客服訊息
     */
    public function reply($id, array $replyData)
    {
        try {
            // 更新回覆資訊（Observer 會自動發送回覆郵件）
            $customerService = $this->customerService->updateReply($id, $replyData);
            
            if (!$customerService) {
                return $this->ReturnHandle(false, '客服訊息不存在');
            }

            // 觸發回覆事件（用於操作紀錄）
            try {
                event(new CustomerServiceReplied($customerService));
            } catch (\Exception $eventError) {
                Log::warning('客服中心：觸發回覆事件失敗', [
                    'id' => $id,
                    'error' => $eventError->getMessage()
                ]);
            }

            return $this->ReturnHandle(true, '回覆已發送');

        } catch (\Exception $e) {
            Log::error('客服中心：回覆失敗', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);

            return $this->ReturnHandle(false, '回覆失敗，請稍後再試');
        }
    }


    /**
     * 切換客服訊息處理狀態
     */
    public function toggleStatus($id, $isReplied)
    {
        try {
            $customerService = $this->customerService->find($id);

            if (!$customerService) {
                return $this->ReturnHandle(false, '客服訊息不存在');
            }

            // 調用 Repository 的 updateStatus 方法
            $updatedCustomerService = $this->customerService->updateStatus($id, $isReplied);

            if (!$updatedCustomerService) {
                return $this->ReturnHandle(false, '狀態更新失敗');
            }

            $statusText = $isReplied ? '已處理' : '待處理';

            return $this->ReturnHandle(true, "訊息狀態已更新為 {$statusText}");

        } catch (\Exception $e) {
            Log::error('客服中心：狀態更新失敗', [
                'id' => $id,
                'is_replied' => $isReplied,
                'error' => $e->getMessage()
            ]);

            return $this->ReturnHandle(false, '狀態更新失敗，請稍後再試');
        }
    }

    /**
     * 更新管理員備註（另一個入口）
     */
    public function updateNote($id, $adminNote)
    {
        try {
            $customerService = $this->customerService->find($id);

            if (!$customerService) {
                return $this->ReturnHandle(false, '客服訊息不存在');
            }

            $this->customerService->updateAdminNote($id, $adminNote);

            return $this->ReturnHandle(true, '備註已更新');

        } catch (\Exception $e) {
            Log::error('客服中心：更新備註失敗', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);

            return $this->ReturnHandle(false, '更新備註失敗，請稍後再試');
        }
    }

    /**
     * 取得未回覆的訊息數量
     */
    public function getUnrepliedCount()
    {
        return $this->customerService->getUnrepliedCount();
    }
}