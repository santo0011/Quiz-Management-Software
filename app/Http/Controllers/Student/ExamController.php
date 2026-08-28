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
    public function dashboard(Request $request): View
    {
        $student = $request->user('student')->load(['branch', 'schoolClass', 'subjects']);

        $baseExamQuery = Exam::visibleToBranch($student->branch_id)
            ->where('school_class_id', $student->class_id)
            ->where('status', Exam::STATUS_PUBLISHED)
            ->where(fn ($query) => $query->whereNull('subject_id')->orWhereIn('subject_id', $student->subjects->pluck('id')))
            ->where(function ($query) use ($student): void {
                $student->session_id
                    ? $query->whereNull('session_id')->orWhere('session_id', $student->session_id)
                    : $query->whereNull('session_id');
            });

        $publishedExams = (clone $baseExamQuery)
            ->withCount('questions')
            ->with('schoolClass')
            ->latest()
            ->get();

        $availableExams = $publishedExams
            ->filter(fn (Exam $exam) => $exam->dynamicStatus($student) === 'available')
            ->filter(fn (Exam $exam) => $exam->remainingAttemptsFor($student) > 0)
            ->values();

        $upcomingExams = $publishedExams
            ->filter(fn (Exam $exam) => $exam->dynamicStatus($student) === 'upcoming')
            ->filter(fn (Exam $exam) => $exam->remainingAttemptsFor($student) > 0)
            ->values();

        $completedAttempts = ExamAttempt::where('student_id', $student->id)
            ->where('status', 'submitted');

        $allAttempts = (clone $completedAttempts)
            ->with(['exam'])
            ->latest('submitted_at')
            ->get();

        $performanceData = $allAttempts->map(fn ($attempt) => [
            'label' => $attempt->exam?->title ?? 'Exam #'.$attempt->exam_id,
            'percentage' => (float) $attempt->percentage,
            'obtained' => (float) $attempt->obtained_marks,
            'total' => (float) ($attempt->exam?->total_marks ?? 0),
        ])->values();

        $passedCount = (clone $completedAttempts)->where('is_passed', true)->count();
        $failedCount = (clone $completedAttempts)->where('is_passed', false)->count();

        return view('student.dashboard', [
            'student' => $student,
            'availableExams' => $availableExams,
            'upcomingExams' => $upcomingExams,
            'totalExams' => $publishedExams->count(),
            'completedExams' => (clone $completedAttempts)->count(),
            'averageScore' => round((float) (clone $completedAttempts)->avg('percentage'), 2),
            'recentResults' => (clone $completedAttempts)->with('exam')->latest('submitted_at')->take(5)->get(),
            'performanceData' => $performanceData,
            'passedCount' => $passedCount,
            'failedCount' => $failedCount,
        ]);
    }

    public function show(Request $request, Exam $exam): View
    {
        $student = $request->user('student');
        abort_if(! $student->isActive(), 403, 'Your student account has been deactivated.');
        abort_if($student->branch && ! $student->branch->isActive(), 403, 'Your branch has been deactivated.');
        abort_if(($exam->branch_id !== null && $exam->branch_id !== $student->branch_id) || $exam->school_class_id !== $student->class_id || ! $exam->isOpen(), 403);
        abort_if($exam->subject_id !== null && ! $student->subjects()->where('subjects.id', $exam->subject_id)->exists(), 403);
        abort_if($exam->session_id !== null && $exam->session_id !== $student->session_id, 403);

        $hasActiveAttempt = ExamAttempt::where('exam_id', $exam->id)
            ->where('student_id', $student->id)
            ->where('status', 'in_progress')
            ->where('expires_at', '>', now())
            ->exists();

        return view('student.exams.show', [
            'student' => $student->load(['branch', 'schoolClass']),
            'exam' => $exam->loadCount('questions')->load('schoolClass'),
            'remainingAttempts' => $exam->remainingAttemptsFor($student),
            'hasActiveAttempt' => $hasActiveAttempt,
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

    public function available(Request $request): View
    {
        $student = $request->user('student')->load(['branch', 'schoolClass', 'subjects']);

        $exams = Exam::visibleToBranch($student->branch_id)
            ->where('school_class_id', $student->class_id)
            ->where('status', Exam::STATUS_PUBLISHED)
            ->where(fn ($query) => $query->whereNull('subject_id')->orWhereIn('subject_id', $student->subjects->pluck('id')))
            ->where(function ($query) use ($student): void {
                $student->session_id
                    ? $query->whereNull('session_id')->orWhere('session_id', $student->session_id)
                    : $query->whereNull('session_id');
            })
            ->withCount('questions')
            ->with('schoolClass')
            ->where(function ($query): void {
                $query->whereNull('starts_at')->orWhere('starts_at', '<=', now());
            })
            ->where(function ($query): void {
                $query->whereNull('ends_at')->orWhere('ends_at', '>=', now());
            })
            // Exclude exams the student has already submitted
            ->whereDoesntHave('attempts', function ($query) use ($student): void {
                $query->where('student_id', $student->id)
                    ->where('status', 'submitted');
            })
            ->latest()
            ->paginate(12)
            ->withQueryString();

        // Additional safety filter using dynamic status to remove any expired/completed exams
        $exams->getCollection()->transform(function (Exam $exam) use ($student) {
            $exam->setAttribute('dynamic_status', $exam->dynamicStatus($student));

            return $exam;
        });

        return view('student.exams.available', [
            'student' => $student,
            'exams' => $exams,
        ]);
    }

    public function upcoming(Request $request): View
    {
        $student = $request->user('student')->load(['branch', 'schoolClass', 'subjects']);

        $exams = Exam::visibleToBranch($student->branch_id)
            ->where('school_class_id', $student->class_id)
            ->where('status', Exam::STATUS_PUBLISHED)
            ->where(fn ($query) => $query->whereNull('subject_id')->orWhereIn('subject_id', $student->subjects->pluck('id')))
            ->where(function ($query) use ($student): void {
                $student->session_id
                    ? $query->whereNull('session_id')->orWhere('session_id', $student->session_id)
                    : $query->whereNull('session_id');
            })
            ->where('starts_at', '>', now())
            // Exclude exams the student has already submitted
            ->whereDoesntHave('attempts', function ($query) use ($student): void {
                $query->where('student_id', $student->id)
                    ->where('status', 'submitted');
            })
            ->withCount('questions')
            ->with('schoolClass')
            ->latest('starts_at')
            ->paginate(20)
            ->withQueryString();

        // Filter out exams that are no longer upcoming (e.g. already started or completed)
        $exams->getCollection()->transform(function (Exam $exam) use ($student) {
            $status = $exam->dynamicStatus($student);
            $exam->setAttribute('dynamic_status', $status);

            // Only keep exams that are actually still upcoming
            return $status === 'upcoming' ? $exam : null;
        });

        // Remove any null entries (exams that are no longer upcoming)
        $exams->setCollection(
            $exams->getCollection()->filter()
        );

        return view('student.exams.upcoming', [
            'student' => $student,
            'exams' => $exams,
        ]);
    }

    public function mine(Request $request): View
    {
        $student = $request->user('student')->load(['branch', 'schoolClass']);

        $attempts = ExamAttempt::with(['exam', 'schoolClass'])
            ->where('student_id', $student->id)
            ->where('status', 'submitted')
            ->latest('submitted_at')
            ->paginate(20)
            ->withQueryString();

        return view('student.exams.mine', [
            'student' => $student,
            'attempts' => $attempts,
        ]);
    }

    public function results(Request $request): View
    {
        $student = $request->user('student');

        $attempts = $student->attempts()
            ->with(['exam', 'schoolClass'])
            ->where('status', 'submitted')
            ->when($request->filled('search'), function ($query) use ($request): void {
                $search = $request->string('search')->toString();
                $query->whereHas('exam', fn ($examQuery) => $examQuery->where('title', 'like', "%{$search}%"));
            })
            ->when($request->filled('result'), fn ($query) => $query->where('is_passed', $request->string('result')->toString() === 'passed'))
            ->latest('submitted_at')
            ->paginate(20)
            ->withQueryString();

        return view('student.results.index', [
            'student' => $student->load(['branch', 'schoolClass']),
            'attempts' => $attempts,
            'filters' => $request->only(['search', 'result']),
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

    public function profile(Request $request): View
    {
        $student = $request->user('student')->load(['branch', 'schoolClass', 'subjects']);

        return view('student.profile', [
            'student' => $student,
        ]);
    }
}