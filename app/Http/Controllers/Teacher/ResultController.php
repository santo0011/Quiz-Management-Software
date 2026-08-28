<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Mail\ResultRemarkMail;
use App\Models\ExamAttempt;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class ResultController extends Controller
{
    public function index(Request $request): View
    {
        $teacher = $request->user('teacher');

        $attempts = ExamAttempt::with(['student', 'exam', 'schoolClass'])
            ->where('branch_id', $teacher->branch_id)
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
            ->withQueryString();

        return view('teacher.results.index', [
            'teacher' => $teacher,
            'attempts' => $attempts,
            'filters' => $request->only(['search', 'remark']),
        ]);
    }

    public function show(Request $request, ExamAttempt $attempt): View
    {
        $this->authorizeTeacherAttempt($request, $attempt);

        return view('teacher.results.show', [
            'teacher' => $request->user('teacher'),
            'attempt' => $attempt->load(['student', 'exam', 'schoolClass', 'answers.question.options', 'answers.selectedOption', 'teacherRemarkBy']),
        ]);
    }

    public function storeRemark(Request $request, ExamAttempt $attempt): RedirectResponse
    {
        $this->authorizeTeacherAttempt($request, $attempt);

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

        $this->sendRemarkEmail($attempt);

        return redirect()->route('teacher.results.show', $attempt)->with('success', 'Remark saved and the result has been emailed.');
    }

    /**
     * Sends the result + remark PDF to the Student's email and, only when
     * present and different, the Guardian's email — never to a Guardian
     * email that doesn't exist, and never a duplicate send to the same
     * address twice.
     */
    private function sendRemarkEmail(ExamAttempt $attempt): void
    {
        $student = $attempt->student;

        if (! $student) {
            return;
        }

        $recipients = array_unique(array_filter([$student->email, $student->guardian_email]));

        foreach ($recipients as $recipient) {
            try {
                Mail::to($recipient)->send(new ResultRemarkMail($attempt));
            } catch (\Throwable $e) {
                report($e);
            }
        }
    }

    private function authorizeTeacherAttempt(Request $request, ExamAttempt $attempt): void
    {
        abort_if($attempt->branch_id !== $request->user('teacher')->branch_id || $attempt->status !== 'submitted', 403, 'This result does not belong to your branch.');
    }
}
