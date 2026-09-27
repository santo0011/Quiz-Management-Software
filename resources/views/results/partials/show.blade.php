@php($prefix = $prefix ?? 'admin')
@include('partials.format-time')

{{-- Staff always see every attempt (no waiting for the Attempt Limit).
     Multi-attempt exams show Attempt History (same condition that partial
     renders on): each attempt's summary and Answer Review live inside its
     own panel there; single-attempt exams keep them on this page. --}}
@php($attemptHistory = $attempt->submittedSiblings())
@php($showsAttemptHistory = $attemptHistory->count() > 1 || (int) ($attempt->exam?->maximum_attempts ?? 1) > 1)

@unless ($hideBack ?? false)
    <div class="student-profile-top-actions">
        <a href="{{ route($prefix.'.results.index') }}" class="btn btn-outline-secondary btn-student-back">
            <i class="bi bi-arrow-left"></i>
            Back
        </a>
    </div>
@endunless

<section class="content-panel exam-details-panel">
    <div class="panel-header">
        <div>
            <h2><i class="bi bi-award me-2 text-primary"></i>{{ $attempt->exam?->title }}</h2>
            <p>{{ $attempt->student?->student_name }} · Attempt {{ $attempt->attemptLabel() }}</p>
        </div>
    </div>

    {{-- Multi-attempt results show each attempt's own summary inside its
         Attempt History panel below instead of once up here. --}}
    @unless ($showsAttemptHistory)
        @include('results.partials.attempt-summary', ['attempt' => $attempt])
    @endunless
</section>

@include('results.partials.attempt-history', [
    'history' => $attemptHistory,
    'current' => $attempt,
    'routeFor' => fn ($item) => route($prefix.'.results.show', $item),
    'showSummary' => true,
])

@php($answersByQuestion = $attempt->answers->keyBy('question_id'))
@php($orderedItems = $attempt->exam->orderedItems())
@php($questionNumbers = $attempt->exam->questionNumbers($orderedItems))

@unless ($showsAttemptHistory)
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
                @include('results.partials.answer-item', ['question' => $question, 'questionNumber' => $questionNumbers[$question->id] ?? null, 'answer' => $answersByQuestion->get($question->id), 'itemIndex' => $loop->index])
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
                            @include('results.partials.answer-item', ['question' => $question, 'questionNumber' => $questionNumbers[$question->id] ?? null, 'answer' => $answersByQuestion->get($question->id), 'itemIndex' => $loop->index])
                        @endforeach
                    </div>
                </article>
            @endif
        @endforeach
    </div>
</section>
@endunless

@push('scripts')
    <script>
        window.MathJax = { tex: { inlineMath: [['$', '$'], ['\\(', '\\)']] } };
    </script>
    <script async src="https://cdn.jsdelivr.net/npm/mathjax@3/es5/tex-mml-chtml.js"></script>
@endpush
