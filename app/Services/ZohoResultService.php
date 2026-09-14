<?php

namespace App\Services;

use App\Exceptions\ZohoApiException;
use App\Models\ExamAttempt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Sends a completed exam result to Zoho CRM API 3
 * (receive_results_data_from_portal). Every failure path here is logged and
 * swallowed — a Zoho outage must never block a student from seeing their
 * locally generated result.
 */
class ZohoResultService
{
    public function __construct(private ZohoAuthService $auth)
    {
    }

    public function sendResult(ExamAttempt $attempt, string $pdfUrl): bool
    {
        $student = $attempt->student;

        if (! $student || ! $student->zoho_student_id) {
            Log::info('Skipped sending result to Zoho: student has no NRICH Student ID.', ['attempt_id' => $attempt->id]);

            return false;
        }

        if (! $student->zoho_class_id || ! $student->zoho_class_name) {
            Log::warning('Skipped sending result to Zoho: no Zoho class on file for student.', [
                'attempt_id' => $attempt->id,
                'student_id' => $student->id,
            ]);

            return false;
        }

        $payload = [
            'Student_NRICH_ID' => $student->zoho_student_id,
            'Student_Class' => [
                'id' => $student->zoho_class_id,
                'name' => $student->zoho_class_name,
            ],
            'Grade' => $student->zoho_grade ?? $student->schoolClass?->name,
            'Result_PDF_URL' => $pdfUrl,
        ];

        try {
            $token = $this->auth->getAccessToken();
        } catch (ZohoApiException) {
            Log::error('Could not send result to Zoho: failed to obtain access token.', ['attempt_id' => $attempt->id]);

            return false;
        }

        $url = rtrim(config('services.zoho.api_url'), '/')
            .'/crm/v7/functions/'.config('services.zoho.receive_results_function').'/actions/execute?auth_type=oauth';

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Zoho-oauthtoken '.$token,
                'Content-Type' => 'application/json',
            ])->post($url, $payload);
        } catch (\Throwable $e) {
            Log::error('Zoho result submission request failed.', [
                'attempt_id' => $attempt->id,
                'exception' => $e->getMessage(),
            ]);

            return false;
        }

        $data = $this->extractBusinessPayload($response->json() ?? []);
        $success = $response->successful() && $this->isTruthy($data['success'] ?? null);

        if (! $success) {
            Log::error('Zoho rejected the result submission.', [
                'attempt_id' => $attempt->id,
                'http_status' => $response->status(),
                'response' => $data,
            ]);

            return false;
        }

        $attempt->update(['zoho_result_synced_at' => now()]);

        return true;
    }

    /**
     * Zoho's `success` flag has an unknown real-world shape (a Deluge
     * function may return a genuine boolean, or the string "true"/"1") — be
     * tolerant of all of them rather than only accepting `=== true`.
     */
    private function isTruthy(mixed $value): bool
    {
        return in_array($value, [true, 1, '1', 'true'], true);
    }

    /**
     * See ZohoStudentService::extractBusinessPayload() — Zoho's REST wrapper
     * around a custom function sometimes nests the actual return value
     * under `details.output` as a JSON string rather than at the top level.
     */
    private function extractBusinessPayload(array $body): array
    {
        if (isset($body['details']['output']) && is_string($body['details']['output'])) {
            $decoded = json_decode($body['details']['output'], true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        if (isset($body['details']) && is_array($body['details']) && array_key_exists('success', $body['details'])) {
            return $body['details'];
        }

        return $body;
    }
}
