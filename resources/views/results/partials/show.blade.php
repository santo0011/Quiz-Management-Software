@php($prefix = $prefix ?? 'admin')

<section class="content-panel">
    <div class="panel-header">
        <div>
            <h2>{{ $attempt->exam?->title }}</h2>
            <p>{{ $attempt->student?->student_name }} · Attempt {{ $attempt->attempt_number }}</p>
        </div>
        <span class="status-badge {{ $attempt->is_passed ? 'status-published' : 'status-closed' }}">{{ $attempt->is_passed ? 'Passed' : 'Failed' }}</span>
    </div>

    <div class="stats-grid">
        <div class="stat-card"><span>Obtained Marks</span><strong>{{ $attempt->obtained_marks }}</strong></div>
        <div class="stat-card"><span>Total Marks</span><strong>{{ $attempt->exam?->total_marks }}</strong></div>
        <div class="stat-card"><span>Percentage</span><strong>{{ $attempt->percentage }}%</strong></div>
        <div class="stat-card"><span>Time Taken</span><strong>{{ $attempt->submitted_at?->diffInMinutes($attempt->started_at) ?? 0 }} min</strong></div>
    </div>

    <dl class="detail-list mt-4">
        <div><dt>Student</dt><dd>{{ $attempt->student?->student_name }}</dd></div>
        <div><dt>Class</dt><dd>{{ $attempt->schoolClass?->name }}</dd></div>
        <div><dt>Correct</dt><dd>{{ $attempt->correct_count }}</dd></div>
        <div><dt>Wrong</dt><dd>{{ $attempt->wrong_count }}</dd></div>
        <div><dt>Unanswered</dt><dd>{{ $attempt->unanswered_count }}</dd></div>
        <div><dt>Passing Marks</dt><dd>{{ $attempt->exam?->passing_marks ?? 'Not set' }}</dd></div>
        <div><dt>Submitted</dt><dd>{{ $attempt->submitted_at?->format('d M Y, h:i A') }}</dd></div>
    </dl>
</section>

<section class="content-panel">
    <div class="panel-header">
        <div>
            <h2>Answer Review</h2>
            <p>Correct answers are shown for management review.</p>
        </div>
    </div>

    <div class="question-list">
        @foreach ($attempt->answers as $answer)
            <article class="question-admin-item">
                <span class="page-kicker">{{ $answer->is_correct ? 'Correct' : ($answer->question_option_id ? 'Wrong' : 'Unanswered') }} · {{ $answer->marks_awarded }} marks</span>
                <h3 class="math-content">{{ $answer->question?->question_text }}</h3>
                <p class="math-content mb-1"><strong>Selected:</strong> {{ $answer->selectedOption?->option_text ?? 'Not answered' }}</p>
                <p class="math-content mb-0"><strong>Correct:</strong> {{ $answer->question?->options?->firstWhere('is_correct', true)?->option_text }}</p>
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
