<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

class ResetPasswordOtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public string $otp,
        public Carbon $expiresAt,
        public string $token
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Kode OTP Reset Password'
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.reset-password-otp',
            with: [
                'user' => $this->user,
                'otp' => $this->otp,
                'expiresAt' => $this->expiresAt,
                'token' => $this->token,
            ]
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
