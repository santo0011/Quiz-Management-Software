<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SchoolClassRequest extends FormRequest
{
    public function authorize(): bool
    {
        if (! in_array($this->user()?->role, ['Super Admin', 'Branch'], true)) {
            return false;
        }

        $schoolClass = $this->route('class');

        if ($schoolClass && $this->user()?->role === 'Branch') {
            return $schoolClass->branch_id === $this->user()?->branch_id;
        }

        return true;
    }

    public function rules(): array
    {
        $schoolClass = $this->route('class');
        $branchId = $this->user()?->role === 'Branch'
            ? $this->user()?->branch_id
            : ($schoolClass?->branch_id ?: $this->session()->get('admin_selected_branch_id'));

        return [
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('school_classes', 'name')
                    ->where(fn ($query) => $query->where('branch_id', $branchId))
                    ->ignore($schoolClass?->id),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Please enter the class name.',
            'name.unique' => 'This class already exists for the active branch.',
        ];
    }
}
