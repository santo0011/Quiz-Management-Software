<?php

namespace App\Mail;

use App\Models\Teacher;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TeacherOtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Teacher $teacher, public string $otp)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Your QuizCore Teacher Password Reset Code');
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.teacher-otp',
            with: ['subject' => 'Your QuizCore Teacher Password Reset Code'],
        );
    }
}
