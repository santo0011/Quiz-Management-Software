<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Mail\ResultRemarkMail;
use App\Models\ExamAttempt;
use App\Services\AcademicSessionResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class ResultController extends Controller
{
    public function index(Request $request): View
    {
        $teacher = $request->user('teacher');
        $selectedSessionId = AcademicSessionResolver::selectedId($request);

        $attempts = $selectedSessionId
            ? ExamAttempt::with(['student', 'exam', 'schoolClass'])
                ->where('branch_id', $teacher->branch_id)
                ->where('session_id', $selectedSessionId)
                ->where('status', 'submitted')
                ->when($request->filled('search'), function ($query) use ($request): void {
                    $search = $request->string('search')->toString();
                    $query->where(function ($query) use ($search): void {
                        $query->whereHas('student', fn ($studentQuery) => $studentQuery
                            ->where('student_name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%"))
                            ->orWhereHas('exam', fn ($examQuery) => $examQuery->where('title', 'like', "%{$search}%"));
                    });
                })
                ->when($request->filled('remark'), function ($query) use ($request): void {
                    $query->when($request->string('remark')->toString() === 'pending',
                        fn ($query) => $query->whereNull('teacher_remark'),
                        fn ($query) => $query->whereNotNull('teacher_remark'));
                })
                ->latest('submitted_at')
                ->paginate(20)
                ->withQueryString()
            : null;

        return view('teacher.results.index', [
            'teacher' => $teacher,
            'selectedSessionId' => $selectedSessionId,
            'attempts' => $attempts,
            'filters' => $request->only(['search', 'remark']),
        ]);
    }

    public function show(Request $request, ExamAttempt $attempt): View
    {
        $this->authorizeTeacherAttempt($request, $attempt);
        $this->authorizeSessionScope($request, $attempt);

        return view('teacher.results.show', [
            'teacher' => $request->user('teacher'),
            'attempt' => $attempt->load(['student', 'exam', 'schoolClass', 'answers.question.options', 'answers.selectedOption', 'teacherRemarkBy']),
        ]);
    }

    public function storeRemark(Request $request, ExamAttempt $attempt): RedirectResponse
    {
        $this->authorizeTeacherAttempt($request, $attempt);
        $this->authorizeSessionScope($request, $attempt);

        $validated = $request->validate([
            'remark' => ['required', 'string', 'max:2000'],
        ], [
            'remark.required' => 'Please enter a remark before saving.',
        ]);

        $attempt->update([
            'teacher_remark' => $validated['remark'],
            'teacher_remark_by' => $request->user('teacher')->id,
            'teacher_remark_at' => now(),
        ]);

        $attempt->load(['student', 'exam', 'schoolClass', 'teacherRemarkBy']);

        $emailResults = $this->sendRemarkEmail($attempt);

        return redirect()->route('teacher.results.show', $attempt)
            ->with('success', 'Remark saved successfully.')
            ->with($this->emailStatusFlash($emailResults));
    }

    /**
     * Sends the result + remark PDF to the Student's email and, only when
     * present and different, the Guardian's email — never to a Guardian
     * email that doesn't exist, and never a duplicate send to the same
     * address twice.
     *
     * @return array<string, bool> recipient email => whether the send succeeded
     */
    private function sendRemarkEmail(ExamAttempt $attempt): array
    {
        $student = $attempt->student;

        if (! $student) {
            return [];
        }

        $recipients = array_unique(array_filter([$student->email, $student->guardian_email]));
        $results = [];

        foreach ($recipients as $recipient) {
            try {
                Mail::to($recipient)->send(new ResultRemarkMail($attempt));
                $results[$recipient] = true;
            } catch (\Throwable $e) {
                report($e);
                $results[$recipient] = false;
            }
        }

        return $results;
    }

    /**
     * Turns the per-recipient send results into a single, clear flash
     * message: which recipients received the result email, and — since the
     * remark itself is already safely saved regardless — a distinct warning
     * naming anyone the email could not reach.
     *
     * @param  array<string, bool>  $emailResults
     * @return array<string, string>
     */
    private function emailStatusFlash(array $emailResults): array
    {
        if ($emailResults === []) {
            return ['warning' => 'No email could be sent: this student has no email on file.'];
        }

        $sent = array_keys(array_filter($emailResults));
        $failed = array_keys(array_filter($emailResults, fn (bool $ok) => ! $ok));

        if ($failed === []) {
            return ['email_status' => 'Result email sent to '.implode(' and ', $sent).'.'];
        }

        if ($sent === []) {
            return ['warning' => 'The result email could not be sent to '.implode(' or ', $failed).'. Please try again.'];
        }

        return ['warning' => 'Result email sent to '.implode(' and ', $sent).', but could not be sent to '.implode(' or ', $failed).'.'];
    }

    private function authorizeTeacherAttempt(Request $request, ExamAttempt $attempt): void
    {
        abort_if($attempt->branch_id !== $request->user('teacher')->branch_id || $attempt->status !== 'submitted', 403, 'This result does not belong to your branch.');
    }

    /**
     * Block reaching a Result that belongs to a different Academic Session
     * than the one currently selected. Legacy rows (either side null) fall
     * through as allowed.
     */
    private function authorizeSessionScope(Request $request, ExamAttempt $attempt): void
    {
        $selectedSessionId = AcademicSessionResolver::selectedId($request);

        abort_if(
            $attempt->session_id !== null && $selectedSessionId !== null && $attempt->session_id !== $selectedSessionId,
            403,
            'This result does not belong to the currently selected academic session.'
        );
    }
}
