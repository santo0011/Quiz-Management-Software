@php($prefix = $prefix ?? 'admin')
@php($contextBranch = $selectedBranch ?? $branch)

<div class="student-profile-top-actions">
    <a href="{{ route($prefix.'.exams.index') }}" class="btn btn-outline-secondary btn-student-back">
        <i class="bi bi-arrow-left"></i>
        Back
    </a>
</div>

<section class="exam-hero">
    <div class="exam-hero-overlay"></div>
    <div class="exam-hero-content">
        <div class="exam-hero-top">
            <div class="exam-hero-badges">
                <span class="exam-status-badge status-{{ $exam->status }}">
                    <i class="bi {{ $exam->status === 'published' ? 'bi-check-circle-fill' : ($exam->status === 'closed' ? 'bi-x-circle-fill' : 'bi-clock-fill') }}"></i>
                    {{ ucfirst($exam->status) }}
                </span>
                <span class="exam-meta-chip">
                    <i class="bi bi-building"></i>
                    {{ $contextBranch->name }}
                </span>
                @if ($exam->schoolClass)
                    <span class="exam-meta-chip">
                        <i class="bi bi-people-fill"></i>
                        {{ $exam->schoolClass->name }}
                    </span>
                @endif
            </div>
            <div class="exam-hero-actions">
                @if (!$exam->isPublished())
                    <a href="{{ route($prefix.'.questions.create', $exam) }}" class="btn btn-light btn-exam-action">
                        <i class="bi bi-patch-plus-fill"></i>
                        Add Question
                    </a>
                    <a href="{{ route($prefix.'.exams.edit', $exam) }}" class="btn btn-outline-light btn-exam-action">
                        <i class="bi bi-pencil-fill"></i>
                        Edit Exam
                    </a>
                    <form method="POST" action="{{ route($prefix.'.exams.publish', $exam) }}" data-publish-exam>
                        @csrf
                        <button type="submit" class="btn btn-success btn-exam-action">
                            <i class="bi bi-rocket-takeoff-fill"></i>
                            Publish Exam
                        </button>
                    </form>
                @else
                    <span class="exam-published-lock">
                        <i class="bi bi-lock-fill"></i>
                        Published - Locked
                    </span>
                @endif
            </div>
        </div>
        <h1 class="exam-hero-title">{{ $exam->title }}</h1>
        @if ($exam->description)
            <p class="exam-hero-desc">{{ $exam->description }}</p>
        @endif
    </div>
</section>

<section class="exam-stats-grid">
    <div class="exam-stat-card">
        <div class="exam-stat-icon primary">
            <i class="bi bi-trophy-fill"></i>
        </div>
        <div class="exam-stat-body">
            <span>Total Marks</span>
            <strong>{{ $exam->total_marks }}</strong>
        </div>
    </div>
    <div class="exam-stat-card">
        <div class="exam-stat-icon accent">
            <i class="bi bi-patch-check-fill"></i>
        </div>
        <div class="exam-stat-body">
            <span>Marks / Question</span>
            <strong>{{ $exam->marks_per_question }}</strong>
        </div>
    </div>
    <div class="exam-stat-card">
        <div class="exam-stat-icon success">
            <i class="bi bi-flag-fill"></i>
        </div>
        <div class="exam-stat-body">
            <span>Passing Marks</span>
            <strong>{{ $exam->passing_marks ?? '—' }}</strong>
        </div>
    </div>
    <div class="exam-stat-card">
        <div class="exam-stat-icon warning">
            <i class="bi bi-stopwatch-fill"></i>
        </div>
        <div class="exam-stat-body">
            <span>Duration</span>
            <strong>{{ $exam->duration_minutes }} <small>min</small></strong>
        </div>
    </div>
    <div class="exam-stat-card">
        <div class="exam-stat-icon info">
            <i class="bi bi-question-circle-fill"></i>
        </div>
        <div class="exam-stat-body">
            <span>Questions</span>
            <strong>{{ $exam->questions->count() }}</strong>
        </div>
    </div>
</section>

