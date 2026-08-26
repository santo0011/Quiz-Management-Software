<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AcademicSession extends Model
{
    protected $fillable = [
        'name',
        'start_date',
        'end_date',
        'is_active',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Message shown (and enforced server-side) when deletion is blocked
     * because the session still has related data.
     */
    public const DELETE_LOCK_MESSAGE = 'This Session cannot be deleted because it contains existing data. Remove or migrate the related data before deleting this Session.';

    public function isActive(): bool
    {
        return $this->is_active;
    }

    public function students()
    {
        return $this->hasMany(Student::class, 'session_id');
    }

    public function exams()
    {
        return $this->hasMany(Exam::class, 'session_id');
    }

    public function examAttempts()
    {
        return $this->hasMany(ExamAttempt::class, 'session_id');
    }

    /**
     * Whether any Student, Exam, or Exam Attempt (result) is still tied to
     * this session — the single source of truth for whether it may be
     * deleted, checked both by the delete guard and by the UI so the
     * Delete action is disabled up front instead of failing after a click.
     */
    public function hasRelatedData(): bool
    {
        return $this->students()->exists()
            || $this->exams()->exists()
            || $this->examAttempts()->exists();
    }
}
