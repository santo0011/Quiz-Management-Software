{{--
    One attempt's result summary: score tiles (marks, total, percentage,
    time) plus the detail grid (student, grade, correct, incorrect,
    negative marks, not attempted, submitted). Shown at the top of a
    single-attempt result, and inside each attempt's panel in Attempt
    History so every attempt carries its own figures.

    @param  \App\Models\ExamAttempt  $attempt
--}}
@include('partials.format-time')

<div class="exam-stats-grid">
    <div class="exam-stat-card">
        <div class="exam-stat-icon primary">
            <i class="bi bi-trophy-fill"></i>
        </div>
        <div class="exam-stat-body">
            <span>Marks Obtained</span>
            <strong>{{ $attempt->obtained_marks }}</strong>
        </div>
    </div>
    <div class="exam-stat-card">
        <div class="exam-stat-icon accent">
            <i class="bi bi-patch-check-fill"></i>
        </div>
        <div class="exam-stat-body">
            <span>Total Marks</span>
            <strong>{{ $attempt->exam?->total_marks }}</strong>
        </div>
    </div>
    <div class="exam-stat-card">
        <div class="exam-stat-icon success">
            <i class="bi bi-percent"></i>
        </div>
        <div class="exam-stat-body">
            <span>Percentage</span>
            <strong>{{ $attempt->percentage }}<small>%</small></strong>
        </div>
    </div>
    <div class="exam-stat-card">
        <div class="exam-stat-icon warning">
            <i class="bi bi-stopwatch-fill"></i>
        </div>
        <div class="exam-stat-body">
            <span>Time Taken</span>
            <strong>{{ format_time_taken($attempt) }}</strong>
        </div>
    </div>
</div>

<div class="exam-details-grid mt-3">
    <div class="exam-detail-item">
        <div class="exam-detail-icon">
            <i class="bi bi-person-fill"></i>
        </div>
        <div>
            <dt>Student</dt>
            <dd>{{ $attempt->student?->student_name }}</dd>
        </div>
    </div>
    <div class="exam-detail-item">
        <div class="exam-detail-icon">
            <i class="bi bi-people-fill"></i>
        </div>
        <div>
            <dt>Grade</dt>
            <dd>{{ $attempt->schoolClass?->name }}</dd>
        </div>
    </div>
    <div class="exam-detail-item">
        <div class="exam-detail-icon success">
            <i class="bi bi-check-circle-fill"></i>
        </div>
        <div>
            <dt>Correct</dt>
            <dd>{{ $attempt->correct_count }}</dd>
        </div>
    </div>
    <div class="exam-detail-item">
        <div class="exam-detail-icon danger">
            <i class="bi bi-x-circle-fill"></i>
        </div>
        <div>
            <dt>Incorrect</dt>
            <dd>{{ $attempt->wrong_count }}</dd>
        </div>
    </div>
    @if ($attempt->showsNegativeMarks())
        <div class="exam-detail-item">
            <div class="exam-detail-icon danger">
                <i class="bi bi-dash-square-fill"></i>
            </div>
            <div>
                <dt>Negative Marks</dt>
                <dd class="text-danger">{{ $attempt->negativeMarksLabel() }}</dd>
            </div>
        </div>
    @endif
    <div class="exam-detail-item">
        <div class="exam-detail-icon">
            <i class="bi bi-dash-circle-fill"></i>
        </div>
        <div>
            <dt>Not Attempted</dt>
            <dd>{{ $attempt->unanswered_count }}</dd>
        </div>
    </div>
    <div class="exam-detail-item">
        <div class="exam-detail-icon">
            <i class="bi bi-calendar-check"></i>
        </div>
        <div>
            <dt>Submitted</dt>
            <dd>{{ $attempt->submitted_at?->format('d-m-Y H:i:s') }}</dd>
        </div>
    </div>
</div>
