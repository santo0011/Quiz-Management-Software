@extends('layouts.student')

@section('title', 'Result Details')
@section('page-title', 'Result Details')

@section('content')
    @include('partials.format-time')
    <div class="student-profile-top-actions">
        <a href="{{ route('student.results.index') }}" class="btn btn-outline-secondary btn-student-back">
            <i class="bi bi-arrow-left"></i>
            Back
        </a>
    </div>

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

        <div class="result-card-grid">
            <div class="result-card color-blue">
                <div class="result-card-left">
                    <div class="result-card-icon"><i class="bi bi-trophy-fill"></i></div>
                    <span>Total Marks</span>
                </div>
                <strong>{{ $attempt->exam?->total_marks }}</strong>
            </div>
            <div class="result-card color-green">
                <div class="result-card-left">
                    <div class="result-card-icon"><i class="bi bi-award-fill"></i></div>
                    <span>Obtained</span>
                </div>
                <strong>{{ $attempt->obtained_marks }}</strong>
            </div>
            <div class="result-card color-orange">
                <div class="result-card-left">
                    <div class="result-card-icon"><i class="bi bi-percent"></i></div>
                    <span>Percentage</span>
                </div>
                <strong>{{ $attempt->percentage }}<small>%</small></strong>
            </div>
            <div class="result-card color-purple">
                <div class="result-card-left">
                    <div class="result-card-icon"><i class="bi bi-check-circle-fill"></i></div>
                    <span>Correct</span>
                </div>
                <strong>{{ $attempt->correct_count }}</strong>
            </div>
            <div class="result-card color-red">
                <div class="result-card-left">
                    <div class="result-card-icon"><i class="bi bi-x-circle-fill"></i></div>
                    <span>Wrong</span>
                </div>
                <strong>{{ $attempt->wrong_count }}</strong>
            </div>
            <div class="result-card color-teal">
                <div class="result-card-left">
                    <div class="result-card-icon"><i class="bi bi-dash-circle-fill"></i></div>
                    <span>Unanswered</span>
                </div>
                <strong>{{ $attempt->unanswered_count }}</strong>
            </div>
            <div class="result-card color-blue">
                <div class="result-card-left">
                    <div class="result-card-icon"><i class="bi bi-flag-fill"></i></div>
                    <span>Passing Marks</span>
                </div>
                <strong>{{ $attempt->exam?->passing_marks ?? 'Not set' }}</strong>
            </div>
            <div class="result-card color-orange">
                <div class="result-card-left">
                    <div class="result-card-icon"><i class="bi bi-stopwatch-fill"></i></div>
                    <span>Time Taken</span>
                </div>
                <strong>{{ format_time_taken($attempt) }}</strong>
            </div>
        </div>
    </section>

    @php($answersByQuestion = $attempt->answers->keyBy('question_id'))
    @php($orderedItems = $attempt->exam->orderedItems())

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
            @foreach ($orderedItems as $item)
                @if ($item['type'] === 'question')
                    @php($question = $item['question'])
                    @include('results.partials.answer-item', ['question' => $question, 'answer' => $answersByQuestion->get($question->id), 'itemIndex' => $loop->index, 'selectedLabel' => 'Your answer'])
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
                                    <span class="question-marks-badge">
                                        <i class="bi bi-collection"></i>
                                        Passage
                                    </span>
                                </div>
                                <h3 class="question-item-text">{{ $group->title }}: Total = {{ rtrim(rtrim(number_format($groupObtained, 2), '0'), '.') }}/{{ rtrim(rtrim(number_format($groupTotal, 2), '0'), '.') }}</h3>
                            </div>
                        </div>
                        <div class="summary-collapsible">
                            <div class="passage-preview math-content summary-collapsible-body">{!! \App\Support\HtmlSanitizer::sanitize($group->content) !!}</div>
                            <button type="button" class="btn btn-sm btn-soft summary-toggle-btn" hidden aria-expanded="false">
                                <i class="bi bi-chevron-down summary-toggle-icon"></i>
                                <span class="summary-toggle-label">Read More</span>
                            </button>
                        </div>
                        <div class="passage-group-questions">
                            @foreach ($group->questions as $question)
                                @include('results.partials.answer-item', ['question' => $question, 'answer' => $answersByQuestion->get($question->id), 'itemIndex' => $loop->index, 'selectedLabel' => 'Your answer'])
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

        <script>
            // Summary "Read More" / "Show Less": collapsed by default only when
            // the content is actually taller than the preview window, so short
            // Summaries never grow a toggle they don't need. Runs on window
            // "load" (not DOMContentLoaded) so image heights are already known
            // before the natural height is measured.
            document.addEventListener('DOMContentLoaded', function () {
                var wraps = document.querySelectorAll('.summary-collapsible');
                if (!wraps.length) return;

                var COLLAPSED_HEIGHT = 320; // must match .summary-collapsible-body.is-collapsed max-height in admin.css

                window.addEventListener('load', function () {
                    wraps.forEach(function (wrap) {
                        var body = wrap.querySelector('.summary-collapsible-body');
                        var btn = wrap.querySelector('.summary-toggle-btn');
                        var label = btn ? btn.querySelector('.summary-toggle-label') : null;
                        var icon = btn ? btn.querySelector('.summary-toggle-icon') : null;
                        if (!body || !btn) return;

                        if (body.scrollHeight <= COLLAPSED_HEIGHT + 4) {
                            return;
                        }

                        body.classList.add('is-collapsed');
                        btn.hidden = false;

                        btn.addEventListener('click', function () {
                            var collapsed = body.classList.toggle('is-collapsed');
                            btn.setAttribute('aria-expanded', String(!collapsed));
                            if (label) label.textContent = collapsed ? 'Read More' : 'Show Less';
                            if (icon) icon.className = collapsed ? 'bi bi-chevron-down summary-toggle-icon' : 'bi bi-chevron-up summary-toggle-icon';
                        });
                    });
                });
            });
        </script>
    @endpush
@endsection
