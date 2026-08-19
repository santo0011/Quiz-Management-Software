<?php

namespace App\Mail;

use App\Models\Branch;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BranchOtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Branch $branch, public string $otp)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Your QuizCore Branch Password Reset Code');
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.branch-otp',
            with: ['subject' => 'Your QuizCore Branch Password Reset Code'],
        );
    }
}