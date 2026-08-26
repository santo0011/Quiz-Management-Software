<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AcademicSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'Super Admin';
    }

    public function rules(): array
    {
        $session = $this->route('academic_session');

        return [
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('academic_sessions', 'name')->ignore($session?->id),
            ],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'description' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Please enter the session name.',
            'name.unique' => 'This session already exists.',
            'start_date.required' => 'Please enter the start date.',
            'end_date.required' => 'Please enter the end date.',
            'end_date.after_or_equal' => 'End date must be on or after the start date.',
        ];
    }

    public function validated($key = null, $default = null)
    {
        $validated = parent::validated($key, $default);

        if ($key !== null) {
            return $validated;
        }

        $validated['is_active'] = $this->boolean('is_active');

        return $validated;
    }
}
