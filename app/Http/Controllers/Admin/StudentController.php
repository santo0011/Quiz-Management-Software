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
        $branch = $this->selectedBranch();

        $students = Student::with(['branch', 'subjects'])
            ->forBranch($branch->id)
            ->search($request->string('search')->toString())
            ->when($request->filled('class'), fn ($query) => $query->where('class', $request->string('class')->toString()))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.students.index', [
            'branch' => $branch,
            'student' => new Student,
            'students' => $students,
            'classes' => SchoolClass::visibleToBranch($branch->id)->orderBy('name')->get(),
            'filters' => $request->only(['search', 'class']),
        ]);
    }

    public function create(): View
    {
        $branch = $this->selectedBranch();

        return view('admin.students.create', [
            'selectedBranch' => $branch,
            'classes' => SchoolClass::visibleToBranch($branch->id)->orderBy('name')->get(),
            'subjects' => Subject::orderBy('name')->get(),
            'student' => new Student,
        ]);
    }

    public function store(StudentRequest $request): RedirectResponse
    {
        $branch = $this->selectedBranch();

        $validated = GuardianResolver::resolve($request->validated());
        $subjectIds = $validated['subject_ids'] ?? [];
        unset($validated['subject_ids']);
        $schoolClass = $this->resolveSchoolClass($validated, $branch->id);
        $validated['branch_id'] = $branch->id;
        $validated['class_id'] = $schoolClass->id;
        $validated['class'] = $schoolClass->name;

        $student = Student::create($validated);
        $student->subjects()->sync($subjectIds);

        return redirect()->route('admin.students.index')->with('success', 'Student added successfully.');
    }

    public function show(Student $student): View
    {
        $this->authorizeSelectedBranchStudent($student);

        return view('admin.students.show', [
            'student' => $student->load(['branch', 'subjects']),
            'selectedBranch' => $student->branch,
        ]);
    }

    public function toggleActive(Student $student): RedirectResponse
    {
        $this->authorizeSelectedBranchStudent($student);

        $student->update(['is_active' => ! $student->is_active]);

        $message = $student->is_active
            ? 'Student activated successfully.'
            : 'Student deactivated successfully.';

        return redirect()->route('admin.students.index')->with('success', $message);
    }

    /**
     * The `branch_selected` route middleware guarantees a value is present
     * in session by the time any of these methods run — this just resolves
     * it to the actual Branch record.
     */
    private function selectedBranch(): Branch
    {
        return Branch::findOrFail(session('admin_selected_branch_id'));
    }

    private function authorizeSelectedBranchStudent(Student $student): void
    {
        abort_if($student->branch_id !== (int) session('admin_selected_branch_id'), 403, 'This student does not belong to the currently selected branch.');
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
