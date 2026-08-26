<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SuperAdminLoginOtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public string $otp)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Your QuizCore Super Admin Login Verification Code');
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.login-otp',
            with: [
                'subject' => 'Your QuizCore Super Admin Login Verification Code',
                'otp' => $this->otp,
                'name' => 'Admin',
                'typeLabel' => 'Super Admin',
            ],
        );
    }
}
