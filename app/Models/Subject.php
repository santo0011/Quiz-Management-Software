<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subject extends Model
{
    protected $fillable = [
        'name',
    ];

    public function students()
    {
        return $this->belongsToMany(Student::class);
    }

    public function exams()
    {
        return $this->hasMany(Exam::class);
    }
}
