<?php

namespace App\Http\Controllers;

use App\Models\ExamAttempt;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ResultPdfController extends Controller
{
    public function __invoke(ExamAttempt $attempt, string $token): Response|StreamedResponse
    {
        $path = $this->resolvePdfPath($attempt, $token);

        abort_if(! $path || ! Storage::disk('public')->exists($path), 404);

        return Storage::disk('public')->response($path, 'Exam-Result-'.$attempt->id.'.pdf', [
            'Content-Type' => 'application/pdf',
        ]);
    }

    private function resolvePdfPath(ExamAttempt $attempt, string $token): ?string
    {
        $branchPath = $attempt->branch_result_pdf_path;
        if ($branchPath && hash_equals(Str::beforeLast(basename($branchPath), '.'), $token)) {
            return $branchPath;
        }

        if ($attempt->result_pdf_path && hash_equals((string) $attempt->result_pdf_token, $token)) {
            return $attempt->result_pdf_path;
        }

        return null;
    }
}
