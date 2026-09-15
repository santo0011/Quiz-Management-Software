<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'Super Admin';
    }

    public function rules(): array
    {
        return [
            'site_name' => ['nullable', 'string', 'max:100'],
            'logo' => ['nullable', 'image', 'max:2048'],
            'mail_mailer' => ['nullable', 'string', 'in:smtp,sendmail,log'],
            'mail_host' => ['nullable', 'string', 'max:255', 'required_with:mail_port,mail_username,mail_from_address'],
            'mail_port' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'mail_username' => ['nullable', 'string', 'max:255'],
            'mail_password' => ['nullable', 'string', 'max:255'],
            'mail_encryption' => ['nullable', 'string', 'in:tls,ssl,'],
            'mail_from_address' => ['nullable', 'email', 'max:255'],
            'mail_from_name' => ['nullable', 'string', 'max:255'],
            'common_student_password' => ['nullable', 'string', 'min:6', 'max:255'],
            'default_teacher_override_branch_id' => ['nullable', 'exists:branches,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'mail_host.required_with' => 'Please enter the SMTP host to configure mail settings.',
            'common_student_password.min' => 'The Teacher Override password must be at least 6 characters.',
        ];
    }
}
