<?php

namespace App\Mail;

use App\Models\Student;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class StudentLoginOtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public string $otp, public ?Student $student = null)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Your QuizCore Student Login Verification Code');
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.login-otp',
            with: [
                'subject' => 'Your QuizCore Student Login Verification Code',
                'otp' => $this->otp,
                'name' => $this->student->student_name ?? 'Student',
                'typeLabel' => 'Student',
            ],
        );
    }
}
