<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class QuestionCategory extends Model
{
    protected $fillable = [
        'branch_id',
        'name',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function questions()
    {
        return $this->hasMany(Question::class);
    }

    public function exams()
    {
        return $this->hasMany(Exam::class);
    }

    public function isGlobal(): bool
    {
        return $this->branch_id === null;
    }

    /**
     * Categories usable by a given branch: its own categories plus any
     * Super-Admin-created global categories (branch_id is null). A null
     * $branchId means the exam itself is global (Super Admin, no branch),
     * so only global categories apply.
     */
    public function scopeVisibleToBranch(Builder $query, ?int $branchId): Builder
    {
        if ($branchId === null) {
            return $query->whereNull('branch_id');
        }

        return $query->where(fn (Builder $q) => $q->where('branch_id', $branchId)->orWhereNull('branch_id'));
    }
}
