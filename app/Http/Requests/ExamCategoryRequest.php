<?php

namespace App\Http\Requests;

use App\Models\Exam;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ExamCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        $exam = $this->route('exam');

        if (! $exam instanceof Exam) {
            return false;
        }

        if ($exam->hasBeenAttempted()) {
            throw new AuthorizationException(Exam::LOCK_MESSAGE);
        }

        if ($this->user()?->role === 'Branch') {
            return $exam->branch_id === $this->user()->branch_id;
        }

        return $this->user()?->role === 'Super Admin';
    }

    public function rules(): array
    {
        $exam = $this->route('exam');

        return [
            'question_category_id' => [
                'required',
                Rule::exists('question_categories', 'id')->where('branch_id', $exam?->branch_id),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'question_category_id.required' => 'Please select a question category for this exam.',
            'question_category_id.exists' => 'Please select a valid question category for this branch.',
        ];
    }
}
