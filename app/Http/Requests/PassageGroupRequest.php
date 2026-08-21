<?php

namespace App\Http\Requests;

use App\Models\Exam;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Http\FormRequest;

class PassageGroupRequest extends FormRequest
{
    public function authorize(): bool
    {
        $exam = $this->route('exam') ?: $this->route('passageGroup')?->exam;

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
