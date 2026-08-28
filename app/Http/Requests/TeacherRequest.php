<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TeacherRequest extends FormRequest
{
    public function authorize(): bool
    {
        if ($this->user()?->role !== 'Branch') {
            return false;
        }

        $teacher = $this->route('teacher');

        if ($teacher) {
            return $teacher->branch_id === $this->user()?->branch_id;
        }

        return true;
    }

    public function rules(): array
    {
        $teacherId = $this->route('teacher')?->id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('teachers', 'email')->ignore($teacherId)],
            'phone_number' => ['required', 'string', 'max:30'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Please enter the teacher name.',
            'email.required' => 'Please enter the teacher email address.',
            'email.email' => 'Please enter a valid email address.',
            'email.unique' => 'A teacher with this email address already exists.',
            'phone_number.required' => 'Please enter the phone number.',
        ];
    }
}
