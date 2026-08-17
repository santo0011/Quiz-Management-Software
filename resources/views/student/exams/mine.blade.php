@extends('layouts.student')

@section('title', 'My Exams')
@section('page-title', 'My Exams')

@section('content')
    <section class="student-section">
        <div class="student-section-header">
            <div>
                <span>Exam History</span>
                <h2>My Exams</h2>
            </div>
        </div>

        @if ($attempts->isEmpty())
            <div class="empty-state">
                <i class="bi bi-clipboard-check"></i>
                <h3>No exams attempted yet</h3>
                <p>Your completed exam attempts will be listed here.</p>
            </div>
        @else
            <div class="exam-history-list">
                @foreach ($attempts as $attempt)
                    <div class="exam-history-item">
                        <div class="exam-history-icon">
                            <i class="bi bi-journal-check"></i>
                        </div>
                        <div class="exam-history-info">
                            <h4>{{ $attempt->exam?->title }}</h4>
                            <span class="exam-history-date">
                                <i class="bi bi-calendar3"></i>
                                {{ $attempt->submitted_at?->format('d M Y, h:i A') }}
                            </span>
                        </div>
                        <div class="exam-history-score">
                            <span>Score</span>
                            <strong>{{ $attempt->obtained_marks }} / {{ $attempt->exam?->total_marks }}</strong>
                        </div>
                        <div class="exam-history-status">
                            <span class="status-badge {{ $attempt->is_passed ? 'status-published' : 'status-closed' }}">
                                {{ $attempt->is_passed ? 'Passed' : 'Failed' }}
                            </span>
                        </div>
                        <a href="{{ route('student.results.show', $attempt) }}" class="btn btn-sm btn-soft exam-history-action">
                            <i class="bi bi-eye-fill"></i> View Result
                        </a>
                    </div>
                @endforeach
            </div>
            {{ $attempts->links() }}
        @endif
    </section>
@endsection