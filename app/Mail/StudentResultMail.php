<?php

namespace App\Mail;

use App\Models\ExamAttempt;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * The Branch Panel's "Review & Send Result" email — attaches the already
 * generated `pdf.branch-result` bytes (never re-renders/recalculates
 * anything) and is sent to the Student's Zoho/OTP email address only.
 */
class StudentResultMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public ExamAttempt $attempt, public string $pdfBytes)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Your Exam Result - '.$this->attempt->exam?->title);
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.student-result',
            with: ['attempt' => $this->attempt],
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [
            Attachment::fromData(fn () => $this->pdfBytes, 'Exam-Result-'.$this->attempt->id.'.pdf')
                ->withMime('application/pdf'),
        ];
    }
}
