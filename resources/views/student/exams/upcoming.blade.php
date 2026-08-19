@extends('layouts.student')

@section('title', 'Upcoming Exams')
@section('page-title', 'Upcoming Exams')

@section('content')
    <section class="student-section">
        <div class="student-section-header">
            <div>
                <span>Scheduled</span>
                <h2>Upcoming Exams</h2>
            </div>
        </div>

        @if ($exams->isEmpty())
            <div class="empty-state">
                <i class="bi bi-calendar-event"></i>
                <h3>No upcoming exams</h3>
                <p>You have no scheduled exams at the moment.</p>
            </div>
        @else
            <div class="exam-card-grid">
                @foreach ($exams as $exam)
                    @php($dynamicStatus = $exam->dynamic_status ?? $exam->dynamicStatus($student))
                    <article class="exam-card">
                        @if ($dynamicStatus === 'upcoming')
                            <span class="status-badge status-upcoming">Upcoming</span>
                        @elseif ($dynamicStatus === 'available')
                            <span class="status-badge status-published">Available</span>
                        @elseif ($dynamicStatus === 'expired')
                            <span class="status-badge status-closed">Expired</span>
                        @else
                            <span class="status-badge status-published">Completed</span>
                        @endif
                        <h3>{{ $exam->title }}</h3>
                        <p>{{ $exam->description ?: 'This exam will be available at the scheduled start time.' }}</p>
                        <dl>
                            <div><dt>Start</dt><dd>{{ $exam->starts_at?->format('d M Y, h:i A') }}</dd></div>
                            <div><dt>Duration</dt><dd>{{ $exam->duration_minutes }} min</dd></div>
                            <div><dt>Total Marks</dt><dd>{{ $exam->total_marks }}</dd></div>
                            <div><dt>Questions</dt><dd>{{ $exam->questions_count }}</dd></div>
                        </dl>
                        @if ($dynamicStatus === 'available')
                            <a href="{{ route('student.exams.available') }}" class="btn btn-primary w-100">
                                <i class="bi bi-play-circle-fill"></i>
                                Start Exam
                            </a>
                        @else
                            <button class="btn btn-soft w-100" disabled>
                                <i class="bi bi-clock"></i>
                                Not Started Yet
                            </button>
                        @endif
                    </article>
                @endforeach
            </div>
            {{ $exams->links() }}
        @endif
    </section>
@endsection