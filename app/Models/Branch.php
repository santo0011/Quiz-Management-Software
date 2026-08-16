<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
    protected $fillable = [
        'name',
        'email',
    ];

    public function user()
    {
        return $this->hasOne(User::class);
    }

    public function students()
    {
        return $this->hasMany(Student::class);
    }

    public function classes()
    {
        return $this->hasMany(SchoolClass::class);
    }

    public function exams()
    {
        return $this->hasMany(Exam::class);
    }
}
