<?php

namespace App\Mail;

use App\Models\Branch;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BranchCredentialsMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Branch $branch, public string $temporaryPassword)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Your Branch Panel Login Credentials');
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.branch-credentials',
            with: ['subject' => 'Your Branch Panel Login Credentials'],
        );
    }
}
