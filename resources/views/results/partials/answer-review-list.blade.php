{{--
    Question-by-question answer review for one attempt (standalone questions
    and Summary/passage groups, in exam order).

    @param  \App\Models\ExamAttempt  $attempt        answers.selectedOption + answers.question.options loaded
    @param  \Illuminate\Support\Collection|null  $orderedItems  $attempt->exam->orderedItems(), if already built
    @param  string|null  $selectedLabel  e.g. "Your answer"
--}}
@php($answersByQuestion = $attempt->answers->keyBy('question_id'))
@php($orderedItems = $orderedItems ?? $attempt->exam->orderedItems())
@php($questionNumbers = $attempt->exam->questionNumbers($orderedItems))

<div class="question-list">
    @foreach ($orderedItems as $item)
        @if ($item['type'] === 'question')
            @php($question = $item['question'])
            @include('results.partials.answer-item', ['question' => $question, 'questionNumber' => $questionNumbers[$question->id] ?? null, 'answer' => $answersByQuestion->get($question->id), 'itemIndex' => $loop->index, 'selectedLabel' => $selectedLabel ?? null])
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
                <div class="passage-preview math-content">{!! \App\Support\HtmlSanitizer::sanitize($group->content) !!}</div>
                <div class="passage-group-questions">
                    @foreach ($group->questions as $question)
                        @include('results.partials.answer-item', ['question' => $question, 'questionNumber' => $questionNumbers[$question->id] ?? null, 'answer' => $answersByQuestion->get($question->id), 'itemIndex' => $loop->index, 'selectedLabel' => $selectedLabel ?? null])
                    @endforeach
                </div>
            </article>
        @endif
    @endforeach
</div>
