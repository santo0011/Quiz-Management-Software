@php($prefix = $prefix ?? 'admin')
@php($orderedItems = $exam->orderedItems())
@php($questionNumbers = $exam->questionNumbers($orderedItems))

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
                    @include('exams.partials.question-item', ['question' => $item['question'], 'prefix' => $prefix, 'exam' => $exam, 'itemIndex' => $loop->index, 'itemCount' => $orderedItems->count(), 'questionNumber' => $questionNumbers[$item['question']->id] ?? null])
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
                                        Passage · {{ $group->questions->count() }} {{ Str::plural('question', $group->questions->count()) }}
                                    </span>
                                </div>
                                <h3 class="question-item-text">{{ $group->title }}</h3>
                            </div>
                            <div class="action-group question-item-actions">
                                <button type="button" class="btn btn-sm btn-soft" data-bs-toggle="modal" data-bs-target="#viewPassageModal{{ $group->id }}" title="View passage details" aria-label="View passage details">
                                    <i class="bi bi-eye-fill" aria-hidden="true"></i>
                                </button>
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
                                <button type="button" class="btn btn-sm btn-soft" data-bs-toggle="collapse" data-bs-target="#addQuestionToSummary{{ $group->id }}" title="Add question to this passage">
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

                        <div class="modal fade" id="viewPassageModal{{ $group->id }}" tabindex="-1" aria-labelledby="viewPassageModalLabel{{ $group->id }}" aria-hidden="true">
                            <div class="modal-dialog modal-lg modal-dialog-scrollable">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <div>
                                            <h2 class="modal-title fs-5" id="viewPassageModalLabel{{ $group->id }}">{{ $group->title }}</h2>
                                            <small class="text-muted">Passage · {{ $group->questions->count() }} {{ Str::plural('question', $group->questions->count()) }}</small>
                                        </div>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="passage-preview math-content">{!! \App\Support\HtmlSanitizer::sanitize($group->content) !!}</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="passage-group-questions">
                            @foreach ($group->questions as $question)
                                @include('exams.partials.question-item', ['question' => $question, 'prefix' => $prefix, 'exam' => $exam, 'itemIndex' => $loop->index, 'itemCount' => $group->questions->count(), 'questionNumber' => $questionNumbers[$question->id] ?? null])
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
                                // New questions go at the end of this Summary.
                                'numberStart' => $exam->questionCountBefore($group, $orderedItems) + $group->questions->count(),
                            ])
                            </div>
                        </div>
                    </article>
                @endif
            @endforeach
        </div>
    @endif
</section>

@once('mathjax')
    @push('scripts')
        <script>
            window.MathJax = { tex: { inlineMath: [['$', '$'], ['\\(', '\\)']] } };
        </script>
        <script async src="https://cdn.jsdelivr.net/npm/mathjax@3/es5/tex-mml-chtml.js"></script>
    @endpush
@endonce

@once
    @push('scripts')
        <script>
            // Returning from Edit Question (Update or Cancel) lands back on this
            // list at the same place: the edited question is put back where it
            // was on screen, so the admin can carry on with the next one.
            (function () {
                var KEY = 'quizcore.questionList.returnPosition';
                var MAX_AGE_MS = 30 * 60 * 1000;

                // The admin/branch layouts scroll an inner panel (.admin-main),
                // not the window; use whichever ancestor actually scrolls.
                var scrollerFor = function (element) {
                    for (var node = element && element.parentElement; node && node !== document.body; node = node.parentElement) {
                        var overflow = getComputedStyle(node).overflowY;
                        if ((overflow === 'auto' || overflow === 'scroll') && node.scrollHeight > node.clientHeight) {
                            return node;
                        }
                    }
                    return document.scrollingElement || document.documentElement;
                };

                document.addEventListener('click', function (event) {
                    var link = event.target.closest('[data-keep-scroll-position]');
                    if (! link) return;
                    var item = link.closest('.question-admin-item[id]');
                    try {
                        sessionStorage.setItem(KEY, JSON.stringify({
                            path: location.pathname,
                            id: item ? item.id : null,
                            top: item ? item.getBoundingClientRect().top : 0,
                            y: scrollerFor(item || link).scrollTop,
                            at: Date.now(),
                        }));
                    } catch (e) {}
                });

                var saved = null;
                try {
                    saved = JSON.parse(sessionStorage.getItem(KEY));
                    sessionStorage.removeItem(KEY);
                } catch (e) {}

                if (! saved || saved.path !== location.pathname || Date.now() - saved.at > MAX_AGE_MS) return;

                if ('scrollRestoration' in history) history.scrollRestoration = 'manual';

                var userMoved = false;
                ['wheel', 'touchmove', 'keydown', 'mousedown'].forEach(function (type) {
                    window.addEventListener(type, function () { userMoved = true; }, { once: true, passive: true });
                });

                // Re-applied as images and MathJax equations finish laying out,
                // until the admin scrolls on their own.
                var restore = function () {
                    if (userMoved) return;
                    var item = saved.id ? document.getElementById(saved.id) : null;
                    var scroller = scrollerFor(item || document.querySelector('.question-admin-item'));
                    scroller.scrollTop = item
                        ? scroller.scrollTop + item.getBoundingClientRect().top - saved.top
                        : saved.y;
                };

                restore();
                window.addEventListener('load', function () {
                    restore();
                    setTimeout(restore, 600);
                    setTimeout(restore, 1500);
                });
            })();
        </script>
    @endpush
@endonce
