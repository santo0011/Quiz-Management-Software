<?php

namespace App\Http\Controllers\Branch;

use App\Http\Controllers\Controller;
use App\Models\ExamAttempt;
use App\Services\ResultPdfUrlVerifier;
use App\Services\ZohoResultService;
use App\Support\ResultPdfUrl;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use RuntimeException;

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
     * professional PDF, verifies the public HTTPS PDF URL, and forwards that
     * URL to Zoho through the existing ZohoResultService/access-token
     * mechanism. Zoho is responsible for processing the result notification.
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
            [, $path, $pdfUrl] = $this->generateBranchResultPdf($attempt);
        } catch (\Throwable $e) {
            Log::error('Failed to generate the Branch result PDF.', [
                'attempt_id' => $attempt->id,
                'exception' => $e->getMessage(),
                'class' => $e::class,
                'file' => $e->getFile().':'.$e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            $detail = config('app.debug') ? ' ['.$e::class.': '.$e->getMessage().']' : '';

            return redirect()->route('branch.results.show', $attempt)
                ->with('error', 'Could not generate the result PDF. Please try again.'.$detail);
        }

        $attempt->update(['branch_result_pdf_path' => $path]);

        Log::info('Generated Branch result PDF public URL.', [
            'attempt_id' => $attempt->id,
            ...ResultPdfUrl::diagnosticsForPath($path, $pdfUrl),
        ]);

        $verifier = app(ResultPdfUrlVerifier::class);

        if (! $verifier->verify($pdfUrl)) {
            Log::warning('Result PDF public URL failed verification before sending to Zoho.', [
                'attempt_id' => $attempt->id,
                'result_pdf_url' => $pdfUrl,
                'reason' => $verifier->lastFailureMessage(),
            ]);

            return redirect()->route('branch.results.show', $attempt)
                ->with('error', 'Result PDF was generated, but its public URL is not ready for Zoho. '.$verifier->lastFailureMessage());
        }

        $zohoService = app(ZohoResultService::class);
        $zohoSent = $zohoService->sendResult($attempt, $pdfUrl);
        $zohoFailureMessage = $zohoService->lastFailureMessage();

        return redirect()->route('branch.results.show', $attempt)
            ->with($this->sendStatusFlash($zohoSent, $otpEmail, $zohoFailureMessage));
    }

    /**
     * @return array{0: string, 1: string, 2: string}
     */
    private function generateBranchResultPdf(ExamAttempt $attempt): array
    {
        $pdfBytes = Pdf::loadView('pdf.branch-result', ['attempt' => $attempt])->output();

        if (! str_starts_with($pdfBytes, '%PDF')) {
            throw new RuntimeException('Dompdf did not return valid PDF bytes.');
        }

        $token = Str::random(48);
        $path = "results/branch/{$token}.pdf";
        $disk = Storage::disk('public');

        $disk->makeDirectory('results/branch');

        if ($disk->put($path, $pdfBytes) !== true) {
            throw new RuntimeException("Could not write Branch result PDF to {$path}.");
        }

        if (! $disk->exists($path)) {
            throw new RuntimeException("Branch result PDF was not found after writing to {$path}.");
        }

        return [$pdfBytes, $path, ResultPdfUrl::make($attempt, $token)];
    }

    /**
     * @return array<string, string>
     */
    private function sendStatusFlash(bool $zohoSent, string $otpEmail, ?string $zohoFailureMessage = null): array
    {
        if ($zohoSent) {
            return ['success' => "Result submitted successfully to Zoho for {$otpEmail}."];
        }

        $reason = $zohoFailureMessage ? " Zoho response: {$zohoFailureMessage}" : '';

        return ['error' => "Sending the result to Zoho failed.{$reason} Please try again."];
    }
}
