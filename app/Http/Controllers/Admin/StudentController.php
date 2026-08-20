<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StudentRequest;
use App\Models\Branch;
use App\Models\SchoolClass;
use App\Models\Student;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StudentController extends Controller
{
    public function index(Request $request): View
    {
        $branchId = $request->integer('branch_id') ?: null;

        $students = Student::with('branch')
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
            'classes' => SchoolClass::when($branchId, fn ($query) => $query->where('branch_id', $branchId))->orderBy('name')->get(),
            'filters' => $request->only(['search', 'class', 'branch_id']),
        ]);
    }

    public function create(): View
    {
        return view('admin.students.create', [
            'branches' => Branch::orderBy('name')->get(),
            'classes' => collect(),
            'student' => new Student,
        ]);
    }

    public function store(StudentRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $branchId = (int) $validated['branch_id'];
        $schoolClass = $this->resolveSchoolClass($validated, $branchId);
        $validated['branch_id'] = $branchId;
        $validated['class_id'] = $schoolClass->id;
        $validated['class'] = $schoolClass->name;

        Student::create($validated);

        return redirect()->route('admin.students.index')->with('success', 'Student added successfully.');
    }

    public function show(Student $student): View
    {
        return view('admin.students.show', [
            'student' => $student->load('branch'),
            'selectedBranch' => $student->branch,
        ]);
    }

    public function edit(Student $student): View
    {
        return view('admin.students.edit', [
            'student' => $student,
            'selectedBranch' => $student->branch,
            'classes' => SchoolClass::where('branch_id', $student->branch_id)->orderBy('name')->get(),
        ]);
    }

    public function update(StudentRequest $request, Student $student): RedirectResponse
    {
        $validated = $request->validated();
        $branchId = $student->branch_id;
        $schoolClass = $this->resolveSchoolClass($validated, $branchId);
        $validated['branch_id'] = $branchId;
        $validated['class_id'] = $schoolClass->id;
        $validated['class'] = $schoolClass->name;

        $student->update($validated);

        return redirect()->route('admin.students.index')->with('success', 'Student updated successfully.');
    }

    public function destroy(Student $student): RedirectResponse
    {
        $hasAttempts = $student->attempts()->exists();

        if ($hasAttempts) {
            return redirect()->route('admin.students.index')
                ->with('error', 'This student has exam history and cannot be permanently deleted. Please deactivate the student instead.');
        }

        $student->delete();

        return redirect()->route('admin.students.index')->with('success', 'Student deleted successfully.');
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
            return SchoolClass::whereKey($validated['class_id'])->where('branch_id', $branchId)->firstOrFail();
        }

        return SchoolClass::firstOrCreate([
            'branch_id' => $branchId,
            'name' => trim($validated['class']),
        ]);
    }
}
