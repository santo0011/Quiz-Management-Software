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
            </div>
        </div>

        @if ($attempt->result_email_sent_at || $attempt->zoho_result_synced_at)
            <div class="alert alert-info d-flex flex-wrap gap-3 align-items-center mb-3">
                <i class="bi bi-info-circle-fill"></i>
                <div>
                    @if ($attempt->result_email_sent_at)
                        <div>Emailed to <strong>{{ $attempt->student?->guardian_email }}</strong> on {{ $attempt->result_email_sent_at->format('d M Y') }}{{ $attempt->resultEmailSentBy ? ' by '.$attempt->resultEmailSentBy->name : '' }}.</div>
                    @endif
                    @if ($attempt->zoho_result_synced_at)
                        <div>Sent to Zoho on {{ $attempt->zoho_result_synced_at->format('d M Y') }}.</div>
                    @endif
                </div>
            </div>
        @endif

        @if ($attempt->branch_review)
            <div class="alert alert-light border mb-3" style="white-space: pre-line;">
                {{ $attempt->branch_review }}
            </div>
            <p class="text-muted small mb-3">
                Last reviewed by {{ $attempt->branchReviewBy?->name ?? 'a branch user' }}
                on {{ $attempt->branch_review_at?->format('d M Y') }}
            </p>
        @endif

        @if (! $attempt->student?->guardian_email)
            <div class="alert alert-warning">
                <i class="bi bi-exclamation-triangle-fill"></i>
                This student has no email on file from their OTP/Zoho verification yet, so the result cannot be sent until they log in at least once.
            </div>
        @else
            <form method="POST" action="{{ route('branch.results.send', $attempt) }}" class="admin-form" data-confirm-send-result data-confirm-message="Send this result to {{ $attempt->student?->guardian_email }} and submit it to Zoho?">
                @csrf

                <div class="mb-3">
                    <label for="review" class="form-label">{{ $attempt->branch_review ? 'Update Review / Feedback' : 'Review / Feedback' }} <span class="required-mark">*</span></label>
                    <textarea id="review" name="review" rows="5" class="form-control @error('review') is-invalid @enderror" maxlength="2000" placeholder="How the exam went, student performance, strengths, areas that need improvement, and any additional feedback..." required>{{ old('review', $attempt->branch_review) }}</textarea>
                    @error('review')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <div class="form-text">This review is included in the result PDF sent to the student.</div>
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-send-check-fill"></i>
                    {{ $attempt->zoho_result_synced_at ? 'Resend Result' : 'Review & Send Result' }}
                </button>
            </form>
        @endif
    </section>

    @include('results.partials.show', ['prefix' => 'branch', 'hideBack' => true])
@endsection
