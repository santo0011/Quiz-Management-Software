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

        $result = app(ZohoStudentService::class)->identify('NL1184', sendOtp: true);

        $this->assertTrue($result['ok']);
    }

    public function test_identify_fails_when_status_explicitly_says_error_even_with_empty_error_array(): void
    {
        Http::fake([
            '*accounts.zoho.com.au*' => Http::response(['access_token' => 'fake-token'], 200),
            '*zohoapis.com.au*' => Http::response(['status' => 'error', 'error' => []], 200),
        ]);

        $result = app(ZohoStudentService::class)->identify('NL1184', sendOtp: true);

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

        $result = app(ZohoStudentService::class)->identify('NL1184', sendOtp: true);

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

        $result = app(ZohoStudentService::class)->identify('ZZ-INVALID-9999', sendOtp: true);

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

        $result = app(ZohoStudentService::class)->identify('NL1184', sendOtp: true);

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
                'Email_status' => 'not_sent',
            ], 200),
        ]);

        $service = app(ZohoStudentService::class);
        $result = $service->identify('NL1405', sendOtp: true);

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

    public function test_result_submission_accepts_success_as_a_string_instead_of_a_boolean(): void
    {
        Http::fake([
            '*accounts.zoho.com.au*' => Http::response(['access_token' => 'fake-token'], 200),
            '*zohoapis.com.au*' => Http::response(['success' => 'true'], 200),
        ]);

        $attempt = $this->makeAttemptWithZohoData();

        $sent = app(ZohoResultService::class)->sendResult($attempt, 'http://localhost/storage/results/abc.pdf');

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

        $sent = app(ZohoResultService::class)->sendResult($attempt, 'http://localhost/storage/results/abc.pdf');

        $this->assertTrue($sent);
    }

    public function test_result_submission_rejects_success_false(): void
    {
        Http::fake([
            '*accounts.zoho.com.au*' => Http::response(['access_token' => 'fake-token'], 200),
            '*zohoapis.com.au*' => Http::response(['success' => false, 'message' => 'Validation error'], 200),
        ]);

        $attempt = $this->makeAttemptWithZohoData();

        $sent = app(ZohoResultService::class)->sendResult($attempt, 'http://localhost/storage/results/abc.pdf');

        $this->assertFalse($sent);
        $this->assertNull($attempt->fresh()->zoho_result_synced_at);
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
            'passing_marks' => 5,
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
            'is_passed' => true,
            'status' => 'submitted',
        ]);
    }
}
