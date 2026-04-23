<?php

namespace App\Mail;

use App\Models\CustomerService;
use App\Services\BasicWebsiteSettingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CustomerServiceReply extends Mailable
{
    use Queueable, SerializesModels;

    public CustomerService $customerService;
    public string $siteName;

    /**
     * Create a new message instance.
     */
    public function __construct(CustomerService $customerService)
    {
        $this->customerService = $customerService;

        // 獲取站點名稱
        $websiteService = app(BasicWebsiteSettingService::class);
        $websiteSettings = $websiteService->getSettings();
        $this->siteName = $websiteSettings['title']['zh_TW'] ?? config('app.name', 'SJTV');
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->customerService->reply_subject ?? 'Re: ' . $this->customerService->subject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.customer-service-reply',
            with: [
                'customerService' => $this->customerService,
                'siteName' => $this->siteName,
            ],
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
