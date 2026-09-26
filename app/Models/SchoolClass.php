<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class SchoolClass extends Model
{
    protected $fillable = [
        'branch_id',
        'name',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function students()
    {
        return $this->hasMany(Student::class, 'class_id');
    }

    /**
     * Every Exam assigned to this Grade (an Exam may have several Grades).
     */
    public function exams()
    {
        return $this->belongsToMany(Exam::class, 'exam_school_class')->withTimestamps();
    }

    public function isGlobal(): bool
    {
        return $this->branch_id === null;
    }

    /**
     * Classes usable by a given branch: its own classes plus any
     * Super-Admin-created global classes (branch_id is null).
     */
    public function scopeVisibleToBranch(Builder $query, int $branchId): Builder
    {
        return $query->where(fn (Builder $q) => $q->where('branch_id', $branchId)->orWhereNull('branch_id'));
    }
}
