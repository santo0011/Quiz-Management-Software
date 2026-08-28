<?php

namespace App\Mail;

use App\Models\Teacher;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TeacherCredentialsMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Teacher $teacher, public string $temporaryPassword)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Your Teacher Panel Login Credentials');
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.teacher-credentials',
            with: ['subject' => 'Your Teacher Panel Login Credentials'],
        );
    }
}
