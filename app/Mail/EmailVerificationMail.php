<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EmailVerificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public User $user;
    public string $verificationUrl;
    public string $token;

    /**
     * Create a new message instance.
     *
     * @param User $user 用戶物件
     * @param string $token 驗證 token
     */
    public function __construct(User $user, string $token)
    {
        $this->user = $user;
        $this->token = $token;
        $this->verificationUrl = $this->buildVerificationUrl($token);
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '【信吉衛視】請驗證您的 Email 地址',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.verify-email',
            with: [
                'user' => $this->user,
                'verificationUrl' => $this->verificationUrl,
                'token' => $this->token,
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

    /**
     * 建立驗證 URL
     *
     * @param string $token
     * @return string
     */
    private function buildVerificationUrl(string $token): string
    {
        return url('/member/email-verify?token=' . $token);
    }
}
