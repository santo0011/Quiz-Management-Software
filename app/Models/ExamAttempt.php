<?php

namespace App\Models;

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
        'is_passed',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'expires_at' => 'datetime',
            'submitted_at' => 'datetime',
            'obtained_marks' => 'decimal:2',
            'percentage' => 'decimal:2',
            'is_passed' => 'boolean',
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
}
