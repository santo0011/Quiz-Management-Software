@extends('emails.layout')

@section('content')
    <h2 class="greeting" style="margin:0 0 12px; font-size:18px; font-weight:600; color:#1e293b;">Exam Result Available</h2>

    <div class="message" style="color:#475569; margin-bottom:20px;">
        <p style="margin:0 0 10px;">The exam result for <strong>{{ $attempt->student?->student_name }}</strong> has been reviewed by their teacher, and a remark has been added.</p>
        <p style="margin:0;">Please find the full result and teacher remark attached as a PDF.</p>
    </div>

    <div class="info-card" style="background-color:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:20px 24px; margin:20px 0;">
        <p class="label" style="margin:0 0 4px; font-size:12px; font-weight:600; text-transform:uppercase; letter-spacing:0.5px; color:#64748b;">Exam</p>
        <p class="value" style="margin:0; font-size:16px; font-weight:600; color:#1e293b;">{{ $attempt->exam?->title }}</p>

        <p class="label" style="margin:16px 0 4px; font-size:12px; font-weight:600; text-transform:uppercase; letter-spacing:0.5px; color:#64748b;">Result</p>
        <p class="value" style="margin:0; font-size:16px; font-weight:600; color:#1e293b;">{{ $attempt->obtained_marks }} / {{ $attempt->exam?->total_marks }} ({{ $attempt->percentage }}%) &middot; {{ $attempt->is_passed ? 'Passed' : 'Failed' }}</p>
    </div>

    <div class="message" style="color:#475569; margin-bottom:0;">
        <p style="margin:0;">If you have any questions about this result, please contact the branch directly.</p>
    </div>
@endsection
