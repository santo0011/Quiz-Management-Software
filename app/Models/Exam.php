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

    public const DELETE_LOCK_MESSAGE = 'This exam cannot be deleted because a student has already attended this exam.';

    public const UNPUBLISH_LOCK_MESSAGE = 'This exam cannot be unpublished because a student has already attended this exam.';

    protected $fillable = [
        'branch_id',
        'school_class_id',
        'subject_id',
        'question_category_id',
        'title',
        'description',
        'total_marks',
        'marks_per_question',
        'duration_minutes',
        'starts_at',
        'ends_at',
        'maximum_attempts',
        'randomize_questions',
        'randomize_answers',
        'negative_marking_enabled',
        'negative_marks',
        'status',
    ];

    protected static function booted(): void
    {
        // Keep the Grades pivot in step with the primary Grade column, so an
        // Exam created with only a school_class_id (older code paths, seeders,
        // tests) is still assigned to that Grade for eligibility purposes.
        static::created(function (Exam $exam): void {
            if ($exam->school_class_id) {
                $exam->grades()->syncWithoutDetaching([$exam->school_class_id]);
            }
        });
    }

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

    /**
     * Every Grade this Exam is assigned to. A Student is eligible when their
     * own Grade is any one of these. school_class_id / schoolClass() is the
     * primary (first selected) Grade and is always part of this set.
     */
    public function grades()
    {
        return $this->belongsToMany(SchoolClass::class, 'exam_school_class')->withTimestamps();
    }

    /**
     * Assign the Exam to exactly the given Grades (one Exam row, never a
     * copy per Grade) and make the first one its primary Grade.
     *
     * @param  array<int, int|string>  $gradeIds
     */
    public function syncGrades(array $gradeIds): void
    {
        $gradeIds = array_values(array_unique(array_map('intval', $gradeIds)));

        if ($gradeIds === []) {
            return;
        }

        if ((int) $this->school_class_id !== $gradeIds[0]) {
            $this->update(['school_class_id' => $gradeIds[0]]);
        }

        $this->grades()->sync($gradeIds);
        $this->unsetRelation('grades');
    }

    public function isAssignedToGrade(?int $gradeId): bool
    {
        return $gradeId !== null && $this->grades()->whereKey($gradeId)->exists();
    }

    /**
     * Grade names for display, e.g. "Grade 5, Grade 6".
     */
    public function gradeNames(): string
    {
        $names = $this->grades->pluck('name')->sort(SORT_NATURAL | SORT_FLAG_CASE)->values();

        if ($names->isEmpty() && $this->schoolClass) {
            $names = collect([$this->schoolClass->name]);
        }

        return $names->join(', ');
    }

    public function createdByLabel(): string
    {
        return $this->isGlobal() ? 'Super Admin' : ($this->branch?->name ?? 'Branch');
    }

    public function visibilityLabel(): string
    {
        return $this->isGlobal() ? 'All Branches' : 'Branch Only';
    }

    public function category()
    {
        return $this->belongsTo(QuestionCategory::class, 'question_category_id');
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
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

    /**
     * Exams usable by a given branch: its own exams plus any
     * Super-Admin-created global exams (branch_id is null). Another
     * branch's exams are never included.
     */
    public function scopeVisibleToBranch(Builder $query, int $branchId): Builder
    {
        return $query->where(fn (Builder $q) => $q->where('branch_id', $branchId)->orWhereNull('branch_id'));
    }

    public function isGlobal(): bool
    {
        return $this->branch_id === null;
    }

    /**
     * The exam-eligibility rule: an Exam is only usable by a Student when
     * the Student's own Grade is one of the Exam's Grades (an Exam may be
     * assigned to several) and its Subject matches one of the Student's
     * assigned Subjects (a Student may have several Subjects — matching any
     * one of them is enough). Branch scoping (own branch or a Super-Admin-created
     * global exam) is included since it's the same "does this Student
     * belong to this Exam" question.
     *
     * This is the single source of truth for that rule — every place that
     * lists or authorizes Exams for a Student (dashboard, available,
     * upcoming, the single-exam page) builds on this scope so the matching
     * logic can never drift out of sync between call sites.
     */
    public function scopeEligibleForStudent(Builder $query, Student $student): Builder
    {
        return $query->where(fn (Builder $q) => $q->where('branch_id', $student->branch_id)->orWhereNull('branch_id'))
            ->whereHas('grades', fn (Builder $q) => $q->whereKey($student->class_id))
            ->where(fn (Builder $q) => $q->whereNull('subject_id')->orWhereIn('subject_id', $student->subjects->pluck('id')));
    }

    /**
     * Eligible Exams that are also currently publishable/open for
     * attempting: published, and within their scheduled time window (if
     * any). Does not exclude Exams the Student already submitted — callers
     * that need that add their own `whereDoesntHave('attempts', ...)`.
     */
    public function scopeAvailableForStudent(Builder $query, Student $student): Builder
    {
        return $query->eligibleForStudent($student)
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
     * exam can no longer be deleted or unpublished — regardless of publish
     * status. Its settings and questions remain editable.
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
     * - 'completed'  → student has used every allowed attempt (requires student)
     */
    public function dynamicStatus(?Student $student = null): string
    {
        if (! $this->isPublished()) {
            return 'closed';
        }

        $now = Carbon::now();

        // Completed only once every allowed attempt is used up — with an
        // Attempt Limit above 1 the student can still take it again after
        // their first submission. (Limit 1 behaves exactly as before.)
        if ($student
            && $this->remainingAttemptsFor($student) <= 0
            && $this->attempts()->where('student_id', $student->id)->where('status', 'submitted')->exists()) {
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

    /**
     * An attempt only counts against the limit once it's actually finished:
     * submitted, or in-progress but past its own expiry (the student never
     * returned to finish it, so it's effectively forfeited). A *currently*
     * active in-progress attempt must NOT count here — otherwise, with the
     * common maximum_attempts = 1, the student's own still-running attempt
     * would zero out their remaining count and lock them out of resuming it
     * (hidden from "Available Exams", "Begin Exam" disabled).
     */
    public function remainingAttemptsFor(Student $student): int
    {
        return max(0, $this->maximum_attempts - $this->usedAttemptsFor($student));
    }

    public function usedAttemptsFor(Student $student): int
    {
        return $this->attempts()
            ->where('student_id', $student->id)
            ->where(fn (Builder $query) => self::countedAttemptConstraint($query))
            ->count();
    }

    /**
     * Attempts that count against the Attempt Limit (see remainingAttemptsFor).
     */
    private static function countedAttemptConstraint(Builder $query): void
    {
        $query->where('status', 'submitted')
            ->orWhere(function (Builder $query): void {
                $query->where('status', 'in_progress')->where('expires_at', '<=', now());
            });
    }

    /**
     * A Student only sees their results for an Exam once they have finished
     * every allowed attempt — or once no further attempt is possible anyway
     * (the exam window has ended or it was closed), so results are never
     * withheld forever. With an Attempt Limit of 1 this is true as soon as
     * the single attempt is submitted, exactly as before.
     */
    public function resultsReleasedFor(Student $student): bool
    {
        return self::attemptSummaries($student, collect([$this]))[$this->id]['released'];
    }

    /**
     * Attempt usage + result visibility for several Exams in one query.
     *
     * @param  Collection<int, Exam>  $exams
     * @return array<int, array{used: int, max: int, remaining: int, released: bool}>
     */
    public static function attemptSummaries(Student $student, Collection $exams): array
    {
        $exams = $exams->filter()->unique('id');

        $usedByExam = ExamAttempt::query()
            ->where('student_id', $student->id)
            ->whereIn('exam_id', $exams->pluck('id'))
            ->where(fn (Builder $query) => self::countedAttemptConstraint($query))
            ->selectRaw('exam_id, count(*) as used')
            ->groupBy('exam_id')
            ->pluck('used', 'exam_id');

        return $exams->mapWithKeys(function (Exam $exam) use ($usedByExam): array {
            $used = (int) ($usedByExam[$exam->id] ?? 0);
            $max = max(1, (int) $exam->maximum_attempts);
            $remaining = max(0, $max - $used);
            $noFurtherAttemptPossible = $exam->status === self::STATUS_CLOSED
                || ($exam->ends_at !== null && $exam->ends_at->isPast());

            return [$exam->id => [
                'used' => $used,
                'max' => $max,
                'remaining' => $remaining,
                'released' => $remaining === 0 || $noFurtherAttemptPossible,
            ]];
        })->all();
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
     * Continuous display serials for every question, in exam order:
     * questions inside a Summary are numbered 1, 2, 3… like any other
     * question, and the next standalone question continues from there.
     * Purely derived from the current order, so it stays correct after
     * adding, editing, deleting or reordering.
     *
     * @return array<int, int> question id => serial number
     */
    public function questionNumbers(?Collection $orderedItems = null): array
    {
        $numbers = [];
        $serial = 0;

        foreach ($orderedItems ?? $this->orderedItems() as $item) {
            $questions = $item['type'] === 'question' ? [$item['question']] : $item['group']->questions;

            foreach ($questions as $question) {
                $numbers[$question->id] = ++$serial;
            }
        }

        return $numbers;
    }

    /**
     * How many questions come before the given Summary's own questions in
     * exam order — i.e. its first question's serial minus one.
     */
    public function questionCountBefore(PassageGroup $group, ?Collection $orderedItems = null): int
    {
        $count = 0;

        foreach ($orderedItems ?? $this->orderedItems() as $item) {
            if ($item['type'] === 'passage_group' && $item['group']->id === $group->id) {
                break;
            }

            $count += $item['type'] === 'question' ? 1 : $item['group']->questions->count();
        }

        return $count;
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
