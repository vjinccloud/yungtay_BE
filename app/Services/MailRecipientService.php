<?php

namespace App\Services;

use App\Repositories\MailRecipientRepository;
use App\Services\BaseService;
use Illuminate\Database\Eloquent\Collection;

class MailRecipientService extends BaseService
{
    /**
     * 建構子
     */
    public function __construct(
        private MailRecipientRepository $mailRecipient
    ) {
        parent::__construct($mailRecipient);
    }

    /**
     * 取得收件信箱分頁資料
     */
    public function getMailRecipients($perPage = 15, $sortColumn = 'updated_at', $sortDirection = 'desc', $filters = [])
    {
        return $this->mailRecipient->paginate($perPage, $sortColumn, $sortDirection, $filters);
    }

    /**
     * 取得所有收件類型
     */
    public function getAllMailTypes(): Collection
    {
        return $this->mailRecipient->getAllMailTypes();
    }

    /**
     * 儲存收件信箱
     */
    public function save(array $attributes, $id = null)
    {
        try {
            // 直接使用 Repository 儲存，Observer 會自動處理事件記錄
            $mailRecipient = $this->mailRecipient->save($attributes, $id);

            // 設定成功訊息
            $message = $id ? '收件信箱更新成功' : '收件信箱建立成功';

            return $this->ReturnHandle(true, $message, 'admin.mail-recipients');

        } catch (\Exception $e) {
            \Log::error('儲存收件信箱時發生錯誤: ' . $e->getMessage(), [
                'data' => $attributes,
                'id' => $id,
                'trace' => $e->getTraceAsString()
            ]);

            return $this->ReturnHandle(false, '儲存失敗，請稍後再試');
        }
    }

    /**
     * 刪除收件信箱
     */
    public function delete($id)
    {
        try {
            // 查找收件信箱
            $mailRecipient = $this->mailRecipient->find($id);
            if (!$mailRecipient) {
                return $this->ReturnHandle(false, '收件信箱不存在');
            }

            // 直接使用 Repository 刪除，Observer 會自動處理事件記錄
            $this->mailRecipient->delete($id);

            return $this->ReturnHandle(true, '收件信箱刪除成功');

        } catch (\Exception $e) {
            \Log::error('刪除收件信箱時發生錯誤: ' . $e->getMessage(), [
                'id' => $id,
                'trace' => $e->getTraceAsString()
            ]);

            return $this->ReturnHandle(false, '刪除失敗，請稍後再試');
        }
    }

    /**
     * 切換收件信箱狀態
     */
    public function toggleStatus($id)
    {
        try {
            // 查找收件信箱
            $mailRecipient = $this->mailRecipient->find($id);
            if (!$mailRecipient) {
                return $this->ReturnHandle(false, '收件信箱不存在');
            }

            // 切換狀態（直接在模型上操作，確保觸發 Observer）
            $mailRecipient->status = !$mailRecipient->status;
            $mailRecipient->save();

            // 自訂成功訊息
            $statusText = $mailRecipient->status ? '啟用' : '停用';
            return $this->ReturnHandle(true, "收件信箱{$statusText}成功");

        } catch (\Exception $e) {
            \Log::error('切換收件信箱狀態時發生錯誤: ' . $e->getMessage(), [
                'id' => $id,
                'trace' => $e->getTraceAsString()
            ]);

            return $this->ReturnHandle(false, '狀態切換失敗，請稍後再試');
        }
    }

    /**
     * 取得啟用的收件信箱
     */
    public function getActiveMailRecipients(): Collection
    {
        return $this->mailRecipient->getActiveMailRecipients();
    }
}