<?php

namespace App\Services;

use App\Exceptions\ZohoApiException;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Subject;
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
        'invalid otp' => 'Invalid OTP. Please enter the correct OTP.',
        'incorrect otp' => 'Invalid OTP. Please enter the correct OTP.',
        'invalid verification code' => 'Invalid OTP. Please enter the correct OTP.',
        'invalid code' => 'Invalid OTP. Please enter the correct OTP.',
    ];

    private const GENERIC_ERROR = 'We could not verify your Student ID right now. Please try again later.';

    public function __construct(private ZohoAuthService $auth)
    {
    }

    /**
     * Normal login flow, step 1: verify the Student ID and have Zoho send
     * the OTP to the registered email in the SAME request. Also used for
     * "resend code" (identical shape).
     *
     * Confirmed by isolating each field against the live Zoho function: it
     * only sends the email when a non-empty `verification_code` key is ALSO
     * present on a `send_otp:"true"` call — omitting it (the previous
     * behavior here) makes Zoho return `status: success`/`error: []` but
     * `Email_status: "not_sent"`, silently. The field's actual value is
     * irrelevant to Zoho's decision to send (two different arbitrary values
     * both worked; Zoho generates and emails its own real OTP regardless),
     * so a fresh random value is sent purely to satisfy that requirement —
     * it is never the code the student/parent actually receives, and is
     * unrelated to `verifyOtp()`'s verification_code, which IS checked
     * against Zoho's real stored code.
     */
    public function sendOtp(string $nrichStudentId): array
    {
        return $this->call([
            'nrich_student_id' => $nrichStudentId,
            'send_otp' => true,
            'otp_validity' => config('services.zoho.otp_validity_minutes'),
            'verification_code' => (string) random_int(100000, 999999),
            'login_through_teacher_master_code' => false,
        ], expectingOtpSend: true);
    }

    /**
     * Normal login flow, step 2: verify the code the student entered.
     */
    public function verifyOtp(string $nrichStudentId, string $code): array
    {
        return $this->call([
            'nrich_student_id' => $nrichStudentId,
            'send_otp' => false,
            'otp_validity' => config('services.zoho.otp_validity_minutes'),
            'verification_code' => $code,
            'login_through_teacher_master_code' => false,
        ], requireExplicitSuccess: true);
    }

    /**
     * Teacher Override: verify the Student ID only — no OTP is sent or
     * expected (`send_otp: "false"`). `login_through_teacher_master_code`
     * is `"true"` here — the one thing that actually distinguishes this
     * from a normal identify call to Zoho. `otp_validity`/`verification_code`
     * are included to exactly match the confirmed-working request shape for
     * this action; with `send_otp` false neither field triggers any email
     * or code-verification logic on Zoho's side (confirmed live), so
     * `verification_code` is a fixed placeholder rather than a real code.
     */
    public function verifyForTeacherOverride(string $nrichStudentId): array
    {
        return $this->call([
            'nrich_student_id' => $nrichStudentId,
            'send_otp' => false,
            'otp_validity' => config('services.zoho.otp_validity_minutes'),
            'verification_code' => '145263',
            'login_through_teacher_master_code' => true,
        ]);
    }

    /**
     * Persist the Student/Parent/Enrolment data Zoho returned onto the local
     * Student record, so ZohoResultService can later read the active class
     * without calling Zoho again.
     *
     * Also re-resolves, on every login:
     * - The Student's LOCAL Grade (`class_id`/`class`) from Zoho's
     *   `Enrolment.Grade` — this is what Exam::scopeEligibleForStudent()
     *   actually matches on, so it must stay current with Zoho rather than
     *   only being set once at creation time.
     * - The Student's Subject assignments from
     *   `Enrolment.classes[].Subject` — Subjects are no longer manually
     *   managed from the Student list at all; Zoho is the sole source of
     *   truth, re-synced (added AND removed) on every successful login.
     * - The Student's `guardian_email` from Zoho's own OTP-destination
     *   address (`extractParentEmail()`) — this is the exact address Zoho
     *   emailed the OTP to for this login, so any feature that needs to
     *   reach the Student "the same way the OTP did" (e.g. the Branch
     *   Panel's result email) can simply read `guardian_email` and stay
     *   correct even if it was never set, or was set to something else, at
     *   Student creation time. Only overwritten when Zoho actually reports
     *   one this login — never blanked out on a response that omits it.
     */
    public function syncStudentFromZoho(Student $student, array $data): void
    {
        $class = $this->extractActiveClass($data);
        $zohoGrade = $this->extractGrade($data);
        $otpEmail = $this->extractParentEmail($data);

        $attributes = [
            'zoho_payload' => $data,
            'zoho_synced_at' => now(),
            'zoho_class_id' => $class['id'],
            'zoho_class_name' => $class['name'],
            'zoho_grade' => $zohoGrade,
        ];

        if ($localGrade = $this->resolveLocalGrade($student->branch_id, $zohoGrade)) {
            $attributes['class_id'] = $localGrade->id;
            $attributes['class'] = $localGrade->name;
        }

        if (filled($otpEmail)) {
            $attributes['guardian_email'] = $otpEmail;
        }

        $student->forceFill($attributes)->save();

        // Subjects are now driven entirely by Zoho (Enrolment.classes[].Subject)
        // rather than manually assigned from the Student list — sync() here
        // replaces whatever was there before with exactly what Zoho reports on
        // this login, matching the same "resolve to existing local records
        // only, never auto-create" rule already used for Grade above.
        $student->subjects()->sync($this->resolveLocalSubjectIds($this->extractSubjectNames($data)));
    }

    /**
     * Every distinct Subject name Zoho reports for this Student, read from
     * `Enrolment.classes[].Subject` — a Student can have multiple classes
     * (and therefore multiple Subjects), so every class entry is inspected,
     * not just the active one `extractActiveClass()` picks for the
     * Grade/class-name fields.
     *
     * Each class entry pairs exactly ONE `Class` object with ONE `Subject`
     * object (`{name, id}`) — Zoho does NOT nest multiple Subjects under a
     * single class. Only `name` is ever used for matching/display; `id` is
     * Zoho's own identifier and is never matched against or shown.
     */
    private function extractSubjectNames(array $data): array
    {
        $enrolment = $data['Enrolment'] ?? $data['enrolment'] ?? [];
        $classes = $enrolment['classes'] ?? $enrolment['Classes'] ?? [];

        // A single class entry can arrive as one object instead of a list.
        if (is_array($classes) && (isset($classes['Subject']) || isset($classes['subject']) || isset($classes['Class']) || isset($classes['id']))) {
            $classes = [$classes];
        }

        if (! is_array($classes)) {
            return [];
        }

        $names = [];

        foreach ($classes as $class) {
            if (! is_array($class)) {
                continue;
            }

            $subject = $class['Subject'] ?? $class['subject'] ?? null;

            if (! is_array($subject)) {
                continue;
            }

            $name = $subject['name'] ?? $subject['Name'] ?? null;

            if (is_string($name) && filled(trim($name))) {
                $names[] = trim($name);
            }
        }

        return array_values(array_unique($names));
    }

    /**
     * Resolve Zoho Subject names to existing local Subject records (matched
     * by name, case/whitespace insensitive) — deliberately never creates a
     * new Subject: Subjects are managed exclusively by Super Admin ("use the
     * existing Subject records/module... do not create duplicate Subject
     * records"), so a Zoho Subject name with no matching local record is
     * simply left unassigned rather than spawning a duplicate.
     */
    private function resolveLocalSubjectIds(array $subjectNames): array
    {
        if (empty($subjectNames)) {
            return [];
        }

        $normalized = array_map(fn (string $name) => strtolower(trim($name)), $subjectNames);

        return Subject::query()
            ->get(['id', 'name'])
            ->filter(fn (Subject $subject) => in_array(strtolower(trim($subject->name)), $normalized, true))
            ->pluck('id')
            ->all();
    }

    /**
     * Resolve Zoho's raw Grade string (e.g. "Grade 2") to an existing local
     * Grade (SchoolClass) record — matched by name (case/whitespace
     * insensitive), scoped to the Student's own branch or a Super-Admin-
     * created global Grade, exactly like Exam's own branch visibility rule;
     * a branch-specific match is preferred over a same-named global one.
     *
     * Deliberately never creates a new Grade record: Grades are managed
     * exclusively by Super Admin ("avoid creating duplicate Grade values
     * from Zoho"), so a Zoho Grade string with no matching local Grade
     * simply leaves the Student's Grade unresolved — they won't be eligible
     * for any Grade-scoped exam until Super Admin adds a matching Grade —
     * rather than silently spawning a duplicate/inconsistent record.
     */
    private function resolveLocalGrade(?int $branchId, ?string $zohoGrade): ?SchoolClass
    {
        if (! $branchId || ! filled($zohoGrade)) {
            return null;
        }

        return SchoolClass::visibleToBranch($branchId)
            ->whereRaw('LOWER(TRIM(name)) = ?', [strtolower(trim($zohoGrade))])
            ->orderByRaw('branch_id IS NULL')
            ->first();
    }

    /**
     * @param  bool  $expectingOtpSend  True only for the "send OTP" call —
     *                                  gates the extra `Email_status` check
     *                                  below, which is meaningless for a
     *                                  verify/teacher-override call that
     *                                  never asked Zoho to send anything.
     * @param  bool  $requireExplicitSuccess  True only for the real OTP-code
     *                                        verify call — see the block below
     *                                        for why this can't share the
     *                                        identify call's lenient default.
     */
    private function call(array $payload, bool $expectingOtpSend = false, bool $requireExplicitSuccess = false): array
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
                'sent_payload' => $payload,
                'exception' => $e->getMessage(),
            ]);

            return ['ok' => false, 'message' => self::GENERIC_ERROR, 'data' => [], 'otp_validity' => config('services.zoho.otp_validity_minutes')];
        }

        $data = $this->extractBusinessPayload($response->json() ?? []);

        $errors = $this->normalizeErrors($data['error'] ?? $data['errors'] ?? []);
        $status = $data['status'] ?? $data['Status'] ?? null;
        $normalizedStatus = is_string($status) ? strtolower(trim($status)) : $status;
        $emailStatus = $data['Email_status'] ?? $data['email_status'] ?? null;

        // The spec is explicit that the `error` array is the source of truth
        // ("do not allow login if the error array contains an error") — a
        // `status` field is a secondary signal. Only treat `status` as a
        // failure indicator when it explicitly says so; don't require it to
        // literally equal "success", since a response with an empty `error`
        // array and no `status` field at all should still count as OK.
        $statusIndicatesFailure = in_array($normalizedStatus, ['error', 'failed', 'failure', false, 0, '0'], true);

        // That lenient default is safe for the identify/send-OTP call — at
        // worst it lets someone reach the OTP screen, which grants nothing.
        // It is NOT safe for the actual code-verify call: this is where a
        // Student is authenticated, so an ambiguous Zoho response (HTTP 200,
        // empty `error`, but no `status: success` confirming the code itself
        // was checked and matched) must be rejected rather than defaulted to
        // success. This is the fix for the real bug — an incorrect OTP was
        // being accepted because "no error reported" was being read as "OTP
        // correct", when it only ever meant "nothing broke".
        $statusConfirmsSuccess = $normalizedStatus === 'success';
        $otpVerificationFailure = $requireExplicitSuccess ? $this->otpVerificationFailure($data) : null;
        $otpVerificationConfirmed = $requireExplicitSuccess ? $this->otpVerificationConfirmed($data, $statusConfirmsSuccess) : true;

        // When we explicitly asked Zoho to send the OTP, an Email_status
        // that positively says it wasn't sent is a real failure even if
        // `error` came back empty — the whole point of this call was the
        // email, so silently treating this as success would leave the
        // Student stuck on an OTP screen for a code that never arrives.
        $emailSendFailed = $expectingOtpSend
            && is_string($emailStatus)
            && strtolower(trim($emailStatus)) === 'not_sent';

        $failed = $requireExplicitSuccess
            ? (! $response->successful() || ! $otpVerificationConfirmed || ! empty($errors) || $otpVerificationFailure !== null)
            : (! $response->successful() || $statusIndicatesFailure || ! empty($errors) || $emailSendFailed);

        if ($failed) {
            Log::error('Zoho student verification returned an error.', [
                'nrich_student_id' => $payload['nrich_student_id'] ?? null,
                'sent_payload' => $payload,
                'http_status' => $response->status(),
                'zoho_status' => $normalizedStatus,
                'email_status' => $emailStatus,
                'otp_verification_failure' => $otpVerificationFailure,
                'errors' => $errors,
            ]);

            $message = match (true) {
                (bool) $errors => $this->mapErrorMessage($errors),
                $otpVerificationFailure === 'expired' => 'This code has expired. Please request a new one.',
                $otpVerificationFailure !== null => 'Invalid OTP. Please enter the correct OTP.',
                $emailSendFailed => 'We could not send the verification code email. Please try again shortly.',
                $requireExplicitSuccess => 'Invalid OTP. Please enter the correct OTP.',
                default => self::GENERIC_ERROR,
            };

            return [
                'ok' => false,
                'message' => $message,
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

    private function otpVerificationConfirmed(array $data, bool $statusConfirmsSuccess): bool
    {
        foreach ($this->verificationValues($data) as $value) {
            if ($this->isTruthyVerificationValue($value)) {
                return true;
            }
        }

        return $statusConfirmsSuccess;
    }

    private function otpVerificationFailure(array $data): ?string
    {
        foreach ($this->verificationValues($data) as $value) {
            if (is_bool($value)) {
                return $value ? null : 'invalid';
            }

            if (is_int($value) || is_float($value)) {
                return ((int) $value) === 1 ? null : 'invalid';
            }

            if (! is_string($value)) {
                continue;
            }

            $normalized = strtolower(trim($value));

            if (in_array($normalized, ['true', '1', 'success', 'successful', 'verified', 'valid', 'matched', 'match'], true)) {
                continue;
            }

            if (str_contains($normalized, 'expired')) {
                return 'expired';
            }

            if (str_contains($normalized, 'invalid')
                || str_contains($normalized, 'incorrect')
                || str_contains($normalized, 'mismatch')
                || str_contains($normalized, 'not match')
                || str_contains($normalized, 'wrong')
                || in_array($normalized, ['false', '0', 'failed', 'failure', 'fail', 'not_verified', 'unverified'], true)) {
                return 'invalid';
            }
        }

        return null;
    }

    private function isTruthyVerificationValue(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_int($value) || is_float($value)) {
            return ((int) $value) === 1;
        }

        if (! is_string($value)) {
            return false;
        }

        return in_array(strtolower(trim($value)), ['true', '1', 'success', 'successful', 'verified', 'valid', 'matched', 'match'], true);
    }

    /**
     * Zoho has used several field names for business-status details over
     * time. For the OTP verify step only, any verification-looking field is
     * security-sensitive and must be honored instead of relying solely on
     * HTTP 200 or a generic top-level `status: success`.
     *
     * @return array<int, mixed>
     */
    private function verificationValues(array $data): array
    {
        $values = [];

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                foreach ($this->verificationValues($value) as $nestedValue) {
                    $values[] = $nestedValue;
                }

                continue;
            }

            $normalizedKey = strtolower((string) $key);

            if ((str_contains($normalizedKey, 'otp') || str_contains($normalizedKey, 'verification') || str_contains($normalizedKey, 'verify'))
                && ! str_contains($normalizedKey, 'email')
                && ! str_contains($normalizedKey, 'mail')
                && ! str_contains($normalizedKey, 'validity')) {
                $values[] = $value;
            }
        }

        return $values;
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
     * Confirmed against the real Zoho function's documented error shapes —
     * each is a structured `{field: reason}` object, never a human-readable
     * string:
     *   {"student_record": "not_found"}
     *   {"parent_record": "no_linking_record_found"}
     *   {"enrolment_record": "not_found"}
     *   {"parent_record": "email_address_not_found"}
     *   {"email": "not_sent"}
     * Flatten the object to a lowercase string and pattern-match on it
     * rather than requiring an exact shape, so close variants still map
     * correctly.
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

            return $this->genericErrorWithDebugDetail($first);
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
            str_contains($haystack, 'parent') && (str_contains($haystack, 'linking') || str_contains($haystack, 'link')) => 'This student is not linked to a parent record. Please contact your administrator.',
            str_contains($haystack, 'enrol') && str_contains($haystack, 'not_found') => 'No active enrolment was found for this student. Please contact your administrator.',
            str_contains($haystack, 'parent') && str_contains($haystack, 'email') => 'No parent email is on file for this student. Please contact your administrator.',
            str_contains($haystack, 'email') && str_contains($haystack, 'not_sent') => 'We could not send the verification code email. Please try again shortly.',
            str_contains($haystack, 'otp') && (str_contains($haystack, 'not_sent') || str_contains($haystack, 'fail')) => 'We could not send the verification code email. Please try again shortly.',
            (str_contains($haystack, 'otp') || str_contains($haystack, 'verification_code') || str_contains($haystack, 'verification code'))
                && str_contains($haystack, 'expired') => 'This code has expired. Please request a new one.',
            (str_contains($haystack, 'otp') || str_contains($haystack, 'verification_code') || str_contains($haystack, 'verification code'))
                && (str_contains($haystack, 'invalid') || str_contains($haystack, 'incorrect') || str_contains($haystack, 'mismatch')) => 'Invalid OTP. Please enter the correct OTP.',
            default => $this->genericErrorWithDebugDetail($first),
        };
    }

    /**
     * Never hide an unrecognized Zoho error behind the generic message
     * while debugging — append the raw (non-sensitive; this is business
     * status data, never a credential) error detail when app.debug is on.
     */
    private function genericErrorWithDebugDetail(mixed $rawError): string
    {
        if (! config('app.debug')) {
            return self::GENERIC_ERROR;
        }

        return self::GENERIC_ERROR.' [Zoho error: '.(is_string($rawError) ? $rawError : json_encode($rawError)).']';
    }

    /**
     * The Class Zoho considers "active" for this Student, used for the
     * Grade/class-name fields and for the `Student_Class` sent back to Zoho
     * in ZohoResultService. Each `Enrolment.classes[]` entry nests its
     * identity under a `Class` object (`class['Class']['id']`/`['name']`) —
     * NOT flat on the entry itself — matching the same confirmed shape
     * `extractSubjectNames()` reads its `Subject` object from; a flat
     * `id`/`name` on the entry is also tolerated in case an older/different
     * payload shape is ever received.
     */
    private function extractActiveClass(array $data): array
    {
        $enrolment = $data['Enrolment'] ?? $data['enrolment'] ?? [];
        $classes = $enrolment['classes'] ?? $enrolment['Classes'] ?? [];

        // A single class entry can arrive as one object instead of a list.
        if (is_array($classes) && (isset($classes['Class']) || isset($classes['class']) || isset($classes['Subject']) || isset($classes['subject']) || isset($classes['id']))) {
            $classes = [$classes];
        }

        if (! is_array($classes)) {
            return ['id' => null, 'name' => null];
        }

        foreach ($classes as $class) {
            if (! is_array($class)) {
                continue;
            }

            $active = $class['active'] ?? $class['is_active'] ?? $class['Active'] ?? null;
            if (in_array($active, [true, 1, '1', 'true'], true)) {
                return $this->classIdentity($class);
            }
        }

        $first = $classes[0] ?? null;
        if (is_array($first)) {
            return $this->classIdentity($first);
        }

        return ['id' => null, 'name' => null];
    }

    /**
     * @return array{id: string, name: string}
     */
    private function classIdentity(array $class): array
    {
        $classObject = $class['Class'] ?? $class['class'] ?? null;

        if (is_array($classObject)) {
            return [
                'id' => (string) ($classObject['id'] ?? ''),
                'name' => (string) ($classObject['name'] ?? ''),
            ];
        }

        return [
            'id' => (string) ($class['id'] ?? ''),
            'name' => (string) ($class['name'] ?? ''),
        ];
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

    /**
     * Everything needed to create a local Student record purely from real
     * Zoho data (Teacher Override's JIT provisioning, when Zoho confirms an
     * ID that has no local record yet) — no field here is invented; each is
     * either a genuine Zoho value or null when Zoho didn't provide one.
     *
     * @return array{student_name: ?string, guardian_name: ?string, guardian_email: ?string, class_name: ?string, grade: ?string}
     */
    public function extractProvisioningData(array $data): array
    {
        $class = $this->extractActiveClass($data);

        return [
            'student_name' => $data['Student']['name'] ?? $data['Student']['Name'] ?? null,
            'guardian_name' => $data['Parent']['name'] ?? $data['Parent']['Name'] ?? null,
            'guardian_email' => $this->extractParentEmail($data),
            'class_name' => $class['name'] ?: null,
            'grade' => $this->extractGrade($data),
        ];
    }
}
