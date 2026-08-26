<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SubjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'Super Admin';
    }

    public function rules(): array
    {
        $subject = $this->route('subject');

        return [
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('subjects', 'name')->ignore($subject?->id),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Please enter the subject name.',
            'name.unique' => 'This subject already exists.',
        ];
    }
}
