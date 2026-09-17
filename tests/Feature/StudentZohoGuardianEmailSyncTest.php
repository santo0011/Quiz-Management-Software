<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Student;
use App\Services\ZohoStudentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * `guardian_email` must always reflect the exact address Zoho emailed the
 * OTP to for the Student's most recent login (`otp_sent_on_to_mail_address`
 * / ZohoStudentService::extractParentEmail()) — the Branch Panel's "Review &
 * Send Result" feature reuses this field as "the same email address used
 * for the student's OTP login/verification", so it must stay current on
 * every sync rather than only being set once at creation time.
 */
class StudentZohoGuardianEmailSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_sync_updates_guardian_email_from_the_zoho_otp_destination_address(): void
    {
        $branch = Branch::create(['name' => 'Branch A', 'email' => 'branch-a@example.com']);
        $student = Student::create([
            'branch_id' => $branch->id,
            'student_name' => 'Test Student',
            'guardian_name' => 'Guardian',
            'guardian_email' => 'stale-manually-entered@example.com',
            'class' => 'Unassigned',
            'phone_number' => '123',
            'email' => 'student-'.uniqid().'@example.com',
            'zoho_student_id' => 'NL-EMAIL-1',
            'is_active' => true,
        ]);

        app(ZohoStudentService::class)->syncStudentFromZoho($student, [
            'otp_sent_on_to_mail_address' => 'current-parent-otp@example.com',
            'Enrolment' => ['Grade' => 'Grade 2'],
        ]);

        $this->assertSame('current-parent-otp@example.com', $student->fresh()->guardian_email);
    }

    public function test_sync_leaves_guardian_email_untouched_when_zoho_reports_none_this_time(): void
    {
        $branch = Branch::create(['name' => 'Branch A', 'email' => 'branch-a@example.com']);
        $student = Student::create([
            'branch_id' => $branch->id,
            'student_name' => 'Test Student',
            'guardian_name' => 'Guardian',
            'guardian_email' => 'existing-parent@example.com',
            'class' => 'Unassigned',
            'phone_number' => '123',
            'email' => 'student-'.uniqid().'@example.com',
            'zoho_student_id' => 'NL-EMAIL-2',
            'is_active' => true,
        ]);

        app(ZohoStudentService::class)->syncStudentFromZoho($student, [
            'Enrolment' => ['Grade' => 'Grade 2'],
        ]);

        $this->assertSame('existing-parent@example.com', $student->fresh()->guardian_email);
    }
}
