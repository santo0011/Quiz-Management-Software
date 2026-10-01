<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\ExamAttempt;
use App\Models\Exam;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Services\ZohoResultService;
use App\Services\ZohoStudentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Targeted regression tests for Zoho response-parsing edge cases. Some lock
 * in defensive handling for shapes that hadn't been observed live at the
 * time they were written; others (marked explicitly) lock in behavior
 * confirmed against a real Zoho response captured with live credentials.
 */
class ZohoResponseParsingTest extends TestCase
{
    use RefreshDatabase;

    public function test_identify_succeeds_when_response_has_no_status_field_at_all(): void
    {
        // Only an empty `error` array — no `status` key present. Per spec,
        // the error array is the source of truth; a missing status must not
        // cause a false rejection.
        Http::fake([
            '*accounts.zoho.com.au*' => Http::response(['access_token' => 'fake-token'], 200),
            '*zohoapis.com.au*' => Http::response([
                'error' => [],
                'Student' => ['NRICH_ID' => 'NL1184'],
                'Enrolment' => ['classes' => [['id' => 'C1', 'name' => 'Class A', 'active' => true]]],
            ], 200),
        ]);

        $result = app(ZohoStudentService::class)->sendOtp('NL1184');

        $this->assertTrue($result['ok']);
    }

    public function test_identify_fails_when_status_explicitly_says_error_even_with_empty_error_array(): void
    {
        Http::fake([
            '*accounts.zoho.com.au*' => Http::response(['access_token' => 'fake-token'], 200),
            '*zohoapis.com.au*' => Http::response(['status' => 'error', 'error' => []], 200),
        ]);

        $result = app(ZohoStudentService::class)->sendOtp('NL1184');

        $this->assertFalse($result['ok']);
    }

    public function test_identify_handles_error_returned_as_a_single_object_instead_of_a_list(): void
    {
        Http::fake([
            '*accounts.zoho.com.au*' => Http::response(['access_token' => 'fake-token'], 200),
            '*zohoapis.com.au*' => Http::response([
                'status' => 'error',
                'error' => ['message' => 'Student not found'],
            ], 200),
        ]);

        $result = app(ZohoStudentService::class)->sendOtp('NL1184');

        $this->assertFalse($result['ok']);
        $this->assertSame('No student account found with this Student ID.', $result['message']);
    }

    /**
     * This is the ACTUAL shape confirmed against the live Zoho API (not a
     * guess): a "not found" error has no free-text message at all, only
     * structured keys like `student_record: "not_found"`. This regression
     * test exists because the original guessed string-matching logic
     * silently missed this real shape and fell back to a generic error.
     */
    public function test_identify_maps_the_real_zoho_student_not_found_error_shape(): void
    {
        Http::fake([
            '*accounts.zoho.com.au*' => Http::response(['access_token' => 'fake-token'], 200),
            '*zohoapis.com.au*' => Http::response([
                'status' => 'success',
                'error' => [
                    ['student_record' => 'not_found', 'search_parameter' => 'ZZ-INVALID-9999'],
                ],
                'Student' => [],
                'Parent' => [],
                'Enrolment' => [],
            ], 200),
        ]);

        $result = app(ZohoStudentService::class)->sendOtp('ZZ-INVALID-9999');

        $this->assertFalse($result['ok']);
        $this->assertSame('No student account found with this Student ID.', $result['message']);
    }

    public function test_identify_extracts_business_payload_nested_under_details_without_a_status_key(): void
    {
        Http::fake([
            '*accounts.zoho.com.au*' => Http::response(['access_token' => 'fake-token'], 200),
            '*zohoapis.com.au*' => Http::response([
                'details' => [
                    'error' => ['Active enrolment not found'],
                ],
                'code' => 'success',
            ], 200),
        ]);

        $result = app(ZohoStudentService::class)->sendOtp('NL1184');

        $this->assertFalse($result['ok']);
        $this->assertSame('No active enrolment was found for this student. Please contact your administrator.', $result['message']);
    }

