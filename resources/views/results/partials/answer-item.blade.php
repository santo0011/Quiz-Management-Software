@php($numberLabel = $question->passage_group_id ? chr(65 + $itemIndex) : $itemIndex + 1)

<article class="question-admin-item {{ $question->passage_group_id ? 'passage-child-item' : '' }}">
    <div class="question-item-header">
        <div class="question-number-badge">
            {{ $numberLabel }}
        </div>
        <div class="question-item-content">
            <div class="question-item-meta">
                <span class="question-marks-badge {{ $answer?->is_correct ? 'correct' : ($answer?->question_option_id ? 'wrong' : 'unanswered') }}">
                    <i class="bi {{ $answer?->is_correct ? 'bi-check-circle-fill' : ($answer?->question_option_id ? 'bi-x-circle-fill' : 'bi-dash-circle-fill') }}"></i>
                    {{ $answer?->is_correct ? 'Correct' : ($answer?->question_option_id ? 'Wrong' : 'Unanswered') }} · {{ $answer?->marks_awarded ?? 0 }} marks
                </span>
            </div>
            @if ($question->passage_group_id)
                <div class="math-content passage-preview question-item-text">{!! \App\Support\HtmlSanitizer::sanitize($question->question_text) !!}</div>
            @else
                <h3 class="math-content question-item-text">{{ $question->question_text }}</h3>
            @endif
        </div>
    </div>
    <div class="option-preview">
        <div class="option-preview-item {{ $answer?->is_correct ? 'correct' : '' }}">
            <span class="option-letter">
                <i class="bi bi-person-check-fill"></i>
            </span>
            <span class="math-content option-text"><strong>{{ $selectedLabel ?? 'Selected' }}:</strong> {{ $answer?->selectedOption?->option_text ?? 'Not answered' }}</span>
        </div>
        @if (! $answer?->is_correct)
            <div class="option-preview-item correct">
                <span class="option-letter">
                    <i class="bi bi-check2-circle"></i>
                </span>
                <span class="math-content option-text"><strong>Correct:</strong> {{ $question->options->firstWhere('is_correct', true)?->option_text }}</span>
            </div>
        @endif
    </div>
</article>
