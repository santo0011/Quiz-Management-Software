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
                    <article class="exam-card">
                        <span class="status-badge status-upcoming">Upcoming</span>
                        <h3>{{ $exam->title }}</h3>
                        <p>{{ $exam->description ?: 'This exam will be available at the scheduled start time.' }}</p>
                        <dl>
                            <div><dt>Start</dt><dd>{{ $exam->starts_at?->format('d M Y, h:i A') }}</dd></div>
                            <div><dt>Duration</dt><dd>{{ $exam->duration_minutes }} min</dd></div>
                            <div><dt>Total Marks</dt><dd>{{ $exam->total_marks }}</dd></div>
                            <div><dt>Questions</dt><dd>{{ $exam->questions_count }}</dd></div>
                        </dl>
                        <button class="btn btn-soft w-100" disabled>
                            <i class="bi bi-clock"></i>
                            Not Started Yet
                        </button>
                    </article>
                @endforeach
            </div>
            {{ $exams->links() }}
        @endif
    </section>
@endsection