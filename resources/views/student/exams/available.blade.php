@extends('layouts.student')

@section('title', 'Available Exams')
@section('page-title', 'Available Exams')

@section('content')
    <section class="student-section">
        <div class="student-section-header">
            <div>
                <span>Ready To Attend</span>
                <h2>Available Exams</h2>
            </div>
        </div>

        @if ($exams->isEmpty())
            <div class="empty-state">
                <i class="bi bi-journal-check"></i>
                <h3>No exams available</h3>
                <p>Published exams for your class will appear here during their scheduled time.</p>
            </div>
        @else
            <div class="exam-card-grid">
                @foreach ($exams as $exam)
                    <article class="exam-card">
                        <span class="status-badge status-published">Available</span>
                        <h3>{{ $exam->title }}</h3>
                        <p>{{ $exam->description ?: 'Read the instructions and begin when ready.' }}</p>
                        <dl>
                            <div><dt>Class</dt><dd>{{ $exam->schoolClass?->name ?? $student->class }}</dd></div>
                            <div><dt>Total Marks</dt><dd>{{ $exam->total_marks }}</dd></div>
                            <div><dt>Duration</dt><dd>{{ $exam->duration_minutes }} min</dd></div>
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
            {{ $exams->links() }}
        @endif
    </section>
@endsection