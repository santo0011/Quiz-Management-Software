<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class Teacher extends Authenticatable
{
    protected $fillable = [
        'branch_id',
        'name',
        'email',
        'phone_number',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'current_session_id',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function remarksGiven()
    {
        return $this->hasMany(ExamAttempt::class, 'teacher_remark_by');
    }
}
