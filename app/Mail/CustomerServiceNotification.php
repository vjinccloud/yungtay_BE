<?php

namespace App\Mail;

use App\Models\CustomerService;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CustomerServiceNotification extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public CustomerService $customerService
    ) {
        //
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '【客服中心】新訊息通知：' . $this->customerService->subject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        // 取得網站設定
        $websiteSettings = app(\App\Services\BasicWebsiteSettingService::class)->getSettings();

        // 取得中文站點名稱，如果沒有則使用預設值
        $siteName = $websiteSettings['title']['zh_TW'] ?? config('app.name');

        return new Content(
            view: 'emails.customer-service-notification',
            with: [
                'customerService' => $this->customerService,
                'siteName' => $siteName,
            ]
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}