    /**
     * Real shape confirmed live for Student ID NL1405: the OTP destination
     * address is a top-level `otp_sent_on_to_mail_address` field — `Parent`
     * only carries a name/id, with no email field at all.
     */
    public function test_identify_extracts_the_real_parent_email_field_and_active_class(): void
    {
        Http::fake([
            '*accounts.zoho.com.au*' => Http::response(['access_token' => 'fake-token'], 200),
            '*zohoapis.com.au*' => Http::response([
                'status' => 'success',
                'error' => [],
                'Student' => ['name' => 'avsbera', 'id' => '96867000007369018'],
                'Parent' => ['name' => 'Laravel Portal', 'id' => '96867000007369006'],
                'Enrolment' => [
                    'classes' => [
                        ['name' => 'Science | Grade 2 (Group) | Ringwood (Head Office)', 'id' => '96867000000927309'],
                    ],
                    'Grade' => 'Grade 2',
                ],
                'otp_sent_on_to_mail_address' => 'student-guardian@example.com',
                'otp_sent_on_cc_mail_address' => 'student-guardian-cc@example.com',
                'Email_status' => 'sent',
            ], 200),
        ]);

        $service = app(ZohoStudentService::class);
        $result = $service->sendOtp('NL1405');

        $this->assertTrue($result['ok']);
        $this->assertSame('student-guardian@example.com', $service->extractParentEmail($result['data']));

        $branch = Branch::create(['name' => 'Main Branch', 'email' => 'main@example.com']);
        $student = Student::create([
            'branch_id' => $branch->id,
            'student_name' => 'Test Student',
            'guardian_name' => 'Test Guardian',
            'class' => 'Class 10',
            'phone_number' => '9876543210',
            'email' => 'student@example.com',
            'zoho_student_id' => 'NL1405',
        ]);

        $service->syncStudentFromZoho($student, $result['data']);
        $student->refresh();

        $this->assertSame('96867000000927309', $student->zoho_class_id);
        $this->assertSame('Science | Grade 2 (Group) | Ringwood (Head Office)', $student->zoho_class_name);
        $this->assertSame('Grade 2', $student->zoho_grade);
    }

    /**
     * The actual root cause, confirmed by isolating each field against the
     * LIVE Zoho function (four calls, one variable changed at a time):
     * omitting `verification_code` on a `send_otp:true` call makes Zoho
     * return `status: success` / `error: []` but silently skip the email
     * (`Email_status: "not_sent"`). A non-empty `verification_code` must be
     * present for Zoho to actually send it — its content is irrelevant
     * (two different arbitrary values both worked; Zoho generates and
     * emails its own real code regardless of what's sent here), and
     * `login_through_teacher_master_code` has no bearing on email sending
     * at all. This locks in the exact payload shape Laravel must send for
     * each of the three distinct Zoho API 2 actions.
     */
    public function test_send_otp_payload_includes_verification_code_and_uses_correct_otp_validity(): void
    {
        Http::fake([
            '*accounts.zoho.com.au*' => Http::response(['access_token' => 'fake-token'], 200),
            '*zohoapis.com.au*' => Http::response(['status' => 'success', 'error' => []], 200),
        ]);

        app(ZohoStudentService::class)->sendOtp('NL1405');

        Http::assertSent(function ($request) {
            if (! str_contains($request->url(), 'lms_portal_endpoint_1')) {
                return false;
            }

            $body = $request->data();

            return $body['nrich_student_id'] === 'NL1405'
                && $body['send_otp'] === true
                && $body['login_through_teacher_master_code'] === false
                && array_key_exists('otp_validity', $body)
                && array_key_exists('verification_code', $body)
                && $body['verification_code'] !== '';
        });
    }

    public function test_verify_otp_payload_includes_the_entered_code(): void
    {
        Http::fake([
            '*accounts.zoho.com.au*' => Http::response(['access_token' => 'fake-token'], 200),
            '*zohoapis.com.au*' => Http::response(['status' => 'success', 'error' => []], 200),
        ]);

        app(ZohoStudentService::class)->verifyOtp('NL1405', '123456');

        Http::assertSent(function ($request) {
            if (! str_contains($request->url(), 'lms_portal_endpoint_1')) {
                return false;
            }

            $body = $request->data();

            return $body['nrich_student_id'] === 'NL1405'
                && $body['send_otp'] === false
                && $body['verification_code'] === '123456'
                && $body['login_through_teacher_master_code'] === false;
        });
    }

