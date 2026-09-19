@extends('emails.layout')

@section('content')
    <h2 class="greeting" style="margin:0 0 12px; font-size:18px; font-weight:600; color:#1e293b;">Your Exam Result is Ready</h2>

    <div class="message" style="color:#475569; margin-bottom:20px;">
        <p style="margin:0 0 10px;">Dear <strong>{{ $attempt->student?->student_name }}</strong>,</p>
        <p style="margin:0;">Your result for <strong>{{ $attempt->exam?->title }}</strong> has been reviewed and is attached to this email as a PDF.</p>
    </div>

    <div class="info-card" style="background-color:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:20px 24px; margin:20px 0;">
        <p class="label" style="margin:0 0 4px; font-size:12px; font-weight:600; text-transform:uppercase; letter-spacing:0.5px; color:#64748b;">Subject</p>
        <p class="value" style="margin:0; font-size:16px; font-weight:600; color:#1e293b;">{{ $attempt->exam?->subject?->name ?? '—' }}</p>

        <p class="label" style="margin:16px 0 4px; font-size:12px; font-weight:600; text-transform:uppercase; letter-spacing:0.5px; color:#64748b;">Class</p>
        <p class="value" style="margin:0; font-size:16px; font-weight:600; color:#1e293b;">{{ $attempt->zoho_class_name ?? '—' }}</p>

        <p class="label" style="margin:16px 0 4px; font-size:12px; font-weight:600; text-transform:uppercase; letter-spacing:0.5px; color:#64748b;">Grade</p>
        <p class="value" style="margin:0; font-size:16px; font-weight:600; color:#1e293b;">{{ $attempt->zoho_grade ?? $attempt->schoolClass?->name ?? '—' }}</p>

        <p class="label" style="margin:16px 0 4px; font-size:12px; font-weight:600; text-transform:uppercase; letter-spacing:0.5px; color:#64748b;">Result</p>
        <p class="value" style="margin:0; font-size:16px; font-weight:600; color:#1e293b;">{{ $attempt->obtained_marks }} / {{ $attempt->exam?->total_marks }} ({{ $attempt->percentage }}%) &middot; {{ $attempt->is_passed ? 'Passed' : 'Failed' }}</p>
    </div>

    <div class="message" style="color:#475569; margin-bottom:0;">
        <p style="margin:0;">If you have any questions about this result, please contact your branch directly.</p>
    </div>
@endsection
