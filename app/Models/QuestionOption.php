<?php

namespace App\Models;

use App\Support\OptionContent;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class QuestionOption extends Model
{
    protected $fillable = [
        'question_id',
        'option_text',
        'is_correct',
        'position',
    ];

    protected function casts(): array
    {
        return [
            'is_correct' => 'boolean',
        ];
    }

    public function question()
    {
        return $this->belongsTo(Question::class);
    }

    /**
     * Safe HTML for display, for both legacy plain-text and rich options.
     */
    protected function optionHtml(): Attribute
    {
        return Attribute::get(fn () => OptionContent::toHtml($this->option_text));
    }
}
