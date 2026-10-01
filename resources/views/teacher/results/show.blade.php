@extends('layouts.teacher')

@section('title', 'Result Details')
@section('page-title', 'Result Details')

@section('content')
    {{-- Same layout as the Branch result page: Back, then the action card
         (Teacher Remark) on top, then the result itself. --}}
    <div class="student-profile-top-actions">
        <a href="{{ route('teacher.results.index') }}" class="btn btn-outline-secondary btn-student-back">
            <i class="bi bi-arrow-left"></i>
            Back
        </a>
    </div>

    <section class="content-panel mb-4">
        <div class="panel-header">
            <div>
                <h2><i class="bi bi-chat-square-text-fill me-2 text-primary"></i>Teacher Remark</h2>
                <p>
                    Add a remark for this result. Saving will email the result and remark as a PDF to the student{{ $remarkAttempt->student?->guardian_email ? ' and guardian' : '' }}.
                    @if ((int) ($remarkAttempt->exam?->maximum_attempts ?? 1) > 1)
                        The student's latest attempt is always the one used — Attempt {{ $remarkAttempt->attemptLabel() }}.
                    @endif
                </p>
            </div>
        </div>

        @unless ($remarkAttempt->is($attempt))
            <div class="feedback-alert info mb-3">
                <i class="bi bi-info-circle-fill"></i>
                <div>
                    You are viewing Attempt {{ $attempt->attemptLabel() }}. Sending from here adds the remark to the latest attempt,
                    <a href="{{ route('teacher.results.show', $remarkAttempt) }}">Attempt {{ $remarkAttempt->attemptLabel() }}</a>
                    ({{ $remarkAttempt->obtained_marks }} / {{ $remarkAttempt->exam?->total_marks }} marks).
                </div>
            </div>
        @endunless

        @if ($remarkAttempt->teacher_remark)
            <div class="alert alert-light border mb-3 remark-display-text" style="white-space: pre-line;">
                {{ $remarkAttempt->teacher_remark }}
            </div>
            <p class="text-muted small mb-3">
                Last updated by {{ $remarkAttempt->teacherRemarkBy?->name ?? 'a teacher' }}
                on {{ $remarkAttempt->teacher_remark_at?->format('d M Y') }}
            </p>
        @endif

        <form method="POST" action="{{ route('teacher.results.remark.store', $remarkAttempt) }}" class="admin-form" data-confirm-remark data-confirm-message="Are you sure you want to send this remark to the {{ $remarkAttempt->student?->guardian_email ? 'Student and Guardian' : 'Student' }}? This will email the result and remark as a PDF.">
            @csrf

            <div class="mb-3">
                <label for="remark" class="form-label">{{ $remarkAttempt->teacher_remark ? 'Update Remark' : 'Remark' }} <span class="required-mark">*</span></label>
                <textarea id="remark" name="remark" rows="5" class="form-control @error('remark') is-invalid @enderror" maxlength="2000" placeholder="How the student did, strengths, areas to improve..." required>{{ old('remark', $remarkAttempt->teacher_remark) }}</textarea>
                @error('remark')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                <div class="form-text">This remark is included in the result PDF emailed to the student.</div>
            </div>

            <button type="submit" class="btn btn-primary">
                <i class="bi bi-send-check-fill"></i>
                {{ $remarkAttempt->teacher_remark ? 'Update & Resend Email' : 'Save & Send Email' }}
            </button>
        </form>
    </section>

    @include('results.partials.show', ['prefix' => 'teacher', 'hideBack' => true])
@endsection
