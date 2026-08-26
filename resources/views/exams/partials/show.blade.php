@php($prefix = $prefix ?? 'admin')
@php($contextBranch = $selectedBranch ?? $branch ?? null)

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
                @if ($exam->subject)
                    <span class="exam-meta-chip">
                        <i class="bi bi-book-fill"></i>
                        {{ $exam->subject->name }}
                    </span>
                @endif
            </div>
            <div class="exam-hero-actions">
                @if (!$exam->hasBeenAttempted())
                    <a href="{{ route($prefix.'.questions.create', $exam) }}" class="btn btn-light btn-exam-action">
                        <i class="bi bi-list-check"></i>
                        Manage Questions
                    </a>
                    <a href="{{ route($prefix.'.exams.edit', $exam) }}" class="btn btn-outline-light btn-exam-action">
                        <i class="bi bi-pencil-fill"></i>
                        Edit Exam
                    </a>
                    @if (!$exam->isPublished())
                        <form method="POST" action="{{ route($prefix.'.exams.publish', $exam) }}" data-publish-exam>
                            @csrf
                            <button type="submit" class="btn btn-success btn-exam-action">
                                <i class="bi bi-rocket-takeoff-fill"></i>
                                Publish Exam
                            </button>
                        </form>
                    @else
                        <form method="POST" action="{{ route($prefix.'.exams.unpublish', $exam) }}" data-unpublish-exam>
                            @csrf
                            <button type="submit" class="btn btn-warning btn-exam-action">
                                <i class="bi bi-arrow-counterclockwise"></i>
                                Unpublish Exam
                            </button>
                        </form>
                    @endif
                @else
                    <span class="exam-published-lock" data-bs-toggle="tooltip" data-bs-title="{{ \App\Models\Exam::LOCK_MESSAGE }}">
                        <i class="bi bi-lock-fill"></i>
                        Locked - Student Attempted
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
            <p>{{ $exam->questions->count() }} {{ Str::plural('question', $exam->questions->count()) }} in this exam.</p>
        </div>
        <a href="{{ route($prefix.'.questions.create', $exam) }}" class="btn btn-primary">
            <i class="bi bi-list-check"></i>
            Manage Questions
        </a>
    </div>
</section>
