<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\ExamAttempt;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $student = auth('student')->user()->load(['branch', 'schoolClass']);
        $baseExamQuery = Exam::where('branch_id', $student->branch_id)
            ->where('school_class_id', $student->class_id)
            ->where('status', Exam::STATUS_PUBLISHED);

        $availableExams = (clone $baseExamQuery)
            ->withCount('questions')
            ->with('schoolClass')
            ->where(function ($query): void {
                $query->whereNull('starts_at')->orWhere('starts_at', '<=', now());
            })
            ->where(function ($query): void {
                $query->whereNull('ends_at')->orWhere('ends_at', '>=', now());
            })
            ->latest()
            ->get()
            ->filter(fn (Exam $exam) => $exam->remainingAttemptsFor($student) > 0)
            ->values();

        $completedAttempts = ExamAttempt::where('student_id', $student->id)
            ->where('status', 'submitted');

        return view('student.dashboard', [
            'student' => $student,
            'availableExams' => $availableExams,
            'upcomingExams' => (clone $baseExamQuery)->where('starts_at', '>', now())->count(),
            'totalExams' => (clone $baseExamQuery)->count(),
            'completedExams' => (clone $completedAttempts)->count(),
            'averageScore' => round((float) (clone $completedAttempts)->avg('percentage'), 2),
            'passedExams' => (clone $completedAttempts)->where('is_passed', true)->count(),
            'failedExams' => (clone $completedAttempts)->where('is_passed', false)->count(),
            'recentResults' => (clone $completedAttempts)->with('exam')->latest('submitted_at')->take(5)->get(),
        ]);
    }
}
