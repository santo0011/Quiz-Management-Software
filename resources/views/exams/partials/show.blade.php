@php($prefix = $prefix ?? 'admin')
@php($contextBranch = $selectedBranch ?? $branch)

<section class="content-panel">
    <div class="panel-header">
        <div>
            <h2>{{ $exam->title }}</h2>
            <p>{{ $contextBranch->name }} · {{ $exam->schoolClass?->name }} · {{ ucfirst($exam->status) }}</p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route($prefix.'.questions.create', $exam) }}" class="btn btn-primary">
                <i class="bi bi-patch-plus-fill"></i>
                Add Question
            </a>
            <a href="{{ route($prefix.'.exams.edit', $exam) }}" class="btn btn-soft">
                <i class="bi bi-pencil-fill"></i>
                Edit Exam
            </a>
        </div>
    </div>

    <div class="stats-grid">
        <div class="stat-card"><span>Total Marks</span><strong>{{ $exam->total_marks }}</strong></div>
        <div class="stat-card"><span>Passing Marks</span><strong>{{ $exam->passing_marks ?? 'Not set' }}</strong></div>
        <div class="stat-card"><span>Duration</span><strong>{{ $exam->duration_minutes }} min</strong></div>
        <div class="stat-card"><span>Questions</span><strong>{{ $exam->questions->count() }}</strong></div>
    </div>

    <dl class="detail-list mt-4">
        <div><dt>Start</dt><dd>{{ $exam->starts_at?->format('d M Y, h:i A') ?? 'Any time' }}</dd></div>
        <div><dt>End</dt><dd>{{ $exam->ends_at?->format('d M Y, h:i A') ?? 'No end time' }}</dd></div>
        <div><dt>Maximum Attempts</dt><dd>{{ $exam->maximum_attempts }}</dd></div>
        <div><dt>Options</dt><dd>{{ $exam->randomize_questions ? 'Random questions' : 'Fixed questions' }} · {{ $exam->randomize_answers ? 'Random answers' : 'Fixed answers' }} · {{ $exam->negative_marking_enabled ? 'Negative marking '.$exam->negative_marks : 'No negative marking' }}</dd></div>
    </dl>
</section>

<section class="content-panel">
    <div class="panel-header">
        <div>
            <h2>Questions</h2>
            <p>Math expressions render with MathJax in management and student screens.</p>
        </div>
    </div>

    @if ($exam->questions->isEmpty())
        <div class="empty-state">
            <i class="bi bi-patch-question"></i>
            <h3>No questions added</h3>
            <p>Add at least one MCQ before publishing this exam to students.</p>
        </div>
    @else
        <div class="question-list">
            @foreach ($exam->questions as $question)
                <article class="question-admin-item">
                    <div class="d-flex justify-content-between gap-3">
                        <div>
                            <span class="page-kicker">Question {{ $loop->iteration }} · {{ $question->marks }} marks</span>
                            <h3 class="math-content">{{ $question->question_text }}</h3>
                        </div>
                        <div class="action-group">
                            <a href="{{ route($prefix.'.questions.edit', $question) }}" class="btn btn-sm btn-soft"><i class="bi bi-pencil-fill"></i></a>
                            <form method="POST" action="{{ route($prefix.'.questions.destroy', $question) }}" data-confirm-delete>
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-danger-soft" type="submit"><i class="bi bi-trash-fill"></i></button>
                            </form>
                        </div>
                    </div>
                    <ol class="option-preview">
                        @foreach ($question->options as $option)
                            <li class="math-content {{ $option->is_correct ? 'correct' : '' }}">{{ $option->option_text }}</li>
                        @endforeach
                    </ol>
                    @if ($question->explanation)
                        <p class="math-content mb-0"><strong>Explanation:</strong> {{ $question->explanation }}</p>
                    @endif
                </article>
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
