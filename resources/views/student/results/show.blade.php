@extends('layouts.student')

@section('title', 'Result Details')
@section('page-title', 'Result Details')

@section('content')
    <section class="student-section">
        <div class="student-section-header">
            <div>
                <span>{{ $attempt->student?->student_name }}</span>
                <h2>{{ $attempt->exam?->title }}</h2>
            </div>
            <span class="status-badge {{ $attempt->is_passed ? 'status-published' : 'status-closed' }}">{{ $attempt->is_passed ? 'Passed' : 'Failed' }}</span>
        </div>

        <div class="student-stat-grid">
            <div class="student-stat"><span>Total Marks</span><strong>{{ $attempt->exam?->total_marks }}</strong></div>
            <div class="student-stat"><span>Obtained</span><strong>{{ $attempt->obtained_marks }}</strong></div>
            <div class="student-stat"><span>Percentage</span><strong>{{ $attempt->percentage }}%</strong></div>
            <div class="student-stat"><span>Correct</span><strong>{{ $attempt->correct_count }}</strong></div>
            <div class="student-stat"><span>Wrong</span><strong>{{ $attempt->wrong_count }}</strong></div>
            <div class="student-stat"><span>Unanswered</span><strong>{{ $attempt->unanswered_count }}</strong></div>
            <div class="student-stat"><span>Passing Marks</span><strong>{{ $attempt->exam?->passing_marks ?? 'Not set' }}</strong></div>
            <div class="student-stat"><span>Time Taken</span><strong>{{ $attempt->submitted_at?->diffInMinutes($attempt->started_at) ?? 0 }} min</strong></div>
        </div>
    </section>

    <section class="student-section">
        <div class="student-section-header">
            <div>
                <span>Review</span>
                <h2>Answer Summary</h2>
            </div>
        </div>
        <div class="question-list">
            @foreach ($attempt->answers as $answer)
                <article class="question-admin-item">
                    <span class="page-kicker">{{ $answer->is_correct ? 'Correct' : ($answer->question_option_id ? 'Wrong' : 'Unanswered') }}</span>
                    <h3 class="math-content">{{ $answer->question?->question_text }}</h3>
                    <p class="math-content mb-0"><strong>Your answer:</strong> {{ $answer->selectedOption?->option_text ?? 'Not answered' }}</p>
                </article>
            @endforeach
        </div>
    </section>

    @push('scripts')
        <script>
            window.MathJax = { tex: { inlineMath: [['$', '$'], ['\\(', '\\)']] } };
        </script>
        <script async src="https://cdn.jsdelivr.net/npm/mathjax@3/es5/tex-mml-chtml.js"></script>
    @endpush
@endsection
