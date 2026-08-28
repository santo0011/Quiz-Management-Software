@extends('layouts.guardian')

@section('title', $attempt->exam?->title)
@section('page-title', 'Result Details')

@section('content')
    <div class="student-profile-top-actions">
        <a href="{{ route('guardian.students.results.show', [$student, $attempt]) }}" class="btn btn-outline-secondary btn-student-back">
            <i class="bi bi-arrow-left"></i>
            Back to Result Summary
        </a>
    </div>

    @include('partials.guardian-student-switcher')

    <section class="student-section">
        <div class="student-section-header">
            <div>
                <span>{{ $student->student_name }}</span>
                <h2>{{ $attempt->exam?->title }}</h2>
            </div>
            <span class="status-badge {{ $attempt->is_passed ? 'status-published' : 'status-closed' }}">
                <i class="bi {{ $attempt->is_passed ? 'bi-check-circle-fill' : 'bi-x-circle-fill' }}"></i>
                {{ $attempt->is_passed ? 'Passed' : 'Failed' }}
            </span>
        </div>

        @php($answersByQuestion = $attempt->answers->keyBy('question_id'))
        @php($orderedItems = $attempt->exam->orderedItems())

        <div class="student-section-header mt-2">
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
                    @include('results.partials.answer-item', ['question' => $question, 'answer' => $answersByQuestion->get($question->id), 'itemIndex' => $loop->index, 'selectedLabel' => 'Student answer'])
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
                                    <span class="question-marks-badge group-total">
                                        <i class="bi bi-trophy-fill"></i>
                                        {{ rtrim(rtrim(number_format($groupObtained, 2), '0'), '.') }}/{{ rtrim(rtrim(number_format($groupTotal, 2), '0'), '.') }} marks
                                    </span>
                                </div>
                                <h3 class="question-item-text">{{ $group->title }}</h3>
                            </div>
                        </div>
                        <div class="passage-preview math-content">{!! \App\Support\HtmlSanitizer::sanitize($group->content) !!}</div>
                        <div class="passage-group-questions">
                            @foreach ($group->questions as $question)
                                @include('results.partials.answer-item', ['question' => $question, 'answer' => $answersByQuestion->get($question->id), 'itemIndex' => $loop->index, 'selectedLabel' => 'Student answer'])
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
@endsection
