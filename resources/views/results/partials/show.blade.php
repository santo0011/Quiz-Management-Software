@php($prefix = $prefix ?? 'admin')

<section class="content-panel exam-details-panel">
    <div class="panel-header">
        <div>
            <h2><i class="bi bi-award me-2 text-primary"></i>{{ $attempt->exam?->title }}</h2>
            <p>{{ $attempt->student?->student_name }} · Attempt {{ $attempt->attempt_number }}</p>
        </div>
        <span class="status-badge {{ $attempt->is_passed ? 'status-published' : 'status-closed' }}">
            <i class="bi {{ $attempt->is_passed ? 'bi-check-circle-fill' : 'bi-x-circle-fill' }}"></i>
            {{ $attempt->is_passed ? 'Passed' : 'Failed' }}
        </span>
    </div>

    <div class="exam-stats-grid">
        <div class="exam-stat-card">
            <div class="exam-stat-icon primary">
                <i class="bi bi-trophy-fill"></i>
            </div>
            <div class="exam-stat-body">
                <span>Obtained Marks</span>
                <strong>{{ $attempt->obtained_marks }}</strong>
            </div>
        </div>
        <div class="exam-stat-card">
            <div class="exam-stat-icon accent">
                <i class="bi bi-patch-check-fill"></i>
            </div>
            <div class="exam-stat-body">
                <span>Total Marks</span>
                <strong>{{ $attempt->exam?->total_marks }}</strong>
            </div>
        </div>
        <div class="exam-stat-card">
            <div class="exam-stat-icon success">
                <i class="bi bi-percent"></i>
            </div>
            <div class="exam-stat-body">
                <span>Percentage</span>
                <strong>{{ $attempt->percentage }}<small>%</small></strong>
            </div>
        </div>
        <div class="exam-stat-card">
            <div class="exam-stat-icon warning">
                <i class="bi bi-stopwatch-fill"></i>
            </div>
            <div class="exam-stat-body">
                <span>Time Taken</span>
                <strong>{{ $attempt->submitted_at?->diffInMinutes($attempt->started_at) ?? 0 }} <small>min</small></strong>
            </div>
        </div>
    </div>

    <div class="exam-details-grid mt-3">
        <div class="exam-detail-item">
            <div class="exam-detail-icon">
                <i class="bi bi-person-fill"></i>
            </div>
            <div>
                <dt>Student</dt>
                <dd>{{ $attempt->student?->student_name }}</dd>
            </div>
        </div>
        <div class="exam-detail-item">
            <div class="exam-detail-icon">
                <i class="bi bi-people-fill"></i>
            </div>
            <div>
                <dt>Class</dt>
                <dd>{{ $attempt->schoolClass?->name }}</dd>
            </div>
        </div>
        <div class="exam-detail-item">
            <div class="exam-detail-icon success">
                <i class="bi bi-check-circle-fill"></i>
            </div>
            <div>
                <dt>Correct</dt>
                <dd>{{ $attempt->correct_count }}</dd>
            </div>
        </div>
        <div class="exam-detail-item">
            <div class="exam-detail-icon danger">
                <i class="bi bi-x-circle-fill"></i>
            </div>
            <div>
                <dt>Wrong</dt>
                <dd>{{ $attempt->wrong_count }}</dd>
            </div>
        </div>
        <div class="exam-detail-item">
            <div class="exam-detail-icon">
                <i class="bi bi-dash-circle-fill"></i>
            </div>
            <div>
                <dt>Unanswered</dt>
                <dd>{{ $attempt->unanswered_count }}</dd>
            </div>
        </div>
        <div class="exam-detail-item">
            <div class="exam-detail-icon">
                <i class="bi bi-flag-fill"></i>
            </div>
            <div>
                <dt>Passing Marks</dt>
                <dd>{{ $attempt->exam?->passing_marks ?? 'Not set' }}</dd>
            </div>
        </div>
        <div class="exam-detail-item">
            <div class="exam-detail-icon">
                <i class="bi bi-calendar-check"></i>
            </div>
            <div>
                <dt>Submitted</dt>
                <dd>{{ $attempt->submitted_at?->format('d M Y, h:i A') }}</dd>
            </div>
        </div>
    </div>
</section>

<section class="content-panel questions-panel">
    <div class="panel-header">
        <div>
            <h2><i class="bi bi-list-check me-2 text-primary"></i>Answer Review</h2>
            <p>Correct answers are shown for management review.</p>
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
                                {{ $answer->is_correct ? 'Correct' : ($answer->question_option_id ? 'Wrong' : 'Unanswered') }} · {{ $answer->marks_awarded }} marks
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
                        <span class="math-content option-text"><strong>Selected:</strong> {{ $answer->selectedOption?->option_text ?? 'Not answered' }}</span>
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