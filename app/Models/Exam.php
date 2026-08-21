<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class Exam extends Model
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_PUBLISHED = 'published';
    public const STATUS_CLOSED = 'closed';

    public const LOCK_MESSAGE = 'This exam cannot be edited or deleted because a student has already attended this exam.';

    protected $fillable = [
        'branch_id',
        'school_class_id',
        'question_category_id',
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

    public function category()
    {
        return $this->belongsTo(QuestionCategory::class, 'question_category_id');
    }

    public function questions()
    {
        return $this->hasMany(Question::class);
    }

    public function passageGroups()
    {
        return $this->hasMany(PassageGroup::class);
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

    /**
     * Whether any student has started (attempted) this exam. Once true, the
     * exam, its questions, and its settings are locked from further edits
     * or deletion — regardless of publish status.
     */
    public function hasBeenAttempted(): bool
    {
        return $this->attempts()->exists();
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
        $this->update([
            'total_marks' => (int) round((float) $this->questions()->sum('marks')),
        ]);
    }

    /**
     * The exam's top-level items (standalone questions and passage groups)
     * in admin-configured order. Each passage group carries its own
     * questions, already ordered within the group.
     *
     * @return Collection<int, array{type: string, position: int, question?: Question, group?: PassageGroup}>
     */
    public function orderedItems(): Collection
    {
        $standaloneQuestions = $this->questions()
            ->whereNull('passage_group_id')
            ->with('options', 'category')
            ->get()
            ->map(fn (Question $question) => [
                'type' => 'question',
                'position' => $question->position,
                'question' => $question,
            ]);

        $groups = $this->passageGroups()
            ->with(['questions' => fn ($query) => $query->with('options', 'category')])
            ->get()
            ->map(fn (PassageGroup $group) => [
                'type' => 'passage_group',
                'position' => $group->position,
                'group' => $group,
            ]);

        return $standaloneQuestions->concat($groups)
            ->sortBy('position')
            ->values();
    }

    /**
     * Next top-level position for a new standalone question or passage
     * group, so both share one ordering sequence.
     */
    public function nextTopLevelPosition(): int
    {
        $maxQuestionPosition = (int) $this->questions()->whereNull('passage_group_id')->max('position');
        $maxGroupPosition = (int) $this->passageGroups()->max('position');

        return max($maxQuestionPosition, $maxGroupPosition) + 1;
    }
}
