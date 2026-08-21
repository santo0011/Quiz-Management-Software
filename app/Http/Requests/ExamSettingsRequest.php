<?php

namespace App\Http\Requests;

use App\Models\Exam;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Http\FormRequest;

class ExamSettingsRequest extends FormRequest
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
        return [
            'duration_minutes' => ['required', 'integer', 'min:1', 'max:1440'],
            'passing_marks' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
