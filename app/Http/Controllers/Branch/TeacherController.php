<?php

namespace App\Http\Controllers\Branch;

use App\Http\Controllers\Controller;
use App\Http\Requests\TeacherRequest;
use App\Mail\TeacherCredentialsMail;
use App\Models\Teacher;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class TeacherController extends Controller
{
    public function index(Request $request): View
    {
        $branch = $request->user()->branch;
        abort_if(! $branch, 403, 'Your account is not linked to a branch.');

        return view('branch.teachers.index', [
            'branch' => $branch,
            'teachers' => Teacher::where('branch_id', $branch->id)->latest()->paginate(20),
        ]);
    }

    public function store(TeacherRequest $request): RedirectResponse
    {
        $branch = $request->user()->branch;
        abort_if(! $branch, 403, 'Your account is not linked to a branch.');

        $temporaryPassword = (string) random_int(100000, 999999);

        $teacher = Teacher::create([
            ...$request->validated(),
            'branch_id' => $branch->id,
            'password' => Hash::make($temporaryPassword),
        ]);

        try {
            Mail::to($teacher->email)->send(new TeacherCredentialsMail($teacher, $temporaryPassword));
        } catch (\Throwable $e) {
            return redirect()->route('branch.teachers.index')->with('error', 'Teacher created, but login credentials could not be sent to the email.');
        }

        return redirect()->route('branch.teachers.index')->with('success', 'Teacher login credentials have been sent to the registered email.');
    }

    public function update(TeacherRequest $request, Teacher $teacher): RedirectResponse
    {
        $teacher->update($request->validated());

        return redirect()->route('branch.teachers.index')->with('success', 'Teacher updated successfully.');
    }

    public function destroy(Request $request, Teacher $teacher): RedirectResponse
    {
        $this->authorizeBranchTeacher($request, $teacher);

        $teacher->delete();

        return redirect()->route('branch.teachers.index')->with('success', 'Teacher deleted successfully.');
    }

    public function updatePassword(Request $request, Teacher $teacher): RedirectResponse
    {
        $this->authorizeBranchTeacher($request, $teacher);

        $validated = $request->validate([
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ], [
            'password.required' => 'Please enter a new password.',
            'password.min' => 'Password must be at least 6 characters.',
            'password.confirmed' => 'Password confirmation does not match.',
        ]);

        $teacher->update([
            'password' => Hash::make($validated['password']),
        ]);

        return redirect()->route('branch.teachers.index')->with('success', 'Teacher password updated successfully.');
    }

    private function authorizeBranchTeacher(Request $request, Teacher $teacher): void
    {
        abort_if($teacher->branch_id !== $request->user()->branch_id, 403, 'This teacher does not belong to your branch.');
    }
}