    /**
     * Teacher Override must actually tell Zoho it's an override call (this
     * was previously hardcoded to "false" unconditionally, meaning Zoho was
     * never informed a Teacher Override login was happening at all), and
     * must match the confirmed-working request shape exactly:
     * `otp_validity`/`verification_code` present (harmless placeholders
     * with `send_otp: false`), `send_otp: false`,
     * `login_through_teacher_master_code: true`.
     */
    public function test_teacher_override_payload_matches_the_confirmed_working_shape(): void
    {
        Http::fake([
            '*accounts.zoho.com.au*' => Http::response(['access_token' => 'fake-token'], 200),
            '*zohoapis.com.au*' => Http::response(['status' => 'success', 'error' => []], 200),
        ]);

        app(ZohoStudentService::class)->verifyForTeacherOverride('NL1405');

        Http::assertSent(function ($request) {
            if (! str_contains($request->url(), 'lms_portal_endpoint_1')) {
                return false;
            }

            $body = $request->data();

            return $body['nrich_student_id'] === 'NL1405'
                && $body['send_otp'] === false
                && $body['login_through_teacher_master_code'] === true
                && array_key_exists('otp_validity', $body)
                && $body['verification_code'] === '145263';
        });
    }

    /**
     * A send-OTP call can come back with `status: success` and an empty
     * `error` array, yet Zoho's own `Email_status` says the email was
     * never actually sent — that combination must still be treated as a
     * failure, not silently pushed through to the OTP screen.
     */
    public function test_send_otp_fails_when_email_status_says_not_sent_even_with_no_error(): void
    {
        Http::fake([
            '*accounts.zoho.com.au*' => Http::response(['access_token' => 'fake-token'], 200),
            '*zohoapis.com.au*' => Http::response([
                'status' => 'success',
                'error' => [],
                'Email_status' => 'not_sent',
            ], 200),
        ]);

        $result = app(ZohoStudentService::class)->sendOtp('NL1405');

        $this->assertFalse($result['ok']);
        $this->assertSame('We could not send the verification code email. Please try again shortly.', $result['message']);
    }

    /**
     * The same Email_status check must NOT apply to a verify/teacher-
     * override call, which never asked Zoho to send anything in the first
     * place — Email_status there is irrelevant noise, not a failure signal.
     */
    public function test_verify_otp_ignores_email_status_since_it_never_requested_a_send(): void
    {
        Http::fake([
            '*accounts.zoho.com.au*' => Http::response(['access_token' => 'fake-token'], 200),
            '*zohoapis.com.au*' => Http::response([
                'status' => 'success',
                'error' => [],
                'Email_status' => 'not_sent',
            ], 200),
        ]);

        $result = app(ZohoStudentService::class)->verifyOtp('NL1405', '123456');

        $this->assertTrue($result['ok']);
    }

    /**
     * The actual bug being fixed: unlike the identify/send-OTP call, the
     * code-verify call must NOT default an ambiguous response (HTTP 200,
     * empty `error`, no explicit `status: success`) to success — that
     * lenient default is what let a wrong OTP log a Student in.
     */
    public function test_verify_otp_rejects_an_ambiguous_response_with_no_explicit_success_status(): void
    {
        Http::fake([
            '*accounts.zoho.com.au*' => Http::response(['access_token' => 'fake-token'], 200),
            '*zohoapis.com.au*' => Http::response([
                'error' => [],
            ], 200),
        ]);

        $result = app(ZohoStudentService::class)->verifyOtp('NL1405', '000000');

        $this->assertFalse($result['ok']);
        $this->assertSame('Invalid OTP. Please enter the correct OTP.', $result['message']);
    }

    public function test_verify_otp_rejects_success_status_when_verification_indicator_is_false(): void
    {
        Http::fake([
            '*accounts.zoho.com.au*' => Http::response(['access_token' => 'fake-token'], 200),
            '*zohoapis.com.au*' => Http::response([
                'status' => 'success',
                'error' => [],
                'otp_verified' => false,
            ], 200),
        ]);

        $result = app(ZohoStudentService::class)->verifyOtp('NL1405', '999999');

        $this->assertFalse($result['ok']);
        $this->assertSame('Invalid OTP. Please enter the correct OTP.', $result['message']);
    }

    public function test_verify_otp_accepts_success_status_with_truthy_verification_indicator(): void
    {
        Http::fake([
            '*accounts.zoho.com.au*' => Http::response(['access_token' => 'fake-token'], 200),
            '*zohoapis.com.au*' => Http::response([
                'status' => 'success',
                'error' => [],
                'otp_verified' => true,
            ], 200),
        ]);

        $result = app(ZohoStudentService::class)->verifyOtp('NL1405', '123456');

        $this->assertTrue($result['ok']);
    }

    public function test_verify_otp_maps_structured_verification_code_errors(): void
    {
        Http::fake([
            '*accounts.zoho.com.au*' => Http::response(['access_token' => 'fake-token'], 200),
            '*zohoapis.com.au*' => Http::response([
                'status' => 'error',
                'error' => [['verification_code' => 'invalid']],
            ], 200),
        ]);

        $result = app(ZohoStudentService::class)->verifyOtp('NL1405', '000000');

        $this->assertFalse($result['ok']);
        $this->assertSame('Invalid OTP. Please enter the correct OTP.', $result['message']);
    }

