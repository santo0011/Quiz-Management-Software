<?php

namespace App\Http\Controllers\Guardian;

use App\Http\Controllers\Controller;
use App\Models\ExamAttempt;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StudentController extends Controller
{
    public function show(Request $request, Student $student): View
    {
        $this->authorizeGuardianStudent($request, $student);

        $student->load(['branch', 'schoolClass', 'session', 'subjects']);

        $attempts = $student->attempts()
            ->with('exam')
            ->where('status', 'submitted')
            ->latest('submitted_at')
            ->get();

        return view('guardian.students.show', [
            'guardian' => $request->user('guardian'),
            'student' => $student,
            'attempts' => $attempts,
            'siblingStudents' => $this->siblingStudents($request, $student),
        ]);
    }

    public function result(Request $request, Student $student, ExamAttempt $attempt): View
    {
        $this->authorizeGuardianStudent($request, $student);

        abort_if($attempt->student_id !== $student->id || $attempt->status !== 'submitted', 404);

        return view('guardian.students.result', [
            'guardian' => $request->user('guardian'),
            'student' => $student,
            'attempt' => $attempt->load(['exam', 'schoolClass']),
            'siblingStudents' => $this->siblingStudents($request, $student),
        ]);
    }

    public function resultDetails(Request $request, Student $student, ExamAttempt $attempt): View
    {
        $this->authorizeGuardianStudent($request, $student);

        abort_if($attempt->student_id !== $student->id || $attempt->status !== 'submitted', 404);

        return view('guardian.students.result-details', [
            'guardian' => $request->user('guardian'),
            'student' => $student,
            'attempt' => $attempt->load(['exam', 'schoolClass', 'answers.question.options', 'answers.selectedOption']),
            'siblingStudents' => $this->siblingStudents($request, $student),
        ]);
    }

    /**
     * Every Student linked to this Guardian's email, for the "switch
     * between Students" strip shown on their profile/result pages.
     */
    private function siblingStudents(Request $request, Student $student): \Illuminate\Support\Collection
    {
        return Student::where('guardian_email', $request->user('guardian')->email)
            ->orderBy('student_name')
            ->get();
    }

    /**
     * The one place that matters for "Guardian can ONLY see Students linked
     * to their verified Guardian Email" — every Guardian-facing route that
     * takes a {student} must call this before touching that Student's data.
     */
    private function authorizeGuardianStudent(Request $request, Student $student): void
    {
        $guardianEmail = $request->user('guardian')->email;

        abort_if(
            $student->guardian_email === null || $student->guardian_email !== $guardianEmail,
            403,
            'This student is not linked to your guardian account.'
        );
    }
}
