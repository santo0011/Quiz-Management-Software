<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BranchRequest extends FormRequest
{
    /**
     * Branch User ID format: letters, numbers, dot, dash and underscore.
     * Shared with BranchController::checkUsername() so the live check and
     * the final save always agree.
     */
    public const USERNAME_RULES = ['required', 'string', 'min:3', 'max:50', 'regex:/^[a-z0-9._-]+$/'];

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->role === 'Super Admin';
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('username')) {
            $this->merge(['username' => strtolower(trim((string) $this->input('username')))]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $branch = $this->route('branch');

        return [
            'name' => ['required', 'string', 'max:255', 'unique:branches,name,'.$branch?->id],
            'username' => [...self::USERNAME_RULES, Rule::unique('users', 'username')->ignore($branch?->user?->id)],
            // The same email may be shared by several branches — branches
            // log in with their unique User ID, not the email.
            'email' => ['required', 'email', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return self::usernameMessages() + [
            'name.required' => 'Please enter a branch name.',
            'name.unique' => 'A branch with this name already exists.',
            'email.required' => 'Please enter the branch email address.',
            'email.email' => 'Please enter a valid branch email address.',
        ];
    }

    public static function usernameMessages(): array
    {
        return [
            'username.required' => 'Please enter a User ID for the branch login.',
            'username.min' => 'User ID must be at least 3 characters.',
            'username.max' => 'User ID may not be longer than 50 characters.',
            'username.regex' => 'User ID may only contain letters, numbers, dot (.), dash (-) and underscore (_), with no spaces.',
            'username.unique' => 'This User ID is already taken. Please choose another one.',
        ];
    }
}