    /** Documented error shape that previously slipped through unmapped. */
    public function test_maps_the_documented_parent_linking_error_shape(): void
    {
        Http::fake([
            '*accounts.zoho.com.au*' => Http::response(['access_token' => 'fake-token'], 200),
            '*zohoapis.com.au*' => Http::response([
                'status' => 'error',
                'error' => [['parent_record' => 'no_linking_record_found']],
            ], 200),
        ]);

        $result = app(ZohoStudentService::class)->sendOtp('NL1405');

        $this->assertFalse($result['ok']);
        $this->assertSame('This student is not linked to a parent record. Please contact your administrator.', $result['message']);
    }

    /** Documented error shape that previously slipped through unmapped. */
    public function test_maps_the_documented_email_not_sent_error_shape(): void
    {
        Http::fake([
            '*accounts.zoho.com.au*' => Http::response(['access_token' => 'fake-token'], 200),
            '*zohoapis.com.au*' => Http::response([
                'status' => 'error',
                'error' => [['email' => 'not_sent']],
            ], 200),
        ]);

        $result = app(ZohoStudentService::class)->sendOtp('NL1405');

        $this->assertFalse($result['ok']);
        $this->assertSame('We could not send the verification code email. Please try again shortly.', $result['message']);
    }

    public function test_result_submission_accepts_success_as_a_string_instead_of_a_boolean(): void
    {
        Http::fake([
            '*accounts.zoho.com.au*' => Http::response(['access_token' => 'fake-token'], 200),
            '*zohoapis.com.au*' => Http::response(['success' => 'true'], 200),
        ]);

        $attempt = $this->makeAttemptWithZohoData();

        $sent = app(ZohoResultService::class)->sendResult($attempt, 'https://portal.example.com/storage/results/abc.pdf');

        $this->assertTrue($sent);
        $this->assertNotNull($attempt->fresh()->zoho_result_synced_at);
    }

    public function test_result_submission_extracts_success_nested_under_details(): void
    {
        Http::fake([
            '*accounts.zoho.com.au*' => Http::response(['access_token' => 'fake-token'], 200),
            '*zohoapis.com.au*' => Http::response(['details' => ['success' => true]], 200),
        ]);

        $attempt = $this->makeAttemptWithZohoData();

        $sent = app(ZohoResultService::class)->sendResult($attempt, 'https://portal.example.com/storage/results/abc.pdf');

        $this->assertTrue($sent);
    }

    public function test_result_submission_rejects_success_false(): void
    {
        Http::fake([
            '*accounts.zoho.com.au*' => Http::response(['access_token' => 'fake-token'], 200),
            '*zohoapis.com.au*' => Http::response(['success' => false, 'message' => 'Validation error'], 200),
        ]);

        $attempt = $this->makeAttemptWithZohoData();

        $sent = app(ZohoResultService::class)->sendResult($attempt, 'https://portal.example.com/storage/results/abc.pdf');

        $this->assertFalse($sent);
        $this->assertNull($attempt->fresh()->zoho_result_synced_at);
    }

    public function test_result_submission_skips_zoho_when_pdf_url_is_localhost(): void
    {
        Http::fake();

        $attempt = $this->makeAttemptWithZohoData();

        $service = app(ZohoResultService::class);
        $sent = $service->sendResult($attempt, 'http://localhost:8000/result-pdfs/1/abc');

        $this->assertFalse($sent);
        $this->assertSame(
            'Zoho needs a verified public HTTPS PDF URL. Set APP_URL or ZOHO_RESULT_PDF_BASE_URL to your live HTTPS website URL.',
            $service->lastFailureMessage()
        );
        $this->assertNull($attempt->fresh()->zoho_result_synced_at);
        Http::assertNothingSent();
    }

    public function test_extract_location_reads_the_enrolment_location_field(): void
    {
        $service = app(ZohoStudentService::class);

        $this->assertSame('Clyde North', $service->extractLocation(['Enrolment' => ['Location' => 'Clyde North']]));
        $this->assertSame('Ringwood Head Office', $service->extractLocation(['Enrolment' => ['location' => 'Ringwood Head Office']]));
        $this->assertNull($service->extractLocation(['Enrolment' => []]));
    }

