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

        $baseExamQuery = Exam::eligibleForStudent($student)
            ->where('status', Exam::STATUS_PUBLISHED);

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

        // Scores only count once the student has finished all attempts of
        // that exam (its results are released).
        $summaries = Exam::attemptSummaries($student, $allAttempts->pluck('exam'));
        $releasedAttempts = $allAttempts
            ->filter(fn (ExamAttempt $attempt) => $summaries[$attempt->exam_id]['released'] ?? false)
            ->values();

        $performanceData = $releasedAttempts->map(fn ($attempt) => [
            'label' => ($attempt->exam?->title ?? 'Exam #'.$attempt->exam_id)
                .(($attempt->exam?->maximum_attempts ?? 1) > 1 ? ' (Attempt '.$attempt->attempt_number.')' : ''),
            'percentage' => (float) $attempt->percentage,
            'obtained' => (float) $attempt->obtained_marks,
            'total' => (float) ($attempt->exam?->total_marks ?? 0),
        ])->values();

        return view('student.dashboard', [
            'student' => $student,
            'availableExams' => $availableExams,
            'upcomingExams' => $upcomingExams,
            'totalExams' => $publishedExams->count(),
            'completedExams' => $allAttempts->count(),
            'averageScore' => round((float) $releasedAttempts->avg('percentage'), 2),
            'recentResults' => $releasedAttempts->take(5),
            'performanceData' => $performanceData,
        ]);
    }

    public function show(Request $request, Exam $exam): View
    {
        $student = $request->user('student');
        abort_if(! $student->isActive(), 403, 'Your student account has been deactivated.');
        abort_if($student->branch && ! $student->branch->isActive(), 403, 'Your branch has been deactivated.');
        abort_unless(Exam::eligibleForStudent($student)->whereKey($exam->id)->exists(), 403);
        abort_if(! $exam->isOpen(), 403);

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

        $exams = Exam::availableForStudent($student)
            ->withCount('questions')
            ->with('schoolClass')
            // Exclude exams whose Attempt Limit the student has used up (a
            // limit of 1 hides it after the first submission, as before).
            ->whereRaw(
                '(select count(*) from exam_attempts where exam_attempts.exam_id = exams.id and exam_attempts.student_id = ? and exam_attempts.status = ?) < exams.maximum_attempts',
                [$student->id, 'submitted']
            )
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

        $exams = Exam::eligibleForStudent($student)
            ->where('status', Exam::STATUS_PUBLISHED)
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
            'summaries' => Exam::attemptSummaries($student, $attempts->getCollection()->pluck('exam')),
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
            ->latest('submitted_at')
            ->paginate(20)
            ->withQueryString();

        return view('student.results.index', [
            'student' => $student->load(['branch', 'schoolClass']),
            'attempts' => $attempts,
            'summaries' => Exam::attemptSummaries($student, $attempts->getCollection()->pluck('exam')),
            'filters' => $request->only(['search']),
        ]);
    }

    public function result(Request $request, ExamAttempt $attempt): View
    {
        $student = $request->user('student');
        abort_if($attempt->student_id !== $student->id || $attempt->status !== 'submitted', 403);

        $attempt->load(['exam', 'schoolClass']);
        $summary = Exam::attemptSummaries($student, collect([$attempt->exam]))[$attempt->exam_id];

        // Results stay hidden until every allowed attempt is completed.
        if (! $summary['released']) {
            return view('student.results.pending', [
                'student' => $student->load(['branch', 'schoolClass']),
                'attempt' => $attempt,
                'summary' => $summary,
                'canTakeNextAttempt' => $attempt->exam->isOpen() && $summary['remaining'] > 0,
            ]);
        }

        return view('student.results.show', [
            'student' => $student->load(['branch', 'schoolClass']),
            'attempt' => $attempt->load(['answers.question.options', 'answers.selectedOption']),
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