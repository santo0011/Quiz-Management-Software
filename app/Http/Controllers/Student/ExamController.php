<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Services\ExamAttemptService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ExamController extends Controller
{
    public function show(Request $request, Exam $exam): View
    {
        $student = $request->user('student');
        abort_if($exam->branch_id !== $student->branch_id || $exam->school_class_id !== $student->class_id || ! $exam->isOpen(), 403);

        return view('student.exams.show', [
            'student' => $student->load(['branch', 'schoolClass']),
            'exam' => $exam->loadCount('questions')->load('schoolClass'),
            'remainingAttempts' => $exam->remainingAttemptsFor($student),
        ]);
    }

    public function start(Request $request, Exam $exam, ExamAttemptService $service): RedirectResponse
    {
        $attempt = $service->start($exam->load('questions'), $request->user('student'));

        return redirect()->route('student.attempts.show', $attempt);
    }

    public function attempt(Request $request, ExamAttempt $attempt): View
    {
        abort_if($attempt->student_id !== $request->user('student')->id, 403);

        return view('student.exams.attempt', [
            'attempt' => $attempt->load('exam'),
        ]);
    }

    public function results(Request $request): View
    {
        $student = $request->user('student');

        return view('student.results.index', [
            'student' => $student->load(['branch', 'schoolClass']),
            'attempts' => $student->attempts()
                ->with(['exam', 'schoolClass'])
                ->where('status', 'submitted')
                ->latest('submitted_at')
                ->paginate(10),
        ]);
    }

    public function result(Request $request, ExamAttempt $attempt): View
    {
        abort_if($attempt->student_id !== $request->user('student')->id || $attempt->status !== 'submitted', 403);

        return view('student.results.show', [
            'student' => $request->user('student')->load(['branch', 'schoolClass']),
            'attempt' => $attempt->load(['exam', 'schoolClass', 'answers.question.options', 'answers.selectedOption']),
        ]);
    }
}
