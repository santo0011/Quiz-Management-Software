<?php

namespace App\Mail;

use App\Models\ExamAttempt;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ResultRemarkMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public ExamAttempt $attempt)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Exam Result & Teacher Remark - '.$this->attempt->exam?->title);
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.result-remark',
            with: [
                'subject' => 'Exam Result & Teacher Remark - '.$this->attempt->exam?->title,
                'attempt' => $this->attempt,
            ],
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        $attempt = $this->attempt;

        return [
            Attachment::fromData(
                fn () => Pdf::loadView('pdf.result-remark', ['attempt' => $attempt])->output(),
                'Exam-Result-'.$attempt->id.'.pdf'
            )->withMime('application/pdf'),
        ];
    }
}