<section class="content-panel exam-details-panel">
    <div class="panel-header">
        <div>
            <h2><i class="bi bi-sliders2 me-2 text-primary"></i>Exam Settings</h2>
            <p>Timing, attempts, and randomization preferences.</p>
        </div>
    </div>

    <div class="exam-details-grid">
        <div class="exam-detail-item">
            <div class="exam-detail-icon">
                <i class="bi bi-calendar-event"></i>
            </div>
            <div>
                <dt>Start</dt>
                <dd>{{ $exam->starts_at?->format('d M Y, h:i A') ?? 'Any time' }}</dd>
            </div>
        </div>
        <div class="exam-detail-item">
            <div class="exam-detail-icon">
                <i class="bi bi-calendar-x"></i>
            </div>
            <div>
                <dt>End</dt>
                <dd>{{ $exam->ends_at?->format('d M Y, h:i A') ?? 'No end time' }}</dd>
            </div>
        </div>
        <div class="exam-detail-item">
            <div class="exam-detail-icon">
                <i class="bi bi-arrow-repeat"></i>
            </div>
            <div>
                <dt>Maximum Attempts</dt>
                <dd>{{ $exam->maximum_attempts }}</dd>
            </div>
        </div>
        <div class="exam-detail-item">
            <div class="exam-detail-icon">
                <i class="bi bi-shuffle"></i>
            </div>
            <div>
                <dt>Question Order</dt>
                <dd>{{ $exam->randomize_questions ? 'Randomized' : 'Fixed order' }}</dd>
            </div>
        </div>
        <div class="exam-detail-item">
            <div class="exam-detail-icon">
                <i class="bi bi-arrow-left-right"></i>
            </div>
            <div>
                <dt>Answer Order</dt>
                <dd>{{ $exam->randomize_answers ? 'Randomized' : 'Fixed order' }}</dd>
            </div>
        </div>
        <div class="exam-detail-item">
            <div class="exam-detail-icon {{ $exam->negative_marking_enabled ? 'danger' : 'success' }}">
                <i class="bi {{ $exam->negative_marking_enabled ? 'bi-exclamation-triangle-fill' : 'bi-shield-check' }}"></i>
            </div>
            <div>
                <dt>Negative Marking</dt>
                <dd>{{ $exam->negative_marking_enabled ? $exam->negative_marks.' per wrong answer' : 'Disabled' }}</dd>
            </div>
        </div>
    </div>
</section>

<section class="content-panel questions-panel">
    <div class="panel-header">
        <div>
            <h2><i class="bi bi-list-check me-2 text-primary"></i>Questions</h2>
            <p>Math expressions render with MathJax in management and student screens.</p>
        </div>
        <span class="question-count-badge">
            <i class="bi bi-file-earmark-text"></i>
            {{ $exam->questions->count() }} {{ Str::plural('Question', $exam->questions->count()) }}
        </span>
    </div>

    @if ($exam->questions->isEmpty())
        <div class="empty-state">
            <div class="empty-state-icon">
                <i class="bi bi-patch-question"></i>
            </div>
            <h3>No questions added</h3>
            <!-- <p>Add at least one MCQ before publishing this exam to students.</p> -->
            <!-- <a href="{{ route($prefix.'.questions.create', $exam) }}" class="btn btn-primary mt-3">
                <i class="bi bi-patch-plus-fill"></i>
                Add First Question
            </a> -->
        </div>
    @else
        <div class="question-list">
            @foreach ($exam->questions as $question)
                <article class="question-admin-item">
                    <div class="question-item-header">
                        <div class="question-number-badge">
                            {{ $loop->iteration }}
                        </div>
                        <div class="question-item-content">
                            <div class="question-item-meta">
                                <span class="question-marks-badge">
                                    <i class="bi bi-trophy"></i>
                                    {{ $question->marks }} marks
                                </span>
                            </div>
                            <h3 class="math-content question-item-text">{{ $question->question_text }}</h3>
                        </div>
                        @if (!$exam->isPublished())
                            <div class="action-group question-item-actions">
                                <a href="{{ route($prefix.'.questions.edit', $question) }}" class="btn btn-sm btn-soft" data-bs-toggle="tooltip" data-bs-title="Edit question">
                                    <i class="bi bi-pencil-fill"></i>
                                </a>
                            </div>
                        @endif
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
