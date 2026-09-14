<?php

namespace App\Services;

use App\Exceptions\ZohoApiException;
use App\Models\Student;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Student identification/OTP verification against Zoho CRM API 2
 * (lms_portal_endpoint_1), used by the Student login flow instead of a
 * local email+password check.
 */
class ZohoStudentService
{
    /**
     * Substring-matched against the lowercased first error message Zoho
     * returns, mapped to a friendly message for the login screen.
     */
    private const KNOWN_ERRORS = [
        'student not found' => 'No student account found with this Student ID.',
        'parent linking not found' => 'This student is not linked to a parent record. Please contact your administrator.',
        'active enrolment not found' => 'No active enrolment was found for this student. Please contact your administrator.',
        'parent email not found' => 'No parent email is on file for this student. Please contact your administrator.',
        'otp email not sent' => 'We could not send the verification code email. Please try again shortly.',
        'otp expired' => 'This code has expired. Please request a new one.',
        'expired otp' => 'This code has expired. Please request a new one.',
        'code expired' => 'This code has expired. Please request a new one.',
        'verification code has expired' => 'This code has expired. Please request a new one.',
        'invalid otp' => 'The verification code you entered is incorrect. Please try again.',
        'incorrect otp' => 'The verification code you entered is incorrect. Please try again.',
        'invalid verification code' => 'The verification code you entered is incorrect. Please try again.',
        'invalid code' => 'The verification code you entered is incorrect. Please try again.',
    ];

    private const GENERIC_ERROR = 'We could not verify your Student ID right now. Please try again later.';

    public function __construct(private ZohoAuthService $auth)
    {
    }

    /**
     * Identify a student by NRICH ID and (optionally) trigger Zoho's OTP
     * email. Used both for the initial login attempt (send_otp: true) and
     * for the "resend code" action.
     */
    public function identify(string $nrichStudentId, bool $sendOtp): array
    {
        return $this->call([
            'nrich_student_id' => $nrichStudentId,
            'send_otp' => $sendOtp ? 'true' : 'false',
            'otp_validity' => config('services.zoho.otp_validity_minutes'),
            'verification_code' => '',
            // Reserved for the Teacher Override feature (defined later) —
            // not wired to anything yet, always sent as false.
            'login_through_teacher_master_code' => 'false',
        ]);
    }

    /**
     * Verify the code the student entered.
     */
    public function verifyOtp(string $nrichStudentId, string $code): array
    {
        return $this->call([
            'nrich_student_id' => $nrichStudentId,
            'send_otp' => 'false',
            'otp_validity' => config('services.zoho.otp_validity_minutes'),
            'verification_code' => $code,
            'login_through_teacher_master_code' => 'false',
        ]);
    }

    /**
     * Persist the Student/Parent/Enrolment data Zoho returned onto the local
     * Student record, so ZohoResultService can later read the active class
     * without calling Zoho again.
     */
    public function syncStudentFromZoho(Student $student, array $data): void
    {
        $class = $this->extractActiveClass($data);

        $student->forceFill([
            'zoho_payload' => $data,
            'zoho_synced_at' => now(),
            'zoho_class_id' => $class['id'],
            'zoho_class_name' => $class['name'],
            'zoho_grade' => $this->extractGrade($data),
        ])->save();
    }

