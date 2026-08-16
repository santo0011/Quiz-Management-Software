<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Student extends Authenticatable
{
    protected $fillable = [
        'branch_id',
        'class_id',
        'student_name',
        'guardian_name',
        'class',
        'phone_number',
        'email',
        'password',
        'login_code_hash',
        'zoho_student_id',
        'zoho_payload',
        'zoho_synced_at',
    ];

    protected $hidden = [
        'password',
        'login_code_hash',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'zoho_payload' => 'array',
            'zoho_synced_at' => 'datetime',
        ];
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function schoolClass()
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function attempts()
    {
        return $this->hasMany(ExamAttempt::class);
    }

    public function scopeForBranch(Builder $query, int $branchId): Builder
    {
        return $query->where('branch_id', $branchId);
    }

    public function scopeSearch(Builder $query, ?string $search): Builder
    {
        return $query->when($search, function (Builder $query, string $search): void {
            $query->where(function (Builder $query) use ($search): void {
                $query->where('student_name', 'like', "%{$search}%")
                    ->orWhere('guardian_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone_number', 'like', "%{$search}%");
            });
        });
    }
}
