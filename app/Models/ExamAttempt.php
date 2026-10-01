<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ExamAttempt extends Model
{
    protected $fillable = [
        'exam_id',
        'student_id',
        'branch_id',
        'school_class_id',
        'attempt_number',
        'started_at',
        'expires_at',
        'submitted_at',
        'obtained_marks',
        'percentage',
        'correct_count',
        'wrong_count',
        'unanswered_count',
        'status',
        'teacher_remark',
        'teacher_remark_by',
        'teacher_remark_at',
        'result_pdf_path',
        'result_pdf_token',
        'zoho_result_synced_at',
        'zoho_class_id',
        'zoho_class_name',
        'zoho_grade',
        'branch_result_pdf_path',
        'result_email_sent_at',
        'result_email_sent_by',
        'branch_review',
        'branch_review_by',
        'branch_review_at',
        'branch_result_sent_at',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'expires_at' => 'datetime',
            'submitted_at' => 'datetime',
            'obtained_marks' => 'decimal:2',
            'percentage' => 'decimal:2',
            'teacher_remark_at' => 'datetime',
            'zoho_result_synced_at' => 'datetime',
            'result_email_sent_at' => 'datetime',
            'branch_review_at' => 'datetime',
            'branch_result_sent_at' => 'datetime',
        ];
    }

    public function exam()
    {
        return $this->belongsTo(Exam::class);
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function schoolClass()
    {
        return $this->belongsTo(SchoolClass::class);
    }

    public function answers()
    {
        return $this->hasMany(ExamAnswer::class);
    }

    /**
     * Submitted results that belong to a Branch — the one rule every Teacher
     * result view (list, detail/remark, dashboard, profile) uses, so a Teacher
     * sees exactly their own Branch's results and never another Branch's.
     *
     * Ownership follows the student's current branch (students.branch_id,
     * kept in sync with Zoho) — the same way the Branch's Students module
     * decides who belongs to it — not the attempt's branch_id, a snapshot
     * from exam start that can be empty or stale.
     */
    public function scopeBelongingToBranch(Builder $query, ?int $branchId): Builder
    {
        return $query
            ->where('status', 'submitted')
            ->whereHas('student', fn (Builder $studentQuery) => $studentQuery->where('branch_id', $branchId ?? 0));
    }

    /**
     * Single-result form of scopeBelongingToBranch().
     */
    public function belongsToBranch(?int $branchId): bool
    {
        return $branchId !== null
            && $this->status === 'submitted'
            && (int) $this->student?->branch_id === $branchId;
    }

    /**
     * Total marks deducted by negative marking, as a positive number — the
     * sum of the negative per-answer marks recorded at submission, so it
     * stays accurate even if the Exam's negative-mark setting changes later.
     */
    public function negativeMarksTotal(): float
    {
        return abs((float) $this->answers->sum(fn ($answer) => min(0, (float) $answer->marks_awarded)));
    }

    /**
     * "-1.50" for display, or "0.00" when nothing was deducted.
     */
    public function negativeMarksLabel(): string
    {
        $total = $this->negativeMarksTotal();

        return ($total > 0 ? '-' : '').number_format($total, 2);
    }

    /**
     * Whether results should show the Negative Marks total: the Exam uses
     * negative marking, or this attempt was scored with deductions.
     */
    public function showsNegativeMarks(): bool
    {
        return (bool) $this->exam?->negative_marking_enabled || $this->negativeMarksTotal() > 0;
    }

    /**
     * "2/3" — this attempt's number out of the Exam's Attempt Limit.
     */
    public function attemptLabel(): string
    {
        $max = max((int) ($this->exam?->maximum_attempts ?? 1), (int) $this->attempt_number);

        return $this->attempt_number.'/'.$max;
    }

    /**
     * Every submitted attempt this Student made at the same Exam (this one
     * included), oldest first — the per-exam attempt history.
     */
    public function submittedSiblings()
    {
        return static::query()
            ->where('exam_id', $this->exam_id)
            ->where('student_id', $this->student_id)
            ->where('status', 'submitted')
            ->orderBy('attempt_number')
            ->get();
    }

    /**
     * The student's most recent submitted attempt at this exam (this one if
     * it is the latest, or the exam allows a single attempt) — the attempt
     * the Branch's "Review & Send Result" always sends.
     */
    public function latestSubmittedAttempt(): self
    {
        return $this->submittedSiblings()->last() ?? $this;
    }

    public function teacherRemarkBy()
    {
        return $this->belongsTo(Teacher::class, 'teacher_remark_by');
    }

    public function resultEmailSentBy()
    {
        return $this->belongsTo(User::class, 'result_email_sent_by');
    }

    public function branchReviewBy()
    {
        return $this->belongsTo(User::class, 'branch_review_by');
    }
}
