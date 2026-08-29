@php($numberLabel = $question->passage_group_id ? chr(65 + $itemIndex) : $itemIndex + 1)

<article class="question-admin-item {{ $question->passage_group_id ? 'passage-child-item' : '' }}">
    <div class="question-item-header">
        <div class="question-number-badge">
            {{ $numberLabel }}
        </div>
        <div class="question-item-content">
            <div class="question-item-meta">
                <span class="question-marks-badge">
                    <i class="bi bi-trophy"></i>
                    {{ $question->marks }} marks
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
        <div class="action-group question-item-actions">
            @if ($itemIndex > 0)
                <form method="POST" action="{{ route($prefix.'.exams.reorder', $exam) }}">
                    @csrf
                    <input type="hidden" name="type" value="question">
                    <input type="hidden" name="id" value="{{ $question->id }}">
                    <input type="hidden" name="direction" value="up">
                    <button type="submit" class="btn btn-sm btn-soft" title="Move up"><i class="bi bi-arrow-up"></i></button>
                </form>
            @endif
            @if ($itemIndex < $itemCount - 1)
                <form method="POST" action="{{ route($prefix.'.exams.reorder', $exam) }}">
                    @csrf
                    <input type="hidden" name="type" value="question">
                    <input type="hidden" name="id" value="{{ $question->id }}">
                    <input type="hidden" name="direction" value="down">
                    <button type="submit" class="btn btn-sm btn-soft" title="Move down"><i class="bi bi-arrow-down"></i></button>
                </form>
            @endif
            <a href="{{ route($prefix.'.questions.edit', $question) }}" class="btn btn-sm btn-soft" data-bs-toggle="tooltip" data-bs-title="Edit question">
                <i class="bi bi-pencil-fill"></i>
            </a>
            <form method="POST" action="{{ route($prefix.'.questions.destroy', $question) }}" data-confirm-delete>
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-sm btn-danger-soft" data-bs-toggle="tooltip" data-bs-title="Delete question">
                    <i class="bi bi-trash-fill"></i>
                </button>
            </form>
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
            <div class="explanation-icon">
                <i class="bi bi-lightbulb-fill"></i>
            </div>
            <div>
                <strong>Explanation</strong>
                <p class="math-content mb-0">{{ $question->explanation }}</p>
            </div>
        </div>
    @endif
</article>
