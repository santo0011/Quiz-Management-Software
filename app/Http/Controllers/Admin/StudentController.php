<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StudentRequest;
use App\Models\Branch;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Subject;
use App\Services\GuardianResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StudentController extends Controller
{
    public function index(Request $request): View
    {
        $branchId = $request->integer('branch_id') ?: null;

        $students = Student::with(['branch', 'subjects'])
            ->when($branchId, fn ($query) => $query->forBranch($branchId))
            ->search($request->string('search')->toString())
            ->when($request->filled('class'), fn ($query) => $query->where('class', $request->string('class')->toString()))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.students.index', [
            'branches' => Branch::orderBy('name')->get(),
            'selectedBranchId' => $branchId,
            'student' => new Student,
            'students' => $students,
            'classes' => SchoolClass::when($branchId, fn ($query) => $query->visibleToBranch($branchId))->orderBy('name')->get(),
            'subjects' => Subject::orderBy('name')->get(),
            'filters' => $request->only(['search', 'class', 'branch_id']),
        ]);
    }

    public function create(): View
    {
        return view('admin.students.create', [
            'branches' => Branch::orderBy('name')->get(),
            'classes' => collect(),
            'subjects' => Subject::orderBy('name')->get(),
            'student' => new Student,
        ]);
    }

    public function store(StudentRequest $request): RedirectResponse
    {
        $validated = GuardianResolver::resolve($request->validated());
        $subjectIds = $validated['subject_ids'] ?? [];
        unset($validated['subject_ids']);
        $branchId = (int) $validated['branch_id'];
        $schoolClass = $this->resolveSchoolClass($validated, $branchId);
        $validated['branch_id'] = $branchId;
        $validated['class_id'] = $schoolClass->id;
        $validated['class'] = $schoolClass->name;

        $student = Student::create($validated);
        $student->subjects()->sync($subjectIds);

        return redirect()->route('admin.students.index')->with('success', 'Student added successfully.');
    }

    public function show(Student $student): View
    {
        return view('admin.students.show', [
            'student' => $student->load(['branch', 'subjects']),
            'selectedBranch' => $student->branch,
        ]);
    }

    /**
     * Students are read-only local records — their core information (name,
     * Grade, Zoho ID, etc.) comes from Zoho, so there is no "Edit Student"
     * action anymore. Subject assignment is the one thing still managed
     * locally, entirely independent of Zoho's data.
     */
    public function updateSubjects(Request $request, Student $student): RedirectResponse
    {
        $validated = $request->validate([
            'subject_ids' => ['nullable', 'array'],
            'subject_ids.*' => ['integer', 'exists:subjects,id'],
        ]);

        $student->subjects()->sync($validated['subject_ids'] ?? []);

        return redirect()->route('admin.students.index')->with('success', 'Student subjects updated successfully.');
    }

    public function toggleActive(Student $student): RedirectResponse
    {
        $student->update(['is_active' => ! $student->is_active]);

        $message = $student->is_active
            ? 'Student activated successfully.'
            : 'Student deactivated successfully.';

        return redirect()->route('admin.students.index')->with('success', $message);
    }

    private function resolveSchoolClass(array $validated, int $branchId): SchoolClass
    {
        if (! empty($validated['class_id'])) {
            return SchoolClass::whereKey($validated['class_id'])->visibleToBranch($branchId)->firstOrFail();
        }

        return SchoolClass::firstOrCreate([
            'branch_id' => $branchId,
            'name' => trim($validated['class']),
        ]);
    }
}
