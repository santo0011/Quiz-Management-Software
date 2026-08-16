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

        $publishedExams = (clone $baseExamQuery)
            ->withCount('questions')
            ->with('schoolClass')
            ->latest()
            ->get();

        // Categorize exams by time window
        $availableExams = $publishedExams
            ->filter(fn (Exam $exam) => $exam->isOpen())
            ->filter(fn (Exam $exam) => $exam->remainingAttemptsFor($student) > 0)
            ->values();

        $upcomingExams = $publishedExams
            ->filter(fn (Exam $exam) => ! $exam->isOpen() && $exam->starts_at && $exam->starts_at->isFuture())
            ->filter(fn (Exam $exam) => $exam->remainingAttemptsFor($student) > 0)
            ->values();

        $expiredExams = $publishedExams
            ->filter(fn (Exam $exam) => $exam->ends_at && $exam->ends_at->isPast())
            ->values();

        $completedAttempts = ExamAttempt::where('student_id', $student->id)
            ->where('status', 'submitted');

        $allAttempts = (clone $completedAttempts)
            ->with(['exam'])
            ->latest('submitted_at')
            ->get();

        // Chart data: Marks/Percentage by Exam
        $performanceData = $allAttempts->map(fn ($attempt) => [
            'label' => $attempt->exam?->title ?? 'Exam #'.$attempt->exam_id,
            'percentage' => (float) $attempt->percentage,
            'obtained' => (float) $attempt->obtained_marks,
            'total' => (float) ($attempt->exam?->total_marks ?? 0),
        ])->values();

        // Chart data: Passed vs Failed
        $passedCount = (clone $completedAttempts)->where('is_passed', true)->count();
        $failedCount = (clone $completedAttempts)->where('is_passed', false)->count();

        return view('student.dashboard', [
            'student' => $student,
            'availableExams' => $availableExams,
            'upcomingExams' => $upcomingExams,
            'expiredExams' => $expiredExams,
            'totalExams' => $publishedExams->count(),
            'completedExams' => (clone $completedAttempts)->count(),
            'averageScore' => round((float) (clone $completedAttempts)->avg('percentage'), 2),
            'passedExams' => $passedCount,
            'failedExams' => $failedCount,
            'recentResults' => (clone $completedAttempts)->with('exam')->latest('submitted_at')->take(5)->get(),
            'performanceData' => $performanceData,
            'passedCount' => $passedCount,
            'failedCount' => $failedCount,
        ]);
    }
}