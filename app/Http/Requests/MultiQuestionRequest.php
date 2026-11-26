<?php

namespace App\Http\Requests;

use App\Models\Exam;
use App\Models\Teacher;
use App\Support\HtmlSanitizer;
use App\Support\OptionContent;
use App\Support\QuestionDuplicateChecker;
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

            if (is_array($question) && is_array($question['options'] ?? null)) {
                $questions[$index]['options'] = array_map(
                    fn ($option) => is_string($option) ? OptionContent::normalize($option) : $option,
                    $question['options']
                );
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

        if ($this->user()?->role === 'Branch' || $this->user() instanceof Teacher) {
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
                'nullable',
                Rule::exists('question_categories', 'id')->where(
                    fn ($query) => $query->where(fn ($q) => $q->where('branch_id', $exam?->branch_id)->orWhereNull('branch_id'))
                ),
            ],
            'questions.*.explanation' => ['nullable', 'string'],
            'questions.*.options' => ['required', 'array', 'min:2'],
            'questions.*.options.*' => ['required', 'string', 'max:'.OptionContent::MAX_LENGTH],
            'questions.*.correct_option' => ['required', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'questions.*.options.*.required' => 'Every answer option needs text, an image or an equation (or remove the empty option).',
            'questions.*.options.*.max' => 'An answer option is too large. Please use smaller images.',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $questions = $this->input('questions', []);
            $exam = $this->route('exam');

            if (! is_array($questions) || ! $exam instanceof Exam) {
                return;
            }

            $candidateQuestionTexts = collect($questions)
                ->filter(fn ($question) => is_array($question) && is_string($question['question_text'] ?? null))
                ->pluck('question_text')
                ->all();
            $existingSignatures = QuestionDuplicateChecker::existingSignatures($exam, $candidateQuestionTexts);
            $submittedSignatures = [];

            foreach ($questions as $index => $question) {
                if (! is_array($question)) {
                    continue;
                }

                $options = collect($question['options'] ?? [])
                    ->filter(fn ($option) => is_string($option) && trim($option) !== '')
                    ->values();

                if ($options->count() < 2) {
                    $validator->errors()->add("questions.$index.options", 'Please add at least 2 answer options.');
                }

                if (! $options->has((int) ($question['correct_option'] ?? -1))) {
                    $validator->errors()->add("questions.$index.correct_option", 'Please select the correct answer.');
                }

                $questionText = $question['question_text'] ?? null;
                if (is_string($questionText) && trim($questionText) !== '' && $options->count() >= 2) {
                    $signature = QuestionDuplicateChecker::signature($questionText, $options->all());

                    if (isset($existingSignatures[$signature]) || isset($submittedSignatures[$signature])) {
                        $validator->errors()->add(
                            "questions.$index.question_text",
                            'A question with the same text and answer options already exists in this exam.'
                        );
                    } else {
                        $submittedSignatures[$signature] = true;
                    }
                }
            }
        });
    }
}
