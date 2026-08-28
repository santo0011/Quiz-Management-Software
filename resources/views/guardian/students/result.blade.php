@extends('layouts.guardian')

@section('title', $attempt->exam?->title)
@section('page-title', 'Result Details')

@section('content')
    @include('partials.format-time')

    <div class="student-profile-top-actions">
        <a href="{{ route('guardian.students.show', $student) }}" class="btn btn-outline-secondary btn-student-back">
            <i class="bi bi-arrow-left"></i>
            Back to {{ $student->student_name }}
        </a>
        <a href="{{ route('guardian.students.results.details', [$student, $attempt]) }}" class="btn btn-primary btn-student-back">
            <i class="bi bi-list-check"></i>
            Result Details
        </a>
    </div>

    @include('partials.guardian-student-switcher')

    <section class="student-section">
        <div class="student-section-header">
            <div>
                <span>{{ $student->student_name }}</span>
                <h2>{{ $attempt->exam?->title }}</h2>
            </div>
            <span class="status-badge {{ $attempt->is_passed ? 'status-published' : 'status-closed' }}">
                <i class="bi {{ $attempt->is_passed ? 'bi-check-circle-fill' : 'bi-x-circle-fill' }}"></i>
                {{ $attempt->is_passed ? 'Passed' : 'Failed' }}
            </span>
        </div>

        <div class="result-card-grid">
            <div class="result-card color-blue">
                <div class="result-card-left">
                    <div class="result-card-icon"><i class="bi bi-trophy-fill"></i></div>
                    <span>Total Marks</span>
                </div>
                <strong>{{ $attempt->exam?->total_marks }}</strong>
            </div>
            <div class="result-card color-green">
                <div class="result-card-left">
                    <div class="result-card-icon"><i class="bi bi-award-fill"></i></div>
                    <span>Obtained</span>
                </div>
                <strong>{{ $attempt->obtained_marks }}</strong>
            </div>
            <div class="result-card color-orange">
                <div class="result-card-left">
                    <div class="result-card-icon"><i class="bi bi-percent"></i></div>
                    <span>Percentage</span>
                </div>
                <strong>{{ $attempt->percentage }}<small>%</small></strong>
            </div>
            <div class="result-card color-purple">
                <div class="result-card-left">
                    <div class="result-card-icon"><i class="bi bi-check-circle-fill"></i></div>
                    <span>Correct</span>
                </div>
                <strong>{{ $attempt->correct_count }}</strong>
            </div>
            <div class="result-card color-red">
                <div class="result-card-left">
                    <div class="result-card-icon"><i class="bi bi-x-circle-fill"></i></div>
                    <span>Wrong</span>
                </div>
                <strong>{{ $attempt->wrong_count }}</strong>
            </div>
            <div class="result-card color-teal">
                <div class="result-card-left">
                    <div class="result-card-icon"><i class="bi bi-dash-circle-fill"></i></div>
                    <span>Unanswered</span>
                </div>
                <strong>{{ $attempt->unanswered_count }}</strong>
            </div>
            <div class="result-card color-blue">
                <div class="result-card-left">
                    <div class="result-card-icon"><i class="bi bi-flag-fill"></i></div>
                    <span>Passing Marks</span>
                </div>
                <strong>{{ $attempt->exam?->passing_marks ?? 'Not set' }}</strong>
            </div>
            <div class="result-card color-orange">
                <div class="result-card-left">
                    <div class="result-card-icon"><i class="bi bi-stopwatch-fill"></i></div>
                    <span>Time Taken</span>
                </div>
                <strong>{{ format_time_taken($attempt) }}</strong>
            </div>
        </div>

        <div class="student-details-grid mt-4">
            <div class="student-detail-item">
                <div class="student-detail-icon">
                    <i class="bi bi-people-fill"></i>
                </div>
                <div>
                    <dt>Grade</dt>
                    <dd>{{ $attempt->schoolClass?->name ?? '—' }}</dd>
                </div>
            </div>
            <div class="student-detail-item">
                <div class="student-detail-icon">
                    <i class="bi bi-calendar-check"></i>
                </div>
                <div>
                    <dt>Submitted On</dt>
                    <dd>{{ $attempt->submitted_at?->format('d M Y, h:i A') ?? '—' }}</dd>
                </div>
            </div>
            <div class="student-detail-item">
                <div class="student-detail-icon">
                    <i class="bi bi-hash"></i>
                </div>
                <div>
                    <dt>Attempt Number</dt>
                    <dd>{{ $attempt->attempt_number }}</dd>
                </div>
            </div>
        </div>
    </section>
@endsection
