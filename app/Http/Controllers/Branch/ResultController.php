<?php

namespace App\Http\Controllers\Branch;

use App\Http\Controllers\Controller;
use App\Mail\StudentResultMail;
use App\Models\ExamAttempt;
use App\Services\ZohoResultService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ResultController extends Controller
{
    /**
     * Temporary, explicit product decision: every Branch Panel currently
     * sees submitted results for ALL branches, not just its own — unlike
     * every other Branch module (Students, Exams, Questions, ...), which
     * stay branch-scoped as before.
     */
    public function index(Request $request): View
    {
        $branch = $request->user()->branch;
        abort_if(! $branch, 403, 'Your account is not linked to a branch.');

        $attempts = ExamAttempt::with(['student', 'exam', 'schoolClass', 'branch'])
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
            ->when($request->filled('result'), fn ($query) => $query->where('is_passed', $request->string('result')->toString() === 'passed'))
            ->latest('submitted_at')
            ->paginate(20)
            ->withQueryString();

        return view('branch.results.index', [
            'branch' => $branch,
            'attempts' => $attempts,
            'filters' => $request->only(['search', 'result']),
        ]);
    }

    /**
     * Same temporary "all branches" visibility as index() above — a Branch
     * user can open any branch's submitted result, not only their own.
     */
    public function show(Request $request, ExamAttempt $attempt): View
    {
        abort_if($attempt->status !== 'submitted', 404);

        return view('branch.results.show', [
            'branch' => $request->user()->branch,
            'attempt' => $attempt->load(['student', 'exam.subject', 'schoolClass', 'branch', 'answers.question.options', 'answers.selectedOption', 'resultEmailSentBy', 'branchReviewBy']),
        ]);
    }

    /**
     * The Branch's manual "Review & Send Result" action: takes the result
     * exactly as already calculated and stored by ExamAttemptService::submit()
     * — no marks/percentage/pass-fail value is touched here — saves the
     * Branch's written review/feedback with the result (so it stays
     * available on later views), renders it all into a dedicated
     * professional PDF, emails that PDF to the Student's Zoho/OTP email
     * (never a manually entered or Branch/Super Admin address), and
     * forwards it to Zoho through the existing ZohoResultService/access-token
     * mechanism. Both outcomes are reported back independently since either
     * can fail without the other.
     */
    public function send(Request $request, ExamAttempt $attempt): RedirectResponse
    {
        abort_if($attempt->status !== 'submitted', 404);

        $validated = $request->validate([
            'review' => ['required', 'string', 'max:2000'],
        ], [
            'review.required' => 'Please write a review/feedback about the student\'s performance before sending the result.',
        ]);

        $attempt->load(['student', 'exam.subject', 'schoolClass', 'branch']);
        $student = $attempt->student;

        // Saved immediately — before the OTP-email check and PDF/email/Zoho
        // steps below — so the Branch's written feedback is never lost even
        // if sending fails right now (e.g. the student hasn't logged in via
        // OTP yet), and the just-written review is what actually appears in
        // the PDF once it does go out.
        $attempt->update([
            'branch_review' => $validated['review'],
            'branch_review_by' => $request->user()->id,
            'branch_review_at' => now(),
        ]);

        $otpEmail = $student?->guardian_email;

        if (! filled($otpEmail)) {
            return redirect()->route('branch.results.show', $attempt)
                ->with('error', 'This student has no email on file from their OTP/Zoho verification, so the result could not be sent.');
        }

        try {
            $pdfBytes = Pdf::loadView('pdf.branch-result', ['attempt' => $attempt])->output();
            $path = 'results/branch/'.Str::random(48).'.pdf';
            Storage::disk('public')->put($path, $pdfBytes);
        } catch (\Throwable $e) {
            Log::error('Failed to generate the Branch result PDF.', ['attempt_id' => $attempt->id, 'exception' => $e->getMessage()]);

            return redirect()->route('branch.results.show', $attempt)
                ->with('error', 'Could not generate the result PDF. Please try again.');
        }

        $emailSent = false;

        try {
            Mail::to($otpEmail)->send(new StudentResultMail($attempt, $pdfBytes));
            $emailSent = true;
        } catch (\Throwable $e) {
            Log::error('Failed to email the Branch-reviewed result to the student.', ['attempt_id' => $attempt->id, 'exception' => $e->getMessage()]);
        }

        $zohoSent = app(ZohoResultService::class)->sendResult($attempt, Storage::disk('public')->url($path));

        $updates = ['branch_result_pdf_path' => $path];

        if ($emailSent) {
            $updates['result_email_sent_at'] = now();
            $updates['result_email_sent_by'] = $request->user()->id;
        }

        $attempt->update($updates);

        return redirect()->route('branch.results.show', $attempt)
            ->with($this->sendStatusFlash($emailSent, $zohoSent, $otpEmail));
    }

    /**
     * @return array<string, string>
     */
    private function sendStatusFlash(bool $emailSent, bool $zohoSent, string $otpEmail): array
    {
        if ($emailSent && $zohoSent) {
            return ['success' => "Result sent successfully to {$otpEmail} and submitted to Zoho."];
        }

        if ($emailSent && ! $zohoSent) {
            return ['warning' => "Result emailed to {$otpEmail}, but sending it to Zoho failed. Please try again."];
        }

        if (! $emailSent && $zohoSent) {
            return ['warning' => "Result submitted to Zoho, but the email to {$otpEmail} failed. Please try again."];
        }

        return ['error' => 'Failed to email the result and to submit it to Zoho. Please try again.'];
    }
}
