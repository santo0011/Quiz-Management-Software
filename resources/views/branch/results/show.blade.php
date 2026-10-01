@extends('layouts.branch')

@section('title', 'Result Details')
@section('page-title', 'Result Details')

@section('content')
    <div class="student-profile-top-actions">
        <a href="{{ route('branch.results.index') }}" class="btn btn-outline-secondary btn-student-back">
            <i class="bi bi-arrow-left"></i>
            Back
        </a>
    </div>

    <section class="content-panel mb-4">
        <div class="panel-header">
            <div>
                <h2><i class="bi bi-send-check-fill me-2 text-primary"></i>Review &amp; Send Result</h2>
                @if ((int) ($sendAttempt->exam?->maximum_attempts ?? 1) > 1)
                    <p>The student's latest attempt is always the one sent — Attempt {{ $sendAttempt->attemptLabel() }}.</p>
                @endif
            </div>
        </div>

        @unless ($sendAttempt->is($attempt))
            <div class="feedback-alert info mb-3">
                <i class="bi bi-info-circle-fill"></i>
                <div>
                    You are viewing Attempt {{ $attempt->attemptLabel() }}. Sending from here sends the latest attempt,
                    <a href="{{ route('branch.results.show', $sendAttempt) }}">Attempt {{ $sendAttempt->attemptLabel() }}</a>
                    ({{ $sendAttempt->obtained_marks }} / {{ $sendAttempt->exam?->total_marks }} marks).
                </div>
            </div>
        @endunless

        @if ($sendAttempt->result_email_sent_at || $sendAttempt->branch_result_sent_at)
            <div class="alert alert-info d-flex flex-wrap gap-3 align-items-center mb-3">
                <i class="bi bi-info-circle-fill"></i>
                <div>
                    @if ($sendAttempt->result_email_sent_at)
                        <div>Emailed to <strong>{{ $sendAttempt->student?->guardian_email }}</strong> on {{ $sendAttempt->result_email_sent_at->format('d M Y') }}{{ $sendAttempt->resultEmailSentBy ? ' by '.$sendAttempt->resultEmailSentBy->name : '' }}.</div>
                    @endif
                    @if ($sendAttempt->branch_result_sent_at)
                        <div>Result sent on {{ $sendAttempt->branch_result_sent_at->format('d M Y') }}.</div>
                    @endif
                </div>
            </div>
        @endif

        @if ($sendAttempt->branch_review)
            <div class="alert alert-light border mb-3" style="white-space: pre-line;">
                {{ $sendAttempt->branch_review }}
            </div>
            <p class="text-muted small mb-3">
                Last reviewed by {{ $sendAttempt->branchReviewBy?->name ?? 'a branch user' }}
                on {{ $sendAttempt->branch_review_at?->format('d M Y') }}
            </p>
        @endif

        @if ($sendAttempt->branch_result_sent_at)
            {{-- Already sent: the review shown above is final. --}}
        @elseif (! $sendAttempt->student?->guardian_email)
            <div class="alert alert-warning">
                <i class="bi bi-exclamation-triangle-fill"></i>
                This student has no email on file from their OTP/Zoho verification yet, so the result cannot be sent until they log in at least once.
            </div>
        @else
            <form method="POST" action="{{ route('branch.results.send', $sendAttempt) }}" class="admin-form" data-confirm-send-result data-confirm-message="Send this result to {{ $sendAttempt->student?->guardian_email }} and submit it to Zoho? A result can only be sent once and the review cannot be changed afterwards.">
                @csrf

                <div class="mb-3">
                    <label for="review" class="form-label">{{ $sendAttempt->branch_review ? 'Update Review / Feedback' : 'Review / Feedback' }} <span class="required-mark">*</span></label>
                    <textarea id="review" name="review" rows="5" class="form-control @error('review') is-invalid @enderror" maxlength="2000" placeholder="How the exam went, student performance, strengths, areas that need improvement, and any additional feedback..." required>{{ old('review', $sendAttempt->branch_review) }}</textarea>
                    @error('review')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <div class="form-text">This review is included in the result PDF sent to the student.</div>
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-send-check-fill"></i>
                    Send Result
                </button>
            </form>
        @endif
    </section>

    @include('results.partials.show', ['prefix' => 'branch', 'hideBack' => true])
@endsection
