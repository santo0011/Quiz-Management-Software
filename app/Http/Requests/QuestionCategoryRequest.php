<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class QuestionCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        if (! in_array($this->user()?->role, ['Super Admin', 'Branch'], true)) {
            return false;
        }

        $category = $this->route('question_category');

        if ($category && $this->user()?->role === 'Branch') {
            return $category->branch_id === $this->user()?->branch_id;
        }

        return true;
    }

    protected function prepareForValidation(): void
    {
        if (! $this->filled('branch_id')) {
            $this->merge(['branch_id' => null]);
        }
    }

    public function rules(): array
    {
        $category = $this->route('question_category');
        $isBranch = $this->user()?->role === 'Branch';
        $branchId = $isBranch
            ? $this->user()?->branch_id
            : ($this->input('branch_id') ?: $category?->branch_id);

        return [
            'branch_id' => $isBranch ? ['nullable'] : ['nullable', 'exists:branches,id'],
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('question_categories', 'name')
                    ->where(fn ($query) => $query->where('branch_id', $branchId))
                    ->ignore($category?->id),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Please enter the category name.',
            'name.unique' => 'This category already exists for the selected branch.',
        ];
    }
}
