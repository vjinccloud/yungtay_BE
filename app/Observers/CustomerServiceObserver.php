<?php

namespace App\Observers;

use App\Models\CustomerService;
use App\Services\MailRecipientService;
use App\Services\NotificationService;
use App\Services\EventService;
use App\Mail\CustomerServiceNotification;
use App\Mail\CustomerServiceReply;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class CustomerServiceObserver
{
    protected $mailRecipientService;
    protected $notificationService;
    protected $eventService;

    public function __construct()
    {
        $this->mailRecipientService = app(MailRecipientService::class);
        $this->notificationService = app(NotificationService::class);
        $this->eventService = app(EventService::class);
    }

    /**
     * 當新的客服訊息建立時
     */
    public function created(CustomerService $customerService): void
    {
        try {
            // 取得客服類型的收件信箱
            $recipients = $this->getCustomerServiceRecipients();
            
            if ($recipients->isEmpty()) {
                Log::warning('客服中心：沒有設定收件信箱');
                return;
            }

            // 發送通知給所有客服信箱
            foreach ($recipients as $recipient) {
                Mail::to($recipient->email)
                    ->send(new CustomerServiceNotification($customerService));
            }

            // 建立後台管理員通知
            $this->notificationService->createCustomerServiceNotification($customerService);

        } catch (\Exception $e) {
            Log::error('客服中心：發送通知失敗', [
                'error' => $e->getMessage(),
                'customer_service_id' => $customerService->id
            ]);
        }
    }

    /**
     * 當客服訊息更新時（主要處理回覆）
     */
    public function updated(CustomerService $customerService): void
    {
        // 檢查是否剛剛回覆（is_replied 從 false 變成 true）
        if ($customerService->isDirty('is_replied') && $customerService->is_replied) {
            $this->sendReplyEmail($customerService);
        }

        // 檢查是否更新了管理員備註
        if ($customerService->isDirty('admin_note')) {
            $this->logAdminNoteUpdate($customerService);
        }

        // 檢查是否切換了處理狀態（不包含回覆情況，避免重複記錄）
        if ($customerService->isDirty('is_replied') && !$customerService->reply_subject) {
            $this->logStatusUpdate($customerService);
        }
    }

    /**
     * 發送回覆郵件給客戶
     */
    protected function sendReplyEmail(CustomerService $customerService): void
    {
        try {
            // 發送回覆郵件給客戶
            Mail::to($customerService->email)
                ->send(new CustomerServiceReply($customerService));

            Log::info('客服中心：已發送回覆郵件', [
                'id' => $customerService->id,
                'to' => $customerService->email,
                'reply_subject' => $customerService->reply_subject
            ]);

        } catch (\Exception $e) {
            Log::error('客服中心：發送回覆郵件失敗', [
                'error' => $e->getMessage(),
                'customer_service_id' => $customerService->id,
                'to' => $customerService->email
            ]);
        }
    }

    /**
     * 取得客服類型的收件信箱
     */
    protected function getCustomerServiceRecipients()
    {
        // 假設收件類型表中有「客服中心」類型，ID = 1
        // 或者可以用名稱查詢
        $mailTypes = \App\Models\MailType::where('name', '客服中心')
                                         ->orWhere('id', 1)
                                         ->first();
        
        if (!$mailTypes) {
            return collect();
        }

        // 取得該類型下啟用的收件信箱
        return \App\Models\MailRecipient::where('type_id', $mailTypes->id)
                                        ->where('status', true)
                                        ->get();
    }

    /**
     * Handle the CustomerService "deleted" event.
     */
    public function deleted(CustomerService $customerService): void
    {
        // 可以在這裡記錄刪除日誌
        Log::info('客服中心：訊息已刪除', [
            'id' => $customerService->id,
            'subject' => $customerService->subject
        ]);
    }

    /**
     * Handle the CustomerService "restored" event.
     */
    public function restored(CustomerService $customerService): void
    {
        //
    }

    /**
     * Handle the CustomerService "force deleted" event.
     */
    public function forceDeleted(CustomerService $customerService): void
    {
        //
    }

    /**
     * 記錄管理員備註更新操作
     */
    protected function logAdminNoteUpdate(CustomerService $customerService): void
    {
        try {
            // 確保 updated_by 被設定（以防 BaseModelTrait 沒有正常運作）
            if (!$customerService->updated_by) {
                $customerService->updated_by = auth('admin')->id();
            }

            // 設定操作紀錄標題並觸發事件
            $customerService->event_title = "信件主旨:{$customerService->subject} 更新備註";
            $this->eventService->fireDataUpdated($customerService);
        } catch (\Exception $e) {
            Log::warning('客服中心：記錄備註更新操作失敗', [
                'id' => $customerService->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * 記錄狀態更新操作
     */
    protected function logStatusUpdate(CustomerService $customerService): void
    {
        try {
            // 確保 updated_by 被設定
            if (!$customerService->updated_by) {
                $customerService->updated_by = auth('admin')->id();
            }

            $statusText = $customerService->is_replied ? '已處理' : '待處理';

            // 設定操作紀錄標題並觸發事件
            $customerService->event_title = "信件主旨:{$customerService->subject} 狀態更新為{$statusText}";
            $this->eventService->fireDataUpdated($customerService);
        } catch (\Exception $e) {
            Log::warning('客服中心：記錄狀態更新操作失敗', [
                'id' => $customerService->id,
                'error' => $e->getMessage()
            ]);
        }
    }
}