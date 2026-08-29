<?php

namespace App\Http\Requests;

use App\Models\Exam;
use App\Support\HtmlSanitizer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MultiQuestionRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        // All question text (standalone or under a Summary/Passage) now comes
        // from the CKEditor question-text field, so it always needs sanitizing
        // before storage — it's later rendered as raw HTML.
        $questions = $this->input('questions');

        if (! is_array($questions)) {
            return;
        }

        foreach ($questions as $index => $question) {
            if (is_array($question) && array_key_exists('question_text', $question)) {
                $questions[$index]['question_text'] = HtmlSanitizer::sanitize($question['question_text']);
            }
        }

        $this->merge(['questions' => $questions]);
    }

    public function authorize(): bool
    {
        $exam = $this->route('exam');

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
        $exam = $this->route('exam');

        return [
            'questions' => ['required', 'array', 'min:1', 'max:100'],
            'questions.*.question_text' => ['required', 'string'],
            'questions.*.marks' => ['required', 'integer', 'min:1'],
            'questions.*.question_category_id' => [
                'required',
                Rule::exists('question_categories', 'id')->where(
                    fn ($query) => $query->where(fn ($q) => $q->where('branch_id', $exam?->branch_id)->orWhereNull('branch_id'))
                ),
            ],
            'questions.*.explanation' => ['nullable', 'string'],
            'questions.*.options' => ['required', 'array', 'min:2'],
            'questions.*.options.*' => ['required', 'string'],
            'questions.*.correct_option' => ['required', 'integer', 'min:0'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $questions = $this->input('questions', []);

            foreach ($questions as $index => $question) {
                $options = collect($question['options'] ?? [])
                    ->map(fn ($option) => trim((string) $option))
                    ->filter()
                    ->values();

                if ($options->count() < 2) {
                    $validator->errors()->add("questions.$index.options", 'Please add at least 2 answer options.');
                }

                if (! $options->has((int) ($question['correct_option'] ?? -1))) {
                    $validator->errors()->add("questions.$index.correct_option", 'Please select the correct answer.');
                }

                $exam = $this->route('exam');
                if ($exam instanceof Exam && isset($question['question_text']) && trim((string) $question['question_text']) !== '') {
                    $duplicate = \App\Models\Question::where('exam_id', $exam->id)
                        ->where('question_text', trim((string) $question['question_text']))
                        ->exists();

                    if ($duplicate) {
                        $validator->errors()->add("questions.$index.question_text", 'This question already exists in this exam.');
                    }
                }
            }
        });
    }
}