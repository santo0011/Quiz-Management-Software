@php($prefix = $prefix ?? 'admin')
@php($orderedItems = $exam->orderedItems())

<section class="content-panel questions-panel question-mgmt-panel">
    @if ($orderedItems->isEmpty())
        <div class="empty-state">
            <div class="empty-state-icon">
                <i class="bi bi-patch-question"></i>
            </div>
            <h3>No questions added</h3>
        </div>
    @else
        <div class="question-list">
            @foreach ($orderedItems as $item)
                @if ($item['type'] === 'question')
                    @include('exams.partials.question-item', ['question' => $item['question'], 'prefix' => $prefix, 'exam' => $exam, 'itemIndex' => $loop->index, 'itemCount' => $orderedItems->count()])
                @else
                    @php($group = $item['group'])
                    @php($groupFormKey = 'summary-'.$group->id)
                    @php($openGroupForm = session('open_summary_id') == $group->id || old('_form_key') === $groupFormKey)
                    <article class="question-admin-item passage-group-item" id="summary-{{ $group->id }}">
                        <div class="question-item-header">
                            <div class="question-number-badge">
                                <i class="bi bi-file-earmark-text-fill"></i>
                            </div>
                            <div class="question-item-content">
                                <div class="question-item-meta">
                                    <span class="question-marks-badge">
                                        <i class="bi bi-collection"></i>
                                        Passage/Summary · {{ $group->questions->count() }} {{ Str::plural('question', $group->questions->count()) }}
                                    </span>
                                </div>
                                <h3 class="question-item-text">{{ $group->title }}</h3>
                            </div>
                            <div class="action-group question-item-actions">
                                @if ($loop->index > 0)
                                    <form method="POST" action="{{ route($prefix.'.exams.reorder', $exam) }}">
                                        @csrf
                                        <input type="hidden" name="type" value="passage_group">
                                        <input type="hidden" name="id" value="{{ $group->id }}">
                                        <input type="hidden" name="direction" value="up">
                                        <button type="submit" class="btn btn-sm btn-soft" title="Move up"><i class="bi bi-arrow-up"></i></button>
                                    </form>
                                @endif
                                @if ($loop->index < $orderedItems->count() - 1)
                                    <form method="POST" action="{{ route($prefix.'.exams.reorder', $exam) }}">
                                        @csrf
                                        <input type="hidden" name="type" value="passage_group">
                                        <input type="hidden" name="id" value="{{ $group->id }}">
                                        <input type="hidden" name="direction" value="down">
                                        <button type="submit" class="btn btn-sm btn-soft" title="Move down"><i class="bi bi-arrow-down"></i></button>
                                    </form>
                                @endif
                                <button type="button" class="btn btn-sm btn-soft" data-bs-toggle="collapse" data-bs-target="#addQuestionToSummary{{ $group->id }}" title="Add question to this summary">
                                    <i class="bi bi-plus-lg"></i>
                                </button>
                                <a href="{{ route($prefix.'.passage-groups.edit', $group) }}" class="btn btn-sm btn-soft" data-bs-toggle="tooltip" data-bs-title="Edit passage">
                                    <i class="bi bi-pencil-fill"></i>
                                </a>
                                <form method="POST" action="{{ route($prefix.'.passage-groups.destroy', $group) }}" data-confirm-delete data-confirm-message="Deleting this passage will also delete all {{ $group->questions->count() }} of its questions. This cannot be undone.">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger-soft" data-bs-toggle="tooltip" data-bs-title="Delete passage and all its questions">
                                        <i class="bi bi-trash-fill"></i>
                                    </button>
                                </form>
                            </div>
                        </div>

                        <div class="passage-preview math-content">{{ Str::limit(strip_tags($group->content), 220) }}</div>

                        <div class="passage-group-questions">
                            @foreach ($group->questions as $question)
                                @include('exams.partials.question-item', ['question' => $question, 'prefix' => $prefix, 'exam' => $exam, 'itemIndex' => $loop->index, 'itemCount' => $group->questions->count()])
                            @endforeach
                        </div>

                        <div class="collapse {{ $openGroupForm ? 'show' : '' }}" id="addQuestionToSummary{{ $group->id }}">
                            <div class="add-item-inline-form passage-add-question-form mt-3">
                            @include('questions.partials.multi-form', [
                                'prefix' => $prefix,
                                'formKey' => $groupFormKey,
                                'action' => route($prefix.'.passage-groups.questions.store', [$exam, $group]),
                                'button' => 'Save Questions',
                                'defaultMarks' => $exam->marks_per_question ?? 1,
                                'existingQuestions' => collect(),
                                'showExistingQuestions' => false,
                                'categories' => $categories ?? collect(),
                            ])
                            </div>
                        </div>
                    </article>
                @endif
            @endforeach
        </div>
    @endif
</section>

@push('scripts')
    <script>
        window.MathJax = { tex: { inlineMath: [['$', '$'], ['\\(', '\\)']] } };
    </script>
    <script async src="https://cdn.jsdelivr.net/npm/mathjax@3/es5/tex-mml-chtml.js"></script>
@endpush
