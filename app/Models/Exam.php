<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Exam extends Model
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_PUBLISHED = 'published';
    public const STATUS_CLOSED = 'closed';

    protected $fillable = [
        'branch_id',
        'school_class_id',
        'title',
        'description',
        'total_marks',
        'marks_per_question',
        'duration_minutes',
        'starts_at',
        'ends_at',
        'passing_marks',
        'maximum_attempts',
        'randomize_questions',
        'randomize_answers',
        'negative_marking_enabled',
        'negative_marks',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'randomize_questions' => 'boolean',
            'randomize_answers' => 'boolean',
            'negative_marking_enabled' => 'boolean',
            'negative_marks' => 'decimal:2',
            'marks_per_question' => 'decimal:2',
        ];
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function schoolClass()
    {
        return $this->belongsTo(SchoolClass::class);
    }

    public function questions()
    {
        return $this->hasMany(Question::class);
    }

    public function attempts()
    {
        return $this->hasMany(ExamAttempt::class);
    }

    public function scopeForBranch(Builder $query, int $branchId): Builder
    {
        return $query->where('branch_id', $branchId);
    }

    public function scopeAvailableForStudent(Builder $query, Student $student): Builder
    {
        return $query->where('branch_id', $student->branch_id)
            ->where('school_class_id', $student->class_id)
            ->where('status', self::STATUS_PUBLISHED)
            ->where(function (Builder $query): void {
                $query->whereNull('starts_at')->orWhere('starts_at', '<=', now());
            })
            ->where(function (Builder $query): void {
                $query->whereNull('ends_at')->orWhere('ends_at', '>=', now());
            });
    }

    public function isPublished(): bool
    {
        return $this->status === self::STATUS_PUBLISHED;
    }

    public function isOpen(): bool
    {
        if (! $this->isPublished()) {
            return false;
        }

        $now = Carbon::now();

        return (! $this->starts_at || $this->starts_at->lte($now))
            && (! $this->ends_at || $this->ends_at->gte($now));
    }

    /**
     * Determine the dynamic exam status based on the actual scheduled date/time.
     *
     * - 'upcoming'   → scheduled start time has not arrived yet
     * - 'available'  → exam is currently within its allowed time window
     * - 'expired'    → exam end time has passed
     * - 'completed'  → student has already submitted the exam (requires student)
     */
    public function dynamicStatus(?Student $student = null): string
    {
        if (! $this->isPublished()) {
            return 'closed';
        }

        $now = Carbon::now();

        // If the student has already submitted this exam → completed
        if ($student && $this->attempts()
            ->where('student_id', $student->id)
            ->where('status', 'submitted')
            ->exists()) {
            return 'completed';
        }

        // If the exam has a start time and it hasn't arrived yet → upcoming
        if ($this->starts_at && $this->starts_at->gt($now)) {
            return 'upcoming';
        }

        // If the exam has an end time and it has passed → expired
        if ($this->ends_at && $this->ends_at->lt($now)) {
            return 'expired';
        }

        // Otherwise the exam is currently available
        return 'available';
    }

    public function remainingAttemptsFor(Student $student): int
    {
        $usedAttempts = $this->attempts()
            ->where('student_id', $student->id)
            ->whereIn('status', ['in_progress', 'submitted'])
            ->count();

        return max(0, $this->maximum_attempts - $usedAttempts);
    }

    public function recalculateTotalMarks(): void
    {
        $count = $this->questions()->count();
        $this->update([
            'total_marks' => (int) round($count * (float) $this->marks_per_question),
        ]);
    }
}
