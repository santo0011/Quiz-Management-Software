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
            <div class="student-exam-grid-2">
                @foreach ($exams as $exam)
                    @php($remainingAttempts = $exam->remainingAttemptsFor($student))
                    @php($dynamicStatus = $exam->dynamic_status ?? $exam->dynamicStatus($student))
                    <article class="exam-card">
                        @if ($dynamicStatus === 'upcoming')
                            <span class="status-badge status-upcoming">Upcoming</span>
                        @elseif ($dynamicStatus === 'expired')
                            <span class="status-badge status-closed">Expired</span>
                        @elseif ($dynamicStatus === 'completed')
                            <span class="status-badge status-published">Completed</span>
                        @else
                            <span class="status-badge status-published">Available</span>
                        @endif
                        <h3>{{ $exam->title }}</h3>
                        <p>{{ $exam->description ?: 'Read the instructions and begin when ready.' }}</p>
                        <dl>
                            <div><dt>Class</dt><dd>{{ $exam->schoolClass?->name ?? $student->class }}</dd></div>
                            <div><dt>Total Marks</dt><dd>{{ $exam->total_marks }}</dd></div>
                            <div><dt>Duration</dt><dd>{{ $exam->duration_minutes }} min</dd></div>
                            <div><dt>Questions</dt><dd>{{ $exam->questions_count }}</dd></div>
                            <div><dt>Ends</dt><dd>{{ $exam->ends_at?->format('d M Y, h:i A') ?? 'Open' }}</dd></div>
                            <div><dt>Attempts Left</dt><dd>{{ $remainingAttempts }}</dd></div>
                        </dl>
                        @if ($dynamicStatus === 'upcoming')
                            <button class="btn btn-soft w-100" disabled>
                                <i class="bi bi-clock"></i>
                                Not Started Yet
                            </button>
                        @elseif ($dynamicStatus === 'expired' || $remainingAttempts <= 0)
                            <button class="btn btn-outline-secondary w-100" disabled>
                                <i class="bi {{ $dynamicStatus === 'expired' ? 'bi-hourglass-split' : 'bi-check-circle-fill' }}"></i>
                                {{ $dynamicStatus === 'expired' ? 'Expired' : 'Completed' }}
                            </button>
                        @else
                            <a href="{{ route('student.exams.show', $exam) }}" class="btn btn-primary w-100">
                                <i class="bi bi-play-circle-fill"></i>
                                Start Exam
                            </a>
                        @endif
                    </article>
                @endforeach
            </div>
            {{ $exams->links() }}
        @endif
    </section>
@endsection