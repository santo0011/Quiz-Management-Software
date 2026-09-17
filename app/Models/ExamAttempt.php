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
        'teacher_remark',
        'teacher_remark_by',
        'teacher_remark_at',
        'result_pdf_path',
        'result_pdf_token',
        'zoho_result_synced_at',
        'branch_result_pdf_path',
        'result_email_sent_at',
        'result_email_sent_by',
        'branch_review',
        'branch_review_by',
        'branch_review_at',
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
            'teacher_remark_at' => 'datetime',
            'zoho_result_synced_at' => 'datetime',
            'result_email_sent_at' => 'datetime',
            'branch_review_at' => 'datetime',
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
