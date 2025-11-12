<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class VerifyEmailOtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public string $otp,
        public int $validMinutes
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Kode OTP Verifikasi Email'
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.verify_email_otp',
            with: [
                'name' => $this->user->name ?? 'Warga',
                'otp' => $this->otp,
                'validMinutes' => $this->validMinutes,
            ]
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
