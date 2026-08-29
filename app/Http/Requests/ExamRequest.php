<?php

namespace App\Http\Requests;

use App\Models\SchoolClass;
use Illuminate\Foundation\Http\FormRequest;

class ExamRequest extends FormRequest
{
    public function authorize(): bool
    {
        $exam = $this->route('exam');

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
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'branch_id' => ['nullable', 'exists:branches,id'],
            'school_class_id' => ['required', 'exists:school_classes,id'],
            'subject_id' => ['required', 'exists:subjects,id'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after_or_equal:starts_at'],
            'total_marks' => ['nullable', 'integer', 'min:0'],
            'passing_marks' => ['nullable', 'integer', 'min:0'],
            'duration_minutes' => ['required', 'integer', 'min:1', 'max:1440'],
            'maximum_attempts' => ['required', 'integer', 'min:1', 'max:20'],
            'randomize_questions' => ['nullable', 'boolean'],
            'randomize_answers' => ['nullable', 'boolean'],
            'negative_marking_enabled' => ['nullable', 'boolean'],
            'negative_marks' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Please enter an exam title.',
            'title.max' => 'Exam title cannot be longer than 255 characters.',
            'branch_id.exists' => 'Please select a valid branch.',
            'school_class_id.required' => 'Please select a grade for this exam.',
            'school_class_id.exists' => 'Please select a valid grade.',
            'subject_id.required' => 'Please select a subject for this exam.',
            'subject_id.exists' => 'Please select a valid subject.',
            'starts_at.required' => 'Please choose when the exam should start.',
            'starts_at.date' => 'Please enter a valid start date and time.',
            'ends_at.required' => 'Please choose when the exam should end.',
            'ends_at.date' => 'Please enter a valid end date and time.',
            'ends_at.after_or_equal' => 'The end date and time must be on or after the start date and time.',
            'total_marks.integer' => 'Total marks must be a whole number.',
            'total_marks.min' => 'Total marks cannot be negative.',
            'passing_marks.integer' => 'Pass marks must be a whole number.',
            'passing_marks.min' => 'Pass marks cannot be negative.',
            'duration_minutes.required' => 'Please enter the exam duration.',
            'duration_minutes.integer' => 'Exam duration must be a whole number of minutes.',
            'duration_minutes.min' => 'Exam duration must be at least 1 minute.',
            'duration_minutes.max' => 'Exam duration cannot be longer than 1440 minutes (24 hours).',
            'maximum_attempts.required' => 'Please enter the maximum number of attempts.',
            'maximum_attempts.integer' => 'Maximum attempts must be a whole number.',
            'maximum_attempts.min' => 'Maximum attempts must be at least 1.',
            'maximum_attempts.max' => 'Maximum attempts cannot be more than 20.',
            'negative_marks.numeric' => 'Negative marks must be a number.',
            'negative_marks.min' => 'Negative marks cannot be negative.',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $isBranchUser = $this->user()?->role === 'Branch';
            $branchId = $isBranchUser
                ? $this->user()?->branch_id
                : $this->route('exam')?->branch_id;

            if ($isBranchUser && ! $branchId) {
                $validator->errors()->add('school_class_id', 'Please select a branch first to manage branch-related data.');

                return;
            }

            // Super Admin exams are available to all branches, so there is no
            // branch context to scope the grade against once branch_id is null.
            if (! $branchId || ! $this->filled('school_class_id')) {
                return;
            }

            $classExists = SchoolClass::whereKey($this->input('school_class_id'))
                ->visibleToBranch($branchId)
                ->exists();

            if (! $classExists) {
                $validator->errors()->add('school_class_id', 'Please select a grade from the active branch.');
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

        // total_marks is not nullable in the database. On create the field
        // is always editable, so always default it. On update it's only
        // submitted when the exam has no questions yet (otherwise the
        // input is disabled and total_marks stays auto-calculated) — only
        // coerce it there if it was actually part of the request.
        if ($this->isMethod('POST') || array_key_exists('total_marks', $validated)) {
            $validated['total_marks'] = $validated['total_marks'] ?? 0;
        }

        return $validated;
    }
}
