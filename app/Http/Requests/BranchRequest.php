<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class BranchRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->role === 'Super Admin';
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'unique:branches,name,'.$this->route('branch')?->id],
            'email' => ['required', 'email', 'max:255', 'unique:branches,email,'.$this->route('branch')?->id, 'unique:users,email,'.$this->route('branch')?->user?->id],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Please enter a branch name.',
            'name.unique' => 'A branch with this name already exists.',
            'email.required' => 'Please enter the branch email address.',
            'email.email' => 'Please enter a valid branch email address.',
            'email.unique' => 'This branch email address is already in use.',
        ];
    }
}
