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
