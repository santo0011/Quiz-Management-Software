<?php

namespace App\Http\Requests;

use App\Models\Exam;
use Illuminate\Foundation\Http\FormRequest;

class ExamSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        $exam = $this->route('exam');

        if (! $exam instanceof Exam || $exam->isPublished()) {
            return false;
        }

        if ($this->user()?->role === 'Branch') {
            return $exam->branch_id === $this->user()->branch_id;
        }

        return $this->user()?->role === 'Super Admin';
    }

    public function rules(): array
    {
        return [
            'duration_minutes' => ['required', 'integer', 'min:1', 'max:1440'],
            'passing_marks' => ['nullable', 'integer', 'min:0'],
            'marks_per_question' => ['required', 'numeric', 'min:0.01', 'max:9999.99'],
        ];
    }
}
