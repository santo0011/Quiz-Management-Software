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
    public function index(Request $request): View|RedirectResponse
    {
        $branch = $this->selectedBranch();

        if (! $branch) {
            return redirect()->route('admin.branch-selection.index')
                ->with('success', 'Please select a branch first to manage branch-related data.');
        }

        $students = Student::with('branch')
            ->forBranch($branch->id)
            ->search($request->string('search')->toString())
            ->when($request->filled('class'), fn ($query) => $query->where('class', $request->string('class')->toString()))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('admin.students.index', [
            'selectedBranch' => $branch,
            'student' => new Student,
            'students' => $students,
            'classes' => SchoolClass::where('branch_id', $branch->id)->orderBy('name')->get(),
            'filters' => $request->only(['search', 'class']),
        ]);
    }

    public function create(): View|RedirectResponse
    {
        $branch = $this->selectedBranch();

        if (! $branch) {
            return redirect()->route('admin.branch-selection.index')
                ->with('success', 'Please select a branch first to manage branch-related data.');
        }

        return view('admin.students.create', [
            'selectedBranch' => $branch,
            'classes' => SchoolClass::where('branch_id', $branch->id)->orderBy('name')->get(),
            'student' => new Student,
        ]);
    }

    public function store(StudentRequest $request): RedirectResponse
    {
        $branch = $this->selectedBranch();

        if (! $branch) {
            return redirect()->route('admin.branch-selection.index');
        }

        $validated = $request->validated();
        $branchId = $branch->id;
        $schoolClass = $this->resolveSchoolClass($validated, $branchId);
        $validated['branch_id'] = $branchId;
        $validated['class_id'] = $schoolClass->id;
        $validated['class'] = $schoolClass->name;

        Student::create($validated);

        session(['admin_selected_branch_id' => $branchId]);

        return redirect()->route('admin.students.index')->with('success', 'Student added successfully.');
    }

    public function show(Student $student): View
    {
        $this->authorizeSelectedBranch($student);

        return view('admin.students.show', [
            'student' => $student->load('branch'),
            'selectedBranch' => $student->branch,
        ]);
    }

    public function edit(Student $student): View
    {
        $this->authorizeSelectedBranch($student);

        return view('admin.students.edit', [
            'student' => $student,
            'selectedBranch' => $student->branch,
            'classes' => SchoolClass::where('branch_id', $student->branch_id)->orderBy('name')->get(),
        ]);
    }

    public function update(StudentRequest $request, Student $student): RedirectResponse
    {
        $this->authorizeSelectedBranch($student);

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
        $this->authorizeSelectedBranch($student);
        $student->delete();

        return redirect()->route('admin.students.index')->with('success', 'Student deleted successfully.');
    }

    private function selectedBranch(): ?Branch
    {
        $branchId = session('admin_selected_branch_id');

        return $branchId ? Branch::find($branchId) : null;
    }

    private function authorizeSelectedBranch(Student $student): void
    {
        $branch = $this->selectedBranch();

        abort_if(! $branch || $student->branch_id !== $branch->id, 403, 'This student is not in the selected branch.');
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
