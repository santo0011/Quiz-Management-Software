<?php

namespace App\Http\Controllers\Branch;

use App\Http\Controllers\Controller;
use App\Http\Requests\StudentRequest;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Subject;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StudentController extends Controller
{
    public function index(Request $request): View
    {
        $branch = $request->user()->branch;
        abort_if(! $branch, 403, 'Your account is not linked to a branch.');

        $students = Student::with('branch')
            ->forBranch($branch->id)
            ->search($request->string('search')->toString())
            ->when($request->filled('class'), fn ($query) => $query->where('class', $request->string('class')->toString()))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('branch.students.index', [
            'branch' => $branch,
            'students' => $students,
            'classes' => SchoolClass::visibleToBranch($branch->id)->orderBy('name')->get(),
            'filters' => $request->only(['search', 'class']),
        ]);
    }

    public function create(Request $request): View
    {
        $branch = $request->user()->branch;
        abort_if(! $branch, 403, 'Your account is not linked to a branch.');

        return view('branch.students.create', [
            'branch' => $branch,
            'classes' => SchoolClass::visibleToBranch($branch->id)->orderBy('name')->get(),
            'subjects' => Subject::orderBy('name')->get(),
            'student' => new Student,
        ]);
    }

    public function store(StudentRequest $request): RedirectResponse
    {
        $branch = $request->user()->branch;
        abort_if(! $branch, 403, 'Your account is not linked to a branch.');

        $validated = $request->validated();
        $subjectIds = $validated['subject_ids'] ?? [];
        unset($validated['subject_ids']);
        $schoolClass = $this->resolveSchoolClass($validated, $branch->id);
        $validated['branch_id'] = $branch->id;
        $validated['class_id'] = $schoolClass->id;
        $validated['class'] = $schoolClass->name;

        $student = Student::create($validated);
        $student->subjects()->sync($subjectIds);

        return redirect()->route('branch.students.index')->with('success', 'Student added successfully.');
    }

    public function show(Request $request, Student $student): View
    {
        $this->authorizeBranchStudent($request, $student);

        return view('branch.students.show', [
            'branch' => $request->user()->branch,
            'student' => $student->load('subjects'),
        ]);
    }

    public function edit(Request $request, Student $student): View
    {
        $this->authorizeBranchStudent($request, $student);

        return view('branch.students.edit', [
            'branch' => $request->user()->branch,
            'student' => $student->load('subjects'),
            'classes' => SchoolClass::visibleToBranch($request->user()->branch_id)->orderBy('name')->get(),
            'subjects' => Subject::orderBy('name')->get(),
        ]);
    }

    public function update(StudentRequest $request, Student $student): RedirectResponse
    {
        $this->authorizeBranchStudent($request, $student);

        $validated = $request->validated();
        $subjectIds = $validated['subject_ids'] ?? [];
        unset($validated['subject_ids']);
        $schoolClass = $this->resolveSchoolClass($validated, $request->user()->branch_id);
        $validated['branch_id'] = $request->user()->branch_id;
        $validated['class_id'] = $schoolClass->id;
        $validated['class'] = $schoolClass->name;

        $student->update($validated);
        $student->subjects()->sync($subjectIds);

        return redirect()->route('branch.students.index')->with('success', 'Student updated successfully.');
    }

    public function destroy(Request $request, Student $student): RedirectResponse
    {
        $this->authorizeBranchStudent($request, $student);

        $hasAttempts = $student->attempts()->exists();

        if ($hasAttempts) {
            return redirect()->route('branch.students.index')
                ->with('error', 'This student has exam history and cannot be permanently deleted. Please deactivate the student instead.');
        }

        $student->delete();

        return redirect()->route('branch.students.index')->with('success', 'Student deleted successfully.');
    }

    public function toggleActive(Request $request, Student $student): RedirectResponse
    {
        $this->authorizeBranchStudent($request, $student);

        $student->update(['is_active' => ! $student->is_active]);

        $message = $student->is_active
            ? 'Student activated successfully.'
            : 'Student deactivated successfully.';

        return redirect()->route('branch.students.index')->with('success', $message);
    }

    private function authorizeBranchStudent(Request $request, Student $student): void
    {
        abort_if(! $request->user()->branch_id || $student->branch_id !== $request->user()->branch_id, 403, 'This student does not belong to your branch.');
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
