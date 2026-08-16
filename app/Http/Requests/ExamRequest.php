<?php

namespace App\Http\Requests;

use App\Models\Exam;
use App\Models\SchoolClass;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ExamRequest extends FormRequest
{
    public function authorize(): bool
    {
        $exam = $this->route('exam');

        if ($this->user()?->role === 'Branch') {
            return ! $exam || $exam->branch_id === $this->user()->branch_id;
        }

        if ($this->user()?->role === 'Super Admin') {
            return ! $exam || $exam->branch_id === $this->session()->get('admin_selected_branch_id');
        }

        return false;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'school_class_id' => ['required', 'exists:school_classes,id'],
            'total_marks' => ['required', 'integer', 'min:1'],
            'duration_minutes' => ['required', 'integer', 'min:1', 'max:1440'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'passing_marks' => ['nullable', 'integer', 'min:0', 'lte:total_marks'],
            'maximum_attempts' => ['required', 'integer', 'min:1', 'max:20'],
            'randomize_questions' => ['nullable', 'boolean'],
            'randomize_answers' => ['nullable', 'boolean'],
            'negative_marking_enabled' => ['nullable', 'boolean'],
            'negative_marks' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', Rule::in([Exam::STATUS_DRAFT, Exam::STATUS_PUBLISHED, Exam::STATUS_CLOSED])],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $branchId = $this->user()?->role === 'Branch'
                ? $this->user()?->branch_id
                : $this->session()->get('admin_selected_branch_id');

            if (! $branchId) {
                $validator->errors()->add('school_class_id', 'Please select a branch first to manage branch-related data.');

                return;
            }

            if ($this->filled('school_class_id')) {
                $classExists = SchoolClass::whereKey($this->input('school_class_id'))
                    ->where('branch_id', $branchId)
                    ->exists();

                if (! $classExists) {
                    $validator->errors()->add('school_class_id', 'Please select a class from the active branch.');
                }
            }
        });
    }

    public function validated($key = null, $default = null)
    {
        $validated = parent::validated($key, $default);

        if ($key !== null) {
            return $validated;
        }

        foreach (['randomize_questions', 'randomize_answers', 'negative_marking_enabled'] as $field) {
            $validated[$field] = $this->boolean($field);
        }

        $validated['negative_marks'] = $validated['negative_marking_enabled']
            ? ($validated['negative_marks'] ?? 0)
            : 0;

        return $validated;
    }
}
