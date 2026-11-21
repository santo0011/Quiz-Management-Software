{{-- Read-only question card for the Teacher panel: no reorder, edit or delete. --}}
<article class="question-admin-item {{ $question->passage_group_id ? 'passage-child-item' : '' }}">
    <div class="question-item-header">
        <div class="question-number-badge">{{ $number }}</div>
        <div class="question-item-content">
            <div class="question-item-meta">
                <span class="question-marks-badge">
                    <i class="bi bi-trophy"></i>
                    {{ $question->marks }} {{ Str::plural('mark', (float) $question->marks) }}
                </span>
                @if ($question->category)
                    <span class="question-marks-badge">
                        <i class="bi bi-tag"></i>
                        {{ $question->category->name }}
                    </span>
                @endif
            </div>
            <div class="math-content passage-preview question-item-text">{!! \App\Support\HtmlSanitizer::sanitize($question->question_text) !!}</div>
        </div>
    </div>

    <div class="option-preview">
        @foreach ($question->options as $option)
            <div class="option-preview-item {{ $option->is_correct ? 'correct' : '' }}">
                <span class="option-letter">{{ chr(65 + $loop->index) }}</span>
                <span class="math-content option-text">{{ $option->option_text }}</span>
                @if ($option->is_correct)
                    <span class="correct-badge">
                        <i class="bi bi-check-circle-fill"></i>
                        Correct
                    </span>
                @endif
            </div>
        @endforeach
    </div>

    @if ($question->explanation)
        <div class="question-explanation">
            <div class="explanation-icon"><i class="bi bi-lightbulb-fill"></i></div>
            <div>
                <strong>Explanation</strong>
                <p class="math-content mb-0">{{ $question->explanation }}</p>
            </div>
        </div>
    @endif
</article>
