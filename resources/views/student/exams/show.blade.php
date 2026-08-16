@extends('layouts.student')

@section('title', $exam->title)
@section('page-title', 'Exam Instructions')

@section('content')
    <section class="student-section exam-instructions">
        <div class="student-section-header">
            <div>
                <span>{{ $exam->schoolClass?->name }}</span>
                <h2>{{ $exam->title }}</h2>
            </div>
            <span class="status-badge status-published">{{ $remainingAttempts }} attempts left</span>
        </div>

        <p>{{ $exam->description ?: 'Please read the exam conditions carefully before starting.' }}</p>

        <div class="student-stat-grid">
            <div class="student-stat"><span>Total Marks</span><strong>{{ $exam->total_marks }}</strong></div>
            <div class="student-stat"><span>Passing Marks</span><strong>{{ $exam->passing_marks ?? 'Not set' }}</strong></div>
            <div class="student-stat"><span>Duration</span><strong>{{ $exam->duration_minutes }} min</strong></div>
            <div class="student-stat"><span>Questions</span><strong>{{ $exam->questions_count }}</strong></div>
            <div class="student-stat"><span>Starts</span><strong>{{ $exam->starts_at?->format('d M, h:i A') ?? 'Open' }}</strong></div>
            <div class="student-stat"><span>Ends</span><strong>{{ $exam->ends_at?->format('d M, h:i A') ?? 'Open' }}</strong></div>
        </div>

        <div class="feedback-alert info mt-4">
            <i class="bi bi-info-circle-fill"></i>
            <div>Once started, the timer continues until submission. The exam auto-submits when time expires.</div>
        </div>

        <form method="POST" action="{{ route('student.exams.start', $exam) }}" class="mt-4">
            @csrf
            <button class="btn btn-primary btn-lg" type="submit" @disabled($remainingAttempts <= 0)>
                <i class="bi bi-play-circle-fill"></i>
                Begin Exam
            </button>
        </form>
    </section>
@endsection
