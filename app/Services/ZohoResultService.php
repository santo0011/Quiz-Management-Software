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
    private ?string $lastFailureMessage = null;

    public function __construct(private ZohoAuthService $auth) {}

    public function lastFailureMessage(): ?string
    {
        return $this->lastFailureMessage;
    }

    public function sendResult(ExamAttempt $attempt, string $pdfUrl): bool
    {
        $this->lastFailureMessage = null;
        $student = $attempt->student;

        if (! $student || ! $student->zoho_student_id) {
            $this->lastFailureMessage = 'Student has no NRICH Student ID.';
            Log::info('Skipped sending result to Zoho: student has no NRICH Student ID.', ['attempt_id' => $attempt->id]);

            return false;
        }

        if (! $student->zoho_class_id || ! $student->zoho_class_name) {
            $this->lastFailureMessage = 'Student has no Zoho class on file.';
            Log::warning('Skipped sending result to Zoho: no Zoho class on file for student.', [
                'attempt_id' => $attempt->id,
                'student_id' => $student->id,
            ]);

            return false;
        }

        if (! $this->isPublicHttpsUrl($pdfUrl)) {
            $this->lastFailureMessage = 'Zoho needs a verified public HTTPS PDF URL. Set APP_URL or ZOHO_RESULT_PDF_BASE_URL to your live HTTPS website URL.';

            Log::warning('Skipped sending result to Zoho: result PDF URL is not public.', [
                'attempt_id' => $attempt->id,
                'pdf_url' => $pdfUrl,
            ]);

            return false;
        }

        $payload = [
            'Student_NRICH_ID' => $student->zoho_student_id,
            'Email' => $student->guardian_email ?: $student->email,
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
            $this->lastFailureMessage = 'Could not obtain Zoho access token.';
            Log::error('Could not send result to Zoho: failed to obtain access token.', ['attempt_id' => $attempt->id]);

            return false;
        }

        $url = rtrim(config('services.zoho.api_url'), '/')
            .'/crm/v7/functions/'.config('services.zoho.receive_results_function').'/actions/execute?auth_type=oauth';

        // Full request trace (the access token is a header, never part of
        // $payload, so nothing here needs masking) — logged unconditionally
        // so a rejected/failed submission can be diagnosed from the log
        // alone, without having to reproduce it live against Zoho again.
        Log::debug('Zoho result submission request.', [
            'attempt_id' => $attempt->id,
            'url' => $url,
            'payload' => $payload,
        ]);

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

            $this->lastFailureMessage = $e->getMessage();

            return false;
        }

        $data = $this->extractBusinessPayload($response->json() ?? []);
        $success = $response->successful() && $this->isTruthy($data['success'] ?? null);

        Log::debug('Zoho result submission response.', [
            'attempt_id' => $attempt->id,
            'http_status' => $response->status(),
            'response_body' => $data,
            'success_field' => $data['success'] ?? null,
            'error_field' => $data['error'] ?? null,
        ]);

        if (! $success) {
            $this->lastFailureMessage = $this->failureMessageFromResponse($data);

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

    private function failureMessageFromResponse(array $data): string
    {
        $message = $data['message'] ?? $data['error']['message'] ?? null;

        if (isset($data['detailed_Response']) && is_array($data['detailed_Response'])) {
            $detail = $data['detailed_Response'];
            $field = $detail['details']['api_name'] ?? null;
            $expected = $detail['details']['expected_data_type'] ?? null;
            $detailMessage = $detail['message'] ?? null;

            if ($field && $detailMessage) {
                return trim($field.': '.$detailMessage.($expected ? " (expected {$expected})." : '.'));
            }
        }

        if (is_string($message) && $message !== '') {
            return $message;
        }

        return 'Zoho rejected the result submission.';
    }

    private function isPublicHttpsUrl(string $url): bool
    {
        $parts = parse_url($url);
        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $host = strtolower((string) ($parts['host'] ?? ''));

        if ($scheme !== 'https' || $host === '') {
            return false;
        }

        if (in_array($host, ['localhost', '127.0.0.1', '::1'], true) || str_ends_with($host, '.local')) {
            return false;
        }

        if (filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false
            && filter_var($host, FILTER_VALIDATE_IP) !== false) {
            return false;
        }

        return true;
    }
}
