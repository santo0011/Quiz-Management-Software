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
            <span class="status-badge {{ $attempt->is_passed ? 'status-published' : 'status-closed' }}">
                <i class="bi {{ $attempt->is_passed ? 'bi-check-circle-fill' : 'bi-x-circle-fill' }}"></i>
                {{ $attempt->is_passed ? 'Passed' : 'Failed' }}
            </span>
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
            <span class="question-count-badge">
                <i class="bi bi-file-earmark-text"></i>
                {{ $attempt->answers->count() }} {{ Str::plural('Answer', $attempt->answers->count()) }}
            </span>
        </div>
        <div class="question-list">
            @foreach ($attempt->answers as $answer)
                <article class="question-admin-item">
                    <div class="question-item-header">
                        <div class="question-number-badge">
                            {{ $loop->iteration }}
                        </div>
                        <div class="question-item-content">
                            <div class="question-item-meta">
                                <span class="question-marks-badge {{ $answer->is_correct ? 'correct' : ($answer->question_option_id ? 'wrong' : 'unanswered') }}">
                                    <i class="bi {{ $answer->is_correct ? 'bi-check-circle-fill' : ($answer->question_option_id ? 'bi-x-circle-fill' : 'bi-dash-circle-fill') }}"></i>
                                    {{ $answer->is_correct ? 'Correct' : ($answer->question_option_id ? 'Wrong' : 'Unanswered') }}
                                </span>
                            </div>
                            <h3 class="math-content question-item-text">{{ $answer->question?->question_text }}</h3>
                        </div>
                    </div>
                    <div class="option-preview">
                        <div class="option-preview-item {{ $answer->is_correct ? 'correct' : '' }}">
                            <span class="option-letter">
                                <i class="bi bi-person-check-fill"></i>
                            </span>
                            <span class="math-content option-text"><strong>Your answer:</strong> {{ $answer->selectedOption?->option_text ?? 'Not answered' }}</span>
                        </div>
                        @if (!$answer->is_correct)
                            <div class="option-preview-item correct">
                                <span class="option-letter">
                                    <i class="bi bi-check2-circle"></i>
                                </span>
                                <span class="math-content option-text"><strong>Correct:</strong> {{ $answer->question?->options?->firstWhere('is_correct', true)?->option_text }}</span>
                            </div>
                        @endif
                    </div>
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