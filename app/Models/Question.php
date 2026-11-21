<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Question extends Model
{
    protected $fillable = [
        'exam_id',
        'passage_group_id',
        'question_category_id',
        'question_text',
        'question_type',
        'marks',
        'explanation',
        'position',
    ];

    protected function casts(): array
    {
        return [
            'marks' => 'integer',
        ];
    }

    public function exam()
    {
        return $this->belongsTo(Exam::class);
    }

    public function passageGroup()
    {
        return $this->belongsTo(PassageGroup::class);
    }

    public function category()
    {
        return $this->belongsTo(QuestionCategory::class, 'question_category_id');
    }

    public function options()
    {
        return $this->hasMany(QuestionOption::class)->orderBy('position');
    }

    public function correctOption()
    {
        return $this->hasOne(QuestionOption::class)->where('is_correct', true);
    }

    /**
     * Saves the submitted options in order. An option posted with its
     * existing id is updated in place, so students' recorded answers
     * (exam_answers.question_option_id, nulled on delete) keep pointing at
     * it; only options removed from the form are deleted.
     *
     * @param  array<int, string>  $optionTexts
     * @param  array<int, int|string|null>  $optionIds
     */
    public function syncOptions(array $optionTexts, int $correctIndex, array $optionIds = []): void
    {
        $existing = $this->options()->get()->keyBy('id');
        $optionIds = array_values($optionIds);
        $keptIds = [];

        foreach (array_values($optionTexts) as $index => $text) {
            $attributes = [
                'option_text' => trim((string) $text),
                'is_correct' => $index === $correctIndex,
                'position' => $index,
            ];

            $option = $existing->get((int) ($optionIds[$index] ?? 0));

            if ($option && ! in_array($option->id, $keptIds, true)) {
                $option->update($attributes);
                $keptIds[] = $option->id;
            } else {
                $keptIds[] = $this->options()->create($attributes)->id;
            }
        }

        QuestionOption::where('question_id', $this->id)->whereNotIn('id', $keptIds)->delete();
    }
}
