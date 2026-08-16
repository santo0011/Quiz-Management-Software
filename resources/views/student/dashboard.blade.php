@extends('layouts.student')

@section('title', 'Student Dashboard')
@section('page-title', 'Dashboard')

@section('content')
    <section class="student-hero" id="student-profile">
        <div>
            <span>{{ $student->branch?->name ?? 'Branch not assigned' }}</span>
            <h1>Welcome, {{ $student->student_name }}</h1>
            <p>{{ $student->schoolClass?->name ?? $student->class }} · {{ $student->email }}</p>
        </div>
    </section>

    <section class="student-stat-grid">
        <div class="student-stat"><span>Total Exams</span><strong>{{ $totalExams }}</strong></div>
        <div class="student-stat"><span>Completed</span><strong>{{ $completedExams }}</strong></div>
        <div class="student-stat"><span>Upcoming</span><strong>{{ $upcomingExams }}</strong></div>
        <div class="student-stat"><span>Available</span><strong>{{ $availableExams->count() }}</strong></div>
        <div class="student-stat"><span>Average Score</span><strong>{{ $averageScore }}%</strong></div>
        <div class="student-stat"><span>Passed</span><strong>{{ $passedExams }}</strong></div>
        <div class="student-stat"><span>Failed</span><strong>{{ $failedExams }}</strong></div>
    </section>

    <section class="student-section" id="available-exams">
        <div class="student-section-header">
            <div>
                <span>Available Exams</span>
                <h2>Ready To Attempt</h2>
            </div>
        </div>

        @if ($availableExams->isEmpty())
            <div class="empty-state">
                <i class="bi bi-journal-check"></i>
                <h3>No exams available</h3>
                <p>Published exams for your class will appear here during their scheduled time.</p>
            </div>
        @else
            <div class="exam-card-grid">
                @foreach ($availableExams as $exam)
                    <article class="exam-card">
                        <span class="status-badge status-published">Available</span>
                        <h3>{{ $exam->title }}</h3>
                        <p>{{ $exam->description ?: 'Read the instructions and begin when ready.' }}</p>
                        <dl>
                            <div><dt>Total Marks</dt><dd>{{ $exam->total_marks }}</dd></div>
                            <div><dt>Duration</dt><dd>{{ $exam->duration_minutes }} min</dd></div>
                            <div><dt>Passing</dt><dd>{{ $exam->passing_marks ?? 'Not set' }}</dd></div>
                            <div><dt>Questions</dt><dd>{{ $exam->questions_count }}</dd></div>
                            <div><dt>Ends</dt><dd>{{ $exam->ends_at?->format('d M Y, h:i A') ?? 'Open' }}</dd></div>
                            <div><dt>Attempts Left</dt><dd>{{ $exam->remainingAttemptsFor($student) }}</dd></div>
                        </dl>
                        <a href="{{ route('student.exams.show', $exam) }}" class="btn btn-primary w-100">
                            <i class="bi bi-play-circle-fill"></i>
                            Start Exam
                        </a>
                    </article>
                @endforeach
            </div>
        @endif
    </section>

    <section class="student-section">
        <div class="student-section-header">
            <div>
                <span>History</span>
                <h2>Recent Results</h2>
            </div>
            <a href="{{ route('student.results.index') }}" class="btn btn-soft">My Results</a>
        </div>
        @if ($recentResults->isEmpty())
            <div class="empty-state">
                <i class="bi bi-bar-chart"></i>
                <h3>No results yet</h3>
                <p>Your completed exam results will be listed here.</p>
            </div>
        @else
            <div class="result-list">
                @foreach ($recentResults as $attempt)
                    <a href="{{ route('student.results.show', $attempt) }}" class="result-row">
                        <div>
                            <strong>{{ $attempt->exam?->title }}</strong>
                            <span>{{ $attempt->submitted_at?->format('d M Y, h:i A') }}</span>
                        </div>
                        <span class="status-badge {{ $attempt->is_passed ? 'status-published' : 'status-closed' }}">{{ $attempt->percentage }}%</span>
                    </a>
                @endforeach
            </div>
        @endif
    </section>
@endsection
