<?php

namespace App\Http\Requests;

use App\Models\Exam;
use App\Models\Teacher;
use App\Support\HtmlSanitizer;
use App\Support\OptionContent;
use App\Support\QuestionDuplicateChecker;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class QuestionRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        // All question text (standalone or under a Summary/Passage) now comes
        // from the CKEditor question-text field, so it always needs sanitizing
        // before storage — it's later rendered as raw HTML.
        if ($this->has('question_text')) {
            $this->merge(['question_text' => HtmlSanitizer::sanitize($this->input('question_text'))]);
        }

        if (is_array($this->input('options'))) {
            $this->merge(['options' => array_map(
                fn ($option) => is_string($option) ? OptionContent::normalize($option) : $option,
                $this->input('options')
            )]);
        }
    }

    public function authorize(): bool
    {
        $exam = $this->route('exam') ?: $this->route('question')?->exam;

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
        $exam = $this->route('exam') ?: $this->route('question')?->exam;

        return [
            'question_text' => ['required', 'string'],
            'marks' => ['required', 'integer', 'min:1'],
            'question_category_id' => [
                'nullable',
                Rule::exists('question_categories', 'id')->where(
                    fn ($query) => $query->where(fn ($q) => $q->where('branch_id', $exam?->branch_id)->orWhereNull('branch_id'))
                ),
            ],
            'explanation' => ['nullable', 'string'],
            'options' => ['required', 'array', 'min:2'],
            'options.*' => ['required', 'string', 'max:'.OptionContent::MAX_LENGTH],
            'option_ids' => ['nullable', 'array'],
            'option_ids.*' => ['nullable', 'integer'],
            'correct_option' => ['required', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'options.*.required' => 'Every answer option needs text, an image or an equation (or remove the empty option).',
            'options.*.max' => 'An answer option is too large. Please use smaller images.',
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

            $questionText = $this->input('question_text');
            $submittedOptions = collect($this->input('options', []))
                ->filter(fn ($option) => is_string($option) && trim($option) !== '')
                ->values()
                ->all();

            if ($exam instanceof Exam && is_string($questionText) && trim($questionText) !== '' && count($submittedOptions) >= 2) {
                $existingSignatures = QuestionDuplicateChecker::existingSignatures($exam, [$questionText], $question?->id);
                $signature = QuestionDuplicateChecker::signature($questionText, $submittedOptions);

                if (isset($existingSignatures[$signature])) {
                    $validator->errors()->add(
                        'question_text',
                        'A question with the same text and answer options already exists in this exam.'
                    );
                }
            }
        });
    }
}
