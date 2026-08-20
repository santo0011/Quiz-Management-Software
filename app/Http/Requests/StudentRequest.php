<?php

namespace App\Http\Requests;

use App\Models\Branch;
use App\Models\SchoolClass;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        if (! in_array($this->user()?->role, ['Super Admin', 'Branch'], true)) {
            return false;
        }

        $student = $this->route('student');

        if ($student && $this->user()?->role === 'Branch') {
            return $student->branch_id === $this->user()?->branch_id;
        }

        return true;
    }

    public function rules(): array
    {
        $studentId = $this->route('student')?->id;
        $isBranch = $this->user()?->role === 'Branch';

        return [
            'student_name' => ['required', 'string', 'max:255'],
            'guardian_name' => ['required', 'string', 'max:255'],
            'branch_id' => $isBranch ? ['nullable'] : ['sometimes', 'required', 'exists:branches,id'],
            'class_id' => ['nullable', 'required_without:class', 'exists:school_classes,id'],
            'class' => ['nullable', 'required_without:class_id', 'string', 'max:100'],
            'phone_number' => ['required', 'string', 'max:30'],
            'email' => ['required', 'email', 'max:255', Rule::unique('students', 'email')->ignore($studentId)],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            if ($validator->errors()->has('class_id') || ! $this->filled('class_id')) {
                return;
            }

            $targetBranchId = $this->user()?->role === 'Branch'
                ? $this->user()?->branch_id
                : ($this->route('student')?->branch_id ?: $this->input('branch_id'));

            $classBelongsToBranch = SchoolClass::whereKey($this->input('class_id'))
                ->visibleToBranch($targetBranchId)
                ->exists();

            if (! $classBelongsToBranch) {
                $validator->errors()->add('class_id', 'Please select a class from the selected branch.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'student_name.required' => 'Please enter the student name.',
            'guardian_name.required' => 'Please enter the guardian name.',
            'class_id.required' => 'Please select the class.',
            'class_id.required_without' => 'Please select the class.',
            'class_id.exists' => 'Please select a valid class.',
            'class.required_without' => 'Please select or enter the class.',
            'phone_number.required' => 'Please enter the phone number.',
            'email.required' => 'Please enter the student email address.',
            'email.email' => 'Please enter a valid student email address.',
            'email.unique' => 'A student with this email address already exists.',
        ];
    }
}
