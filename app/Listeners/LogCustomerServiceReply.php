<?php

namespace App\Listeners;

use App\Events\CustomerServiceReplied;
use App\Models\OperationLog;

class LogCustomerServiceReply
{
    public function handle(CustomerServiceReplied $event): void
    {
        $customerService = $event->customerService;

        OperationLog::create([
            'created_by' => auth('admin')->id(),
            'action_type' => 'Reply',
            'message' => '[回覆信件]' . $customerService->subject,
            'ip_address' => request()->ip(),
            'attachable_type' => 'Reply',
            'attachable_id' => $customerService->id,
            'details' => json_encode([
                'customer_service_id' => $customerService->id,
                'customer_email' => $customerService->email,
                'customer_name' => $customerService->name,
                'original_subject' => $customerService->subject,
                'reply_subject' => $customerService->reply_subject,
                'reply_content' => $customerService->reply_content,
                'replied_at' => $customerService->replied_at,
            ], JSON_UNESCAPED_UNICODE),
        ]);
    }
}