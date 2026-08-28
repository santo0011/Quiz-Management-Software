<?php

namespace App\Http\Requests;

use App\Models\Exam;
use App\Models\Question;
use App\Support\HtmlSanitizer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class QuestionRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        // Only questions that belong to a Summary/Passage use the rich-text
        // (CKEditor) editor and need their HTML sanitized before storage —
        // standalone questions use the plain math-editor and are rendered
        // escaped as plain text, so their text is left untouched here.
        $isSummaryQuestion = $this->route('question')?->passage_group_id !== null;

        if ($isSummaryQuestion && $this->has('question_text')) {
            $this->merge(['question_text' => HtmlSanitizer::sanitize($this->input('question_text'))]);
        }
    }

    public function authorize(): bool
    {
        $exam = $this->route('exam') ?: $this->route('question')?->exam;

        if (! $exam instanceof Exam) {
            return false;
        }

        if ($this->user()?->role === 'Branch') {
            return $exam->branch_id === $this->user()->branch_id;
        }

        if ($this->user()?->role === 'Super Admin') {
            return true;
        }

        return false;
    }

    public function rules(): array
    {
        $exam = $this->route('exam') ?: $this->route('question')?->exam;

        return [
            'question_text' => ['required', 'string'],
            'marks' => ['required', 'integer', 'min:1'],
            'question_category_id' => [
                'required',
                Rule::exists('question_categories', 'id')->where(
                    fn ($query) => $query->where(fn ($q) => $q->where('branch_id', $exam?->branch_id)->orWhereNull('branch_id'))
                ),
            ],
            'explanation' => ['nullable', 'string'],
            'options' => ['required', 'array', 'min:2'],
            'options.*' => ['required', 'string'],
            'correct_option' => ['required', 'integer', 'min:0'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $options = collect($this->input('options', []))
                ->map(fn ($option) => trim((string) $option))
                ->filter()
                ->values();

            if ($options->count() < 2) {
                $validator->errors()->add('options', 'Please add at least 2 answer options.');
            }

            if (! $options->has((int) $this->input('correct_option'))) {
                $validator->errors()->add('correct_option', 'Please select the correct answer.');
            }

            $exam = $this->route('exam') ?: $this->route('question')?->exam;
            $question = $this->route('question');

            if ($exam instanceof Exam && $this->filled('question_text')) {
                $duplicate = Question::where('exam_id', $exam->id)
                    ->where('question_text', trim($this->input('question_text')))
                    ->when($question, fn ($query) => $query->whereKeyNot($question->id))
                    ->exists();

                if ($duplicate) {
                    $validator->errors()->add('question_text', 'This question already exists in this exam.');
                }
            }
        });
    }
}