    public function test_first_location_segment_takes_only_the_first_word(): void
    {
        $service = app(ZohoStudentService::class);

        $this->assertSame('Clyde', $service->firstLocationSegment('Clyde North'));
        $this->assertSame('Clyde', $service->firstLocationSegment('Clyde South'));
        $this->assertSame('Clyde', $service->firstLocationSegment('Clyde'));
        $this->assertSame('Ringwood', $service->firstLocationSegment('Ringwood Head Office'));
        $this->assertNull($service->firstLocationSegment(null));
        $this->assertNull($service->firstLocationSegment('   '));
    }

    public function test_resolve_branch_from_location_matches_case_insensitively(): void
    {
        $branch = Branch::create(['name' => 'clyde', 'email' => 'clyde-branch@example.com']);

        $service = app(ZohoStudentService::class);

        $this->assertSame($branch->id, $service->resolveBranchFromLocation('Clyde North')?->id);
        $this->assertSame($branch->id, $service->resolveBranchFromLocation('CLYDE SOUTH')?->id);
        $this->assertSame($branch->id, $service->resolveBranchFromLocation('ClYdE')?->id);
    }

    public function test_resolve_branch_from_location_returns_null_and_never_falls_back_when_no_branch_matches(): void
    {
        Branch::create(['name' => 'Clyde', 'email' => 'clyde-branch@example.com']);

        $service = app(ZohoStudentService::class);

        $this->assertNull($service->resolveBranchFromLocation('Somewhere Else'));
        $this->assertNull($service->resolveBranchFromLocation(null));
    }

    public function test_subject_matching_is_case_insensitive_when_syncing_from_zoho(): void
    {
        $branch = Branch::create(['name' => 'Clyde', 'email' => 'clyde-subject-test@example.com']);
        $class = SchoolClass::create(['branch_id' => $branch->id, 'name' => 'Class 10']);
        $subject = \App\Models\Subject::create(['name' => 'Mathematics']);

        $student = Student::create([
            'branch_id' => $branch->id,
            'class_id' => $class->id,
            'student_name' => 'Subject Case Test',
            'guardian_name' => 'Guardian',
            'class' => $class->name,
            'phone_number' => '9876543210',
            'email' => 'subject-case-test@example.com',
            'is_active' => true,
            'zoho_student_id' => 'NL2001',
        ]);

        app(ZohoStudentService::class)->syncStudentFromZoho($student, [
            'Enrolment' => [
                'classes' => [
                    ['Class' => ['id' => 'C1', 'name' => 'Class 10'], 'Subject' => ['name' => 'MATHEMATICS'], 'active' => true],
                ],
            ],
        ]);

        $this->assertTrue($student->subjects()->whereKey($subject->id)->exists());
    }

    private function makeAttemptWithZohoData(): ExamAttempt
    {
        $branch = Branch::create(['name' => 'Main Branch', 'email' => 'main@example.com']);
        $class = SchoolClass::create(['branch_id' => $branch->id, 'name' => 'Class 10']);

        $student = Student::create([
            'branch_id' => $branch->id,
            'class_id' => $class->id,
            'student_name' => 'Test Student',
            'guardian_name' => 'Test Guardian',
            'class' => $class->name,
            'phone_number' => '9876543210',
            'email' => 'student@example.com',
            'is_active' => true,
            'zoho_student_id' => 'NL1184',
            'zoho_class_id' => '96867000000904800',
            'zoho_class_name' => 'English | Grade 1 ( 1 on 1 ) | Clyde North',
            'zoho_grade' => 'Grade 1',
        ]);

        $exam = Exam::create([
            'branch_id' => $branch->id,
            'school_class_id' => $class->id,
            'title' => 'Algebra Basics',
            'total_marks' => 10,
            'duration_minutes' => 30,
            'starts_at' => now()->subMinute(),
            'ends_at' => now()->addHour(),
            'maximum_attempts' => 1,
            'status' => Exam::STATUS_PUBLISHED,
        ]);

        return ExamAttempt::create([
            'exam_id' => $exam->id,
            'student_id' => $student->id,
            'branch_id' => $branch->id,
            'school_class_id' => $class->id,
            'attempt_number' => 1,
            'started_at' => now()->subMinutes(10),
            'expires_at' => now()->addMinutes(10),
            'submitted_at' => now(),
            'obtained_marks' => 8,
            'percentage' => 80,
            'correct_count' => 1,
            'wrong_count' => 0,
            'unanswered_count' => 0,
            'status' => 'submitted',
        ]);
    }
}
