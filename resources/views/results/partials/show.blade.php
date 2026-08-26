@php($prefix = $prefix ?? 'admin')
@include('partials.format-time')

<div class="student-profile-top-actions">
    <a href="{{ route($prefix.'.results.index') }}" class="btn btn-outline-secondary btn-student-back">
        <i class="bi bi-arrow-left"></i>
        Back
    </a>
</div>

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
                <strong>{{ format_time_taken($attempt) }}</strong>
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
                <dt>Grade</dt>
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

@php($answersByQuestion = $attempt->answers->keyBy('question_id'))
@php($orderedItems = $attempt->exam->orderedItems())

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
        @foreach ($orderedItems as $item)
            @if ($item['type'] === 'question')
                @php($question = $item['question'])
                @include('results.partials.answer-item', ['question' => $question, 'answer' => $answersByQuestion->get($question->id), 'itemIndex' => $loop->index])
            @else
                @php($group = $item['group'])
                @php($groupAnswers = $group->questions->map(fn ($q) => $answersByQuestion->get($q->id)))
                @php($groupObtained = $groupAnswers->sum(fn ($a) => (float) ($a?->marks_awarded ?? 0)))
                @php($groupTotal = $group->questions->sum('marks'))
                <article class="question-admin-item passage-group-item">
                    <div class="question-item-header">
                        <div class="question-number-badge"><i class="bi bi-file-earmark-text-fill"></i></div>
                        <div class="question-item-content">
                            <div class="question-item-meta">
                                <span class="status-badge status-published"><i class="bi bi-collection"></i> Passage/Summary Question</span>
                                <span class="question-marks-badge group-total">
                                    <i class="bi bi-trophy-fill"></i>
                                    {{ rtrim(rtrim(number_format($groupObtained, 2), '0'), '.') }}/{{ rtrim(rtrim(number_format($groupTotal, 2), '0'), '.') }} marks
                                </span>
                            </div>
                            <h3 class="question-item-text">{{ $group->title }}</h3>
                        </div>
                    </div>
                    <div class="passage-preview">{!! \App\Support\HtmlSanitizer::sanitize($group->content) !!}</div>
                    <div class="passage-group-questions">
                        @foreach ($group->questions as $question)
                            @include('results.partials.answer-item', ['question' => $question, 'answer' => $answersByQuestion->get($question->id), 'itemIndex' => $loop->index])
                        @endforeach
                    </div>
                </article>
            @endif
        @endforeach
    </div>
</section>

@push('scripts')
    <script>
        window.MathJax = { tex: { inlineMath: [['$', '$'], ['\\(', '\\)']] } };
    </script>
    <script async src="https://cdn.jsdelivr.net/npm/mathjax@3/es5/tex-mml-chtml.js"></script>
@endpush
