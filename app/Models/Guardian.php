<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class Guardian extends Authenticatable
{
    protected $fillable = [
        'name',
        'email',
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

    /**
     * Every Student whose Guardian Email matches this account's email —
     * the only link between a Guardian and its Students, matched by value
     * rather than a foreign key, since the same email can be entered on any
     * number of Student profiles.
     */
    public function students()
    {
        return $this->hasMany(Student::class, 'guardian_email', 'email');
    }

    /**
     * The FK-based counterpart to students() above, added alongside
     * guardian_id so the Admin/Branch "Existing Guardian" picker on the
     * Student Create form can scope its search (e.g. Branch users only see
     * guardians who already have a student in their own branch) without
     * disturbing the by-value relation every Guardian-guard read path
     * still relies on.
     */
    public function linkedStudents()
    {
        return $this->hasMany(Student::class, 'guardian_id');
    }
}
