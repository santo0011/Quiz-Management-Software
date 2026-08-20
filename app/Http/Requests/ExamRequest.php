<?php

namespace App\Http\Requests;

use App\Models\SchoolClass;
use Illuminate\Foundation\Http\FormRequest;

class ExamRequest extends FormRequest
{
    public function authorize(): bool
    {
        $exam = $this->route('exam');

        if ($exam && $exam->isPublished() && $this->isMethod('PUT')) {
            return false;
        }

        if ($this->user()?->role === 'Branch') {
            return ! $exam || $exam->branch_id === $this->user()->branch_id;
        }

        if ($this->user()?->role === 'Super Admin') {
            return true;
        }

        return false;
    }

    public function rules(): array
    {
        $isBranch = $this->user()?->role === 'Branch';

        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'branch_id' => $isBranch ? ['nullable'] : ['sometimes', 'required', 'exists:branches,id'],
            'school_class_id' => ['required', 'exists:school_classes,id'],
            'marks_per_question' => ['required', 'numeric', 'min:0.01', 'max:9999.99'],
            'total_marks' => ['required', 'numeric', 'min:1'],
            'duration_minutes' => ['required', 'integer', 'min:1', 'max:1440'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after_or_equal:starts_at'],
            'passing_marks' => ['nullable', 'integer', 'min:0'],
            'maximum_attempts' => ['required', 'integer', 'min:1', 'max:20'],
            'randomize_questions' => ['nullable', 'boolean'],
            'randomize_answers' => ['nullable', 'boolean'],
            'negative_marking_enabled' => ['nullable', 'boolean'],
            'negative_marks' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $branchId = $this->user()?->role === 'Branch'
                ? $this->user()?->branch_id
                : ($this->route('exam')?->branch_id ?: $this->input('branch_id'));

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

        $validated['marks_per_question'] = (float) ($validated['marks_per_question'] ?? 1);

        return $validated;
    }
}
