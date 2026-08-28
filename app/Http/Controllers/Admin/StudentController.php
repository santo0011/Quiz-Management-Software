<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StudentRequest;
use App\Models\Branch;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Subject;
use App\Services\AcademicSessionResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class StudentController extends Controller
{
    public function index(Request $request): View
    {
        $branchId = $request->integer('branch_id') ?: null;
        $selectedSessionId = AcademicSessionResolver::selectedId($request);

        $students = $selectedSessionId
            ? Student::with('branch')
                ->where('session_id', $selectedSessionId)
                ->when($branchId, fn ($query) => $query->forBranch($branchId))
                ->search($request->string('search')->toString())
                ->when($request->filled('class'), fn ($query) => $query->where('class', $request->string('class')->toString()))
                ->latest()
                ->paginate(20)
                ->withQueryString()
            : null;

        return view('admin.students.index', [
            'branches' => Branch::orderBy('name')->get(),
            'selectedBranchId' => $branchId,
            'selectedSessionId' => $selectedSessionId,
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
        $selectedSessionId = AcademicSessionResolver::selectedId($request);
        abort_if(! $selectedSessionId, 403, 'Please select an academic session first.');

        $validated = $request->validated();
        $subjectIds = $validated['subject_ids'] ?? [];
        unset($validated['subject_ids']);
        $branchId = (int) $validated['branch_id'];
        $schoolClass = $this->resolveSchoolClass($validated, $branchId);
        $validated['branch_id'] = $branchId;
        $validated['class_id'] = $schoolClass->id;
        $validated['class'] = $schoolClass->name;
        $validated['session_id'] = $selectedSessionId;

        $student = Student::create($validated);
        $student->subjects()->sync($subjectIds);

        return redirect()->route('admin.students.index')->with('success', 'Student added successfully.');
    }

    public function show(Request $request, Student $student): View
    {
        $this->authorizeSessionScope($request, $student);

        return view('admin.students.show', [
            'student' => $student->load(['branch', 'subjects']),
            'selectedBranch' => $student->branch,
        ]);
    }

    public function edit(Request $request, Student $student): View
    {
        $this->authorizeSessionScope($request, $student);

        return view('admin.students.edit', [
            'student' => $student->load('subjects'),
            'selectedBranch' => $student->branch,
            'classes' => SchoolClass::visibleToBranch($student->branch_id)->orderBy('name')->get(),
            'subjects' => Subject::orderBy('name')->get(),
        ]);
    }

    public function update(StudentRequest $request, Student $student): RedirectResponse
    {
        $this->authorizeSessionScope($request, $student);

        $validated = $request->validated();
        $subjectIds = $validated['subject_ids'] ?? [];
        unset($validated['subject_ids']);
        $branchId = $student->branch_id;
        $schoolClass = $this->resolveSchoolClass($validated, $branchId);
        $validated['branch_id'] = $branchId;
        $validated['class_id'] = $schoolClass->id;
        $validated['class'] = $schoolClass->name;

        $student->update($validated);
        $student->subjects()->sync($subjectIds);

        return redirect()->route('admin.students.index')->with('success', 'Student updated successfully.');
    }

    public function updatePassword(Request $request, Student $student): RedirectResponse
    {
        $validated = $request->validate([
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ], [
            'password.required' => 'Please enter a new password.',
            'password.min' => 'Password must be at least 6 characters.',
            'password.confirmed' => 'Passwords do not match.',
        ]);

        $student->update([
            'password' => Hash::make($validated['password']),
        ]);

        return redirect()->route('admin.students.index')->with('success', 'Student password updated successfully.');
    }

    public function destroy(Request $request, Student $student): RedirectResponse
    {
        $this->authorizeSessionScope($request, $student);

        $hasAttempts = $student->attempts()->exists();

        if ($hasAttempts) {
            return redirect()->route('admin.students.index')
                ->with('error', 'This student has exam history and cannot be permanently deleted. Please deactivate the student instead.');
        }

        $student->delete();

        return redirect()->route('admin.students.index')->with('success', 'Student deleted successfully.');
    }

    public function toggleActive(Request $request, Student $student): RedirectResponse
    {
        $this->authorizeSessionScope($request, $student);

        $student->update(['is_active' => ! $student->is_active]);

        $message = $student->is_active
            ? 'Student activated successfully.'
            : 'Student deactivated successfully.';

        return redirect()->route('admin.students.index')->with('success', $message);
    }

    /**
     * Block reaching a Student that belongs to a different Academic
     * Session than the one currently selected, so switching context
     * (or direct URL/form tampering) can't mix data across sessions.
     * Legacy rows (either side null) fall through as allowed.
     */
    private function authorizeSessionScope(Request $request, Student $student): void
    {
        $selectedSessionId = AcademicSessionResolver::selectedId($request);

        abort_if(
            $student->session_id !== null && $selectedSessionId !== null && $student->session_id !== $selectedSessionId,
            403,
            'This student does not belong to the currently selected academic session.'
        );
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
