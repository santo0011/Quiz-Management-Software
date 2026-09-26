@extends('layouts.student')

@section('title', 'Attempt Submitted')
@section('page-title', 'Result Pending')

@section('content')
    <div class="student-profile-top-actions">
        <a href="{{ route('student.results.index') }}" class="btn btn-outline-secondary btn-student-back">
            <i class="bi bi-arrow-left"></i>
            Back
        </a>
    </div>

    <section class="student-section result-pending">
        <div class="result-pending-icon">
            <i class="bi bi-hourglass-split"></i>
        </div>
        <span class="attempt-chip attempt-chip-lg">Attempt {{ $attempt->attemptLabel() }} submitted</span>
        <h2>{{ $attempt->exam?->title }}</h2>
        <p>
            You have completed <strong>{{ $summary['used'] }} of {{ $summary['max'] }}</strong> allowed attempts.
            Your results will be shown after you complete all {{ $summary['max'] }} attempts.
        </p>

        <div class="result-pending-progress" aria-label="Attempts completed">
            @for ($i = 1; $i <= $summary['max']; $i++)
                <span class="{{ $i <= $summary['used'] ? 'done' : '' }}">{{ $i }}</span>
            @endfor
        </div>

        <div class="result-pending-actions">
            @if ($canTakeNextAttempt)
                <a href="{{ route('student.exams.show', $attempt->exam) }}" class="btn btn-primary">
                    <i class="bi bi-arrow-repeat"></i>
                    Take Attempt {{ $summary['used'] + 1 }} of {{ $summary['max'] }}
                </a>
            @endif
            <a href="{{ route('student.dashboard') }}" class="btn btn-soft">
                <i class="bi bi-house-door-fill"></i>
                Go to Dashboard
            </a>
        </div>
    </section>
@endsection
