<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PassageGroup extends Model
{
    protected $fillable = [
        'exam_id',
        'title',
        'content',
        'image_path',
        'position',
    ];

    public function exam()
    {
        return $this->belongsTo(Exam::class);
    }

    public function questions()
    {
        return $this->hasMany(Question::class)->orderBy('position');
    }
}
