@extends('layouts.student')

@section('title', $exam->title)
@section('page-title', 'Exam Instructions')

@section('content')

    {{-- Back Button --}}
    <div class="student-profile-top-actions">
        <a href="{{ route('student.exams.available') }}"
           class="btn btn-outline-secondary btn-student-back">
            <i class="bi bi-arrow-left"></i>
            Back
        </a>
    </div>


    {{-- Exam Instructions --}}
    <section class="student-section exam-instructions">

        <div class="student-section-header">
            <div>
                <span>{{ $exam->schoolClass?->name }}</span>
                <h2>{{ $exam->title }}</h2>
            </div>

            <span class="status-badge status-published">
                {{ $remainingAttempts }} attempts left
            </span>
        </div>


        {{-- Description --}}
        <p>
            {{ $exam->description ?: 'Please read the exam conditions carefully before starting.' }}
        </p>


        {{-- Exam Information Cards --}}
        <div class="student-info-card-grid">

            {{-- Total Marks --}}
            <div class="info-card color-blue">
                <div class="info-card-icon">
                    <i class="bi bi-trophy-fill"></i>
                </div>
                <span>Total Marks</span>
                <strong>{{ $exam->total_marks }}</strong>
            </div>


            {{-- Duration --}}
            <div class="info-card color-orange">
                <div class="info-card-icon">
                    <i class="bi bi-stopwatch-fill"></i>
                </div>
                <span>Duration</span>
                <strong>
                    {{ $exam->duration_minutes }}
                    <small>min</small>
                </strong>
            </div>


            {{-- Questions --}}
            <div class="info-card color-purple">
                <div class="info-card-icon">
                    <i class="bi bi-patch-question-fill"></i>
                </div>
                <span>Questions</span>
                <strong>{{ $exam->questions_count }}</strong>
            </div>


            {{-- Start Date --}}
            <div class="info-card color-teal">
                <div class="info-card-icon">
                    <i class="bi bi-play-circle-fill"></i>
                </div>
                <span>Starts</span>
                <strong>
                    {{ $exam->starts_at?->format('d M Y') ?? 'Open' }}
                </strong>
            </div>


            {{-- End Date --}}
            <div class="info-card color-red">
                <div class="info-card-icon">
                    <i class="bi bi-stop-circle-fill"></i>
                </div>
                <span>Ends</span>
                <strong>
                    {{ $exam->ends_at?->format('d M Y') ?? 'Open' }}
                </strong>
            </div>

        </div>


        {{-- Exam Warning --}}
        <div class="feedback-alert info mt-4">
            <i class="bi bi-info-circle-fill"></i>

            <div>
                @if ($hasActiveAttempt)
                    You already have this exam in progress. Continuing will resume your existing
                    timer from where you left off — it does not restart.
                @else
                    Once started, the timer continues until submission, even if you leave this page.
                    The exam auto-submits when time expires.
                @endif
            </div>
        </div>


        {{-- Start Exam Form --}}
        <form
            method="POST"
            action="{{ route('student.exams.start', $exam) }}"
            class="mt-4"
            id="beginExamForm"
        >
            @csrf

            <button
                class="btn btn-primary btn-lg"
                type="submit"
                id="beginExamButton"
                @disabled($remainingAttempts <= 0)
            >
                <i class="bi {{ $hasActiveAttempt ? 'bi-arrow-right-circle-fill' : 'bi-play-circle-fill' }}"></i>
                {{ $hasActiveAttempt ? 'Continue Exam' : 'Begin Exam' }}
            </button>

        </form>

    </section>

@endsection