    private function call(array $payload): array
    {
        try {
            $token = $this->auth->getAccessToken();
        } catch (ZohoApiException) {
            return ['ok' => false, 'message' => self::GENERIC_ERROR, 'data' => [], 'otp_validity' => config('services.zoho.otp_validity_minutes')];
        }

        $url = rtrim(config('services.zoho.api_url'), '/')
            .'/crm/v7/functions/'.config('services.zoho.student_login_function').'/actions/execute';

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Zoho-oauthtoken '.$token,
                'Content-Type' => 'application/json',
            ])->post($url.'?auth_type=oauth', $payload);
        } catch (\Throwable $e) {
            Log::error('Zoho student verification request failed.', [
                'nrich_student_id' => $payload['nrich_student_id'] ?? null,
                'exception' => $e->getMessage(),
            ]);

            return ['ok' => false, 'message' => self::GENERIC_ERROR, 'data' => [], 'otp_validity' => config('services.zoho.otp_validity_minutes')];
        }

        $data = $this->extractBusinessPayload($response->json() ?? []);
        $errors = $this->normalizeErrors($data['error'] ?? $data['errors'] ?? []);
        $status = $data['status'] ?? $data['Status'] ?? null;
        $normalizedStatus = is_string($status) ? strtolower(trim($status)) : $status;

        // The spec is explicit that the `error` array is the source of truth
        // ("do not allow login if the error array contains an error") — a
        // `status` field is a secondary signal. Only treat `status` as a
        // failure indicator when it explicitly says so; don't require it to
        // literally equal "success", since a response with an empty `error`
        // array and no `status` field at all should still count as OK.
        $statusIndicatesFailure = in_array($normalizedStatus, ['error', 'failed', 'failure', false, 0, '0'], true);

        if (! $response->successful() || $statusIndicatesFailure || ! empty($errors)) {
            Log::error('Zoho student verification returned an error.', [
                'nrich_student_id' => $payload['nrich_student_id'] ?? null,
                'http_status' => $response->status(),
                'zoho_status' => $normalizedStatus,
                'errors' => $errors,
            ]);

            return [
                'ok' => false,
                'message' => $errors ? $this->mapErrorMessage($errors) : self::GENERIC_ERROR,
                'data' => $data,
                'otp_validity' => (int) ($data['otp_validity'] ?? config('services.zoho.otp_validity_minutes')),
            ];
        }

        return [
            'ok' => true,
            'message' => null,
            'data' => $data,
            'otp_validity' => (int) ($data['otp_validity'] ?? config('services.zoho.otp_validity_minutes')),
        ];
    }

    /**
     * Zoho's REST wrapper around a custom function often nests the
     * function's own return value as a JSON string under
     * `details.output` rather than at the top level. Support both shapes so
     * this doesn't silently misparse once we see the real response.
     */
    private function extractBusinessPayload(array $body): array
    {
        if (isset($body['details']['output']) && is_string($body['details']['output'])) {
            $decoded = json_decode($body['details']['output'], true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        if (isset($body['details']) && is_array($body['details']) && (array_key_exists('status', $body['details']) || array_key_exists('error', $body['details']))) {
            return $body['details'];
        }

        return $body;
    }

    private function normalizeErrors(mixed $errors): array
    {
        if (empty($errors)) {
            return [];
        }

        if (is_array($errors)) {
            // A list (`["Student not found"]`) is already what we want; a
            // single associative error object (`{"message": "..."}`) needs
            // wrapping so `$errors[0]` below reaches it correctly instead of
            // silently missing a numeric index.
            return array_is_list($errors) ? $errors : [$errors];
        }

        return [$errors];
    }

    /**
     * Confirmed against a real Zoho response: a "not found" error is NOT a
     * human-readable string — it's a structured object like
     * `{"student_record": "not_found", "search_parameter": "NL..."}`. There
     * is no free-text `message` key to match against at all. Flatten the
     * whole object to a lowercase string and pattern-match on it, so this
     * still works whether Zoho sends `student_record`, `parent_link`,
     * `enrolment`, etc. — none of which are free text, and only one of
     * which (student_record: not_found) has actually been observed live.
     */
    private function mapErrorMessage(array $errors): string
    {
        $first = $errors[0] ?? '';

        if (! is_array($first)) {
            $normalized = strtolower(trim((string) $first));

            foreach (self::KNOWN_ERRORS as $needle => $message) {
                if (str_contains($normalized, $needle)) {
                    return $message;
                }
            }

            return self::GENERIC_ERROR;
        }

        // A free-text `message` field, if Zoho ever includes one alongside
        // the structured keys, is still honored first.
        if (isset($first['message']) && is_string($first['message'])) {
            $normalized = strtolower(trim($first['message']));

            foreach (self::KNOWN_ERRORS as $needle => $message) {
                if (str_contains($normalized, $needle)) {
                    return $message;
                }
            }
        }

        $haystack = strtolower(json_encode($first) ?: '');

        return match (true) {
            str_contains($haystack, 'student') && str_contains($haystack, 'not_found') => 'No student account found with this Student ID.',
            str_contains($haystack, 'parent') && str_contains($haystack, 'link') && str_contains($haystack, 'not_found') => 'This student is not linked to a parent record. Please contact your administrator.',
            str_contains($haystack, 'enrol') && str_contains($haystack, 'not_found') => 'No active enrolment was found for this student. Please contact your administrator.',
            str_contains($haystack, 'parent') && str_contains($haystack, 'email') && str_contains($haystack, 'not_found') => 'No parent email is on file for this student. Please contact your administrator.',
            str_contains($haystack, 'otp') && (str_contains($haystack, 'not_sent') || str_contains($haystack, 'fail')) => 'We could not send the verification code email. Please try again shortly.',
            str_contains($haystack, 'otp') && str_contains($haystack, 'expired') => 'This code has expired. Please request a new one.',
            str_contains($haystack, 'otp') && (str_contains($haystack, 'invalid') || str_contains($haystack, 'incorrect') || str_contains($haystack, 'mismatch')) => 'The verification code you entered is incorrect. Please try again.',
            default => self::GENERIC_ERROR,
        };
    }

    private function extractActiveClass(array $data): array
    {
        $enrolment = $data['Enrolment'] ?? $data['enrolment'] ?? [];
        $classes = $enrolment['classes'] ?? $enrolment['Classes'] ?? [];

        if (isset($classes['id'])) {
            return ['id' => (string) $classes['id'], 'name' => (string) ($classes['name'] ?? '')];
        }

        if (is_array($classes)) {
            foreach ($classes as $class) {
                if (! is_array($class)) {
                    continue;
                }

                $active = $class['active'] ?? $class['is_active'] ?? $class['Active'] ?? null;
                if (in_array($active, [true, 1, '1', 'true'], true)) {
                    return ['id' => (string) ($class['id'] ?? ''), 'name' => (string) ($class['name'] ?? '')];
                }
            }

            $first = $classes[0] ?? null;
            if (is_array($first)) {
                return ['id' => (string) ($first['id'] ?? ''), 'name' => (string) ($first['name'] ?? '')];
            }
        }

        return ['id' => null, 'name' => null];
    }

    private function extractGrade(array $data): ?string
    {
        return $data['Grade']
            ?? $data['Student']['Grade']
            ?? $data['Enrolment']['Grade']
            ?? null;
    }

    /**
     * The OTP is emailed to the parent, not the student, so the login/OTP
     * screens should reference that address (masked) rather than the NRICH
     * ID when telling the student where the code went. Confirmed against a
     * real Zoho response: the destination lives in
     * `otp_sent_on_to_mail_address` at the top level — NOT under
     * `Parent.Email` (the `Parent` object only carries a name/id, no email
     * field at all). The other keys are kept as a fallback only.
     */
    public function extractParentEmail(array $data): ?string
    {
        return $data['otp_sent_on_to_mail_address']
            ?? $data['Parent']['Email']
            ?? $data['Parent']['email']
            ?? $data['Parent_Email']
            ?? null;
    }
}
