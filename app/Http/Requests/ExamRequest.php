<?php

namespace App\Http\Requests;

use App\Models\SchoolClass;
use App\Models\Teacher;
use Illuminate\Foundation\Http\FormRequest;

class ExamRequest extends FormRequest
{
    public function authorize(): bool
    {
        $exam = $this->route('exam');

        if ($this->user()?->role === 'Branch' || $this->user() instanceof Teacher) {
            return ! $exam || $exam->branch_id === $this->user()->branch_id;
        }

        if ($this->user()?->role === 'Super Admin') {
            return true;
        }

        return false;
    }

    /**
     * An Exam can be assigned to several Grades via school_class_ids[]. A
     * single legacy school_class_id is still accepted and treated as a
     * one-Grade selection.
     */
    protected function prepareForValidation(): void
    {
        if (! $this->has('school_class_ids') && $this->filled('school_class_id')) {
            $this->merge(['school_class_ids' => [$this->input('school_class_id')]]);
        }
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'branch_id' => ['nullable', 'exists:branches,id'],
            'school_class_ids' => ['required', 'array', 'min:1'],
            'school_class_ids.*' => ['integer', 'distinct', 'exists:school_classes,id'],
            'subject_id' => ['required', 'exists:subjects,id'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after_or_equal:starts_at'],
            'total_marks' => ['nullable', 'integer', 'min:0'],
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
            'school_class_ids.required' => 'Please select at least one grade for this exam.',
            'school_class_ids.array' => 'Please select at least one grade for this exam.',
            'school_class_ids.min' => 'Please select at least one grade for this exam.',
            'school_class_ids.*.integer' => 'Please select a valid grade.',
            'school_class_ids.*.distinct' => 'Each grade can only be selected once.',
            'school_class_ids.*.exists' => 'Please select a valid grade.',
            'subject_id.required' => 'Please select a subject for this exam.',
            'subject_id.exists' => 'Please select a valid subject.',
            'starts_at.required' => 'Please choose when the exam should start.',
            'starts_at.date' => 'Please enter a valid start date and time.',
            'ends_at.required' => 'Please choose when the exam should end.',
            'ends_at.date' => 'Please enter a valid end date and time.',
            'ends_at.after_or_equal' => 'The end date and time must be on or after the start date and time.',
            'total_marks.integer' => 'Total marks must be a whole number.',
            'total_marks.min' => 'Total marks cannot be negative.',
            'duration_minutes.required' => 'Please enter the exam duration.',
            'duration_minutes.integer' => 'Exam duration must be a whole number of minutes.',
            'duration_minutes.min' => 'Exam duration must be at least 1 minute.',
            'duration_minutes.max' => 'Exam duration cannot be longer than 1440 minutes (24 hours).',
            'maximum_attempts.required' => 'Please enter the attempt limit.',
            'maximum_attempts.integer' => 'Attempt limit must be a whole number.',
            'maximum_attempts.min' => 'Attempt limit must be at least 1.',
            'maximum_attempts.max' => 'Attempt limit cannot be more than 20.',
            'negative_marks.numeric' => 'Negative marks must be a number.',
            'negative_marks.min' => 'Negative marks cannot be negative.',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $isBranchUser = $this->user()?->role === 'Branch' || $this->user() instanceof Teacher;
            $branchId = $isBranchUser
                ? $this->user()?->branch_id
                : $this->route('exam')?->branch_id;

            if ($isBranchUser && ! $branchId) {
                $validator->errors()->add('school_class_ids', 'Please select a branch first to manage branch-related data.');

                return;
            }

            // Super Admin exams are available to all branches, so there is no
            // branch context to scope the grades against once branch_id is null.
            $gradeIds = $this->gradeIds();

            if (! $branchId || $gradeIds === [] || $validator->errors()->has('school_class_ids*')) {
                return;
            }

            // Every selected grade must belong to the branch (or be global) —
            // a branch can't assign its exam to another branch's grade.
            $visibleCount = SchoolClass::whereKey($gradeIds)
                ->visibleToBranch($branchId)
                ->count();

            if ($visibleCount !== count($gradeIds)) {
                $validator->errors()->add('school_class_ids', 'Please select grades from the active branch only.');
            }
        });
    }

    public function validated($key = null, $default = null)
    {
        $validated = parent::validated($key, $default);

        if ($key !== null) {
            return $validated;
        }

        // Grades are stored through the exam_school_class pivot (see
        // Exam::syncGrades()); the first one doubles as the primary Grade.
        unset($validated['school_class_ids']);
        $validated['school_class_id'] = $this->gradeIds()[0];

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

    /**
     * The selected Grade ids, de-duplicated, in submitted order.
     *
     * @return array<int, int>
     */
    public function gradeIds(): array
    {
        $ids = (array) $this->input('school_class_ids', []);

        return array_values(array_unique(array_map('intval', array_filter($ids, fn ($id) => is_numeric($id)))));
    }
}
