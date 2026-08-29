<?php

namespace App\Http\Requests;

use App\Models\Exam;
use App\Support\HtmlSanitizer;
use Illuminate\Foundation\Http\FormRequest;

class PassageGroupRequest extends FormRequest
{
    public function authorize(): bool
    {
        $exam = $this->route('exam') ?: $this->route('passageGroup')?->exam;

        if (! $exam instanceof Exam) {
            return false;
        }

        if ($this->user()?->role === 'Branch') {
            return $exam->branch_id === $this->user()->branch_id;
        }

        return $this->user()?->role === 'Super Admin';
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('content')) {
            $this->merge(['content' => HtmlSanitizer::sanitize($this->input('content'))]);
        }
    }

    public function rules(): array
    {
        return [
            'content' => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'content.required' => 'Please enter the passage/summary content.',
        ];
    }
}
