@extends('layouts.teacher')

@section('title', 'Result Details')
@section('page-title', 'Result Details')

@section('content')
    @include('results.partials.show', ['prefix' => 'teacher'])

    <section class="content-panel narrow-panel mt-4">
        <div class="panel-header">
            <div>
                <h2><i class="bi bi-chat-square-text-fill me-2 text-primary"></i>Teacher Remark</h2>
                <p>Add a remark for this result. Saving will email the result and remark as a PDF to the student{{ $attempt->student?->guardian_email ? ' and guardian' : '' }}.</p>
            </div>
        </div>

        @if ($attempt->teacher_remark)
            <div class="alert alert-light border mb-3 remark-display-text" style="white-space: pre-line;">
                {{ $attempt->teacher_remark }}
            </div>
            <p class="text-muted small mb-3">
                Last updated by {{ $attempt->teacherRemarkBy?->name ?? 'a teacher' }}
                on {{ $attempt->teacher_remark_at?->format('d M Y, h:i A') }}
            </p>
        @endif

        <form method="POST" action="{{ route('teacher.results.remark.store', $attempt) }}" class="admin-form" data-confirm-remark data-confirm-message="Are you sure you want to send this remark to the {{ $attempt->student?->guardian_email ? 'Student and Guardian' : 'Student' }}? This will email the result and remark as a PDF.">
            @csrf

            <div class="mb-3">
                <label for="remark" class="form-label">{{ $attempt->teacher_remark ? 'Update Remark' : 'Remark' }} <span class="required-mark">*</span></label>
                <textarea id="remark" name="remark" rows="4" class="form-control @error('remark') is-invalid @enderror" maxlength="2000" required>{{ old('remark', $attempt->teacher_remark) }}</textarea>
                @error('remark')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <button type="submit" class="btn btn-primary">
                <i class="bi bi-send-check-fill"></i>
                {{ $attempt->teacher_remark ? 'Update & Resend Email' : 'Save & Send Email' }}
            </button>
        </form>
    </section>
@endsection
