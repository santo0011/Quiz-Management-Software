<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Setting;
use App\Models\Student;
use App\Models\User;
use App\Mail\BranchLoginOtpMail;
use App\Mail\SuperAdminLoginOtpMail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class UnifiedLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_login_requires_super_admin_type(): void
    {
        Mail::fake();

        User::create([
            'name' => 'Super Admin',
            'email' => 'admin@example.com',
            'role' => 'Super Admin',
            'password' => Hash::make('123456'),
        ]);

        $this->post(route('login.store'), [
            'login_type' => 'super_admin',
            'email' => 'admin@example.com',
            'password' => '123456',
        ])->assertRedirect(route('login.otp'));

        $this->assertGuest();

        $otp = null;
        Mail::assertSent(SuperAdminLoginOtpMail::class, function (SuperAdminLoginOtpMail $mail) use (&$otp): bool {
            $otp = $mail->otp;

            return true;
        });

        $this->post(route('login.otp.verify'), ['otp' => $otp])
            ->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticated();
    }

    public function test_branch_cannot_login_from_super_admin_mode(): void
    {
        $branch = Branch::create(['name' => 'Kolkata Branch', 'email' => 'kolkata@example.com']);

        User::create([
            'name' => $branch->name,
            'email' => $branch->email,
            'role' => 'Branch',
            'branch_id' => $branch->id,
            'password' => Hash::make('123456'),
        ]);

        $this->post(route('login.store'), [
            'login_type' => 'super_admin',
            'email' => 'kolkata@example.com',
            'password' => '123456',
        ])->assertSessionHas('login_error');

        $this->assertGuest();
    }

    public function test_branch_login_redirects_to_branch_dashboard(): void
    {
        Mail::fake();

        $branch = Branch::create(['name' => 'Delhi Branch', 'email' => 'delhi@example.com']);

        User::create([
            'name' => $branch->name,
            'email' => $branch->email,
            'role' => 'Branch',
            'branch_id' => $branch->id,
            'password' => Hash::make('123456'),
        ]);

        $this->post(route('login.store'), [
            'login_type' => 'branch',
            'email' => 'delhi@example.com',
            'password' => '123456',
        ])->assertRedirect(route('login.otp'));

        $otp = null;
        Mail::assertSent(BranchLoginOtpMail::class, function (BranchLoginOtpMail $mail) use (&$otp): bool {
            $otp = $mail->otp;

            return true;
        });

        $this->post(route('login.otp.verify'), ['otp' => $otp])
            ->assertRedirect(route('branch.dashboard'));
    }

    public function test_student_can_login_with_nrich_student_id_and_zoho_otp(): void
    {
        $branch = Branch::create(['name' => 'Mumbai Branch', 'email' => 'mumbai@example.com']);

        $student = Student::create([
            'branch_id' => $branch->id,
            'student_name' => 'Student One',
            'guardian_name' => 'Guardian One',
            'class' => 'Class 10',
            'phone_number' => '9876543210',
            'email' => 'student@example.com',
            'zoho_student_id' => 'NL1184',
        ]);

        $zohoPayload = [
            'status' => 'success',
            'error' => [],
            'Student' => ['NRICH_ID' => 'NL1184'],
            'Parent' => ['Email' => 'parent@example.com'],
            'Enrolment' => [
                'classes' => [
                    ['id' => '96867000000904800', 'name' => 'English | Grade 1 ( 1 on 1 ) | Clyde North', 'active' => true],
                ],
            ],
        ];

        Http::fake([
            '*accounts.zoho.com.au*' => Http::response(['access_token' => 'fake-access-token'], 200),
            '*zohoapis.com.au*' => Http::response($zohoPayload, 200),
        ]);

        $this->post(route('login.store'), [
            'login_type' => 'student',
            'nrich_student_id' => 'NL1184',
        ])->assertRedirect(route('login.otp'));

        $this->assertGuest('student');

        $this->post(route('login.otp.verify'), ['otp' => '145263'])
            ->assertRedirect(route('student.dashboard'));

        $this->assertAuthenticated('student');

        $student->refresh();
        $this->assertSame('96867000000904800', $student->zoho_class_id);
        $this->assertSame('English | Grade 1 ( 1 on 1 ) | Clyde North', $student->zoho_class_name);
    }

    public function test_student_login_rejects_an_id_zoho_itself_does_not_recognize(): void
    {
        Http::fake([
            '*accounts.zoho.com.au*' => Http::response(['access_token' => 'fake-access-token'], 200),
            '*zohoapis.com.au*' => Http::response(['status' => 'error', 'error' => ['Student not found']], 200),
        ]);

        $this->post(route('login.store'), [
            'login_type' => 'student',
            'nrich_student_id' => 'UNKNOWN-ID',
        ])->assertSessionHas('login_error', 'No student account found with this Student ID.');

        $this->assertGuest('student');
    }

    /**
     * The core fix under test: Zoho is the source of truth for whether a
     * Student ID is real. An ID Zoho recognizes must reach the OTP screen
     * even when no local Student record exists yet for it — a missing local
     * account is only surfaced (with a distinct, accurate message) after OTP
     * verification, not at this first step.
     */
    public function test_student_reaches_otp_step_when_zoho_recognizes_the_id_even_with_no_local_record_yet(): void
    {
        Http::fake([
            '*accounts.zoho.com.au*' => Http::response(['access_token' => 'fake-access-token'], 200),
            '*zohoapis.com.au*' => Http::response(['status' => 'success', 'error' => []], 200),
        ]);

        $this->assertDatabaseMissing('students', ['zoho_student_id' => 'NL1405']);

        $this->post(route('login.store'), [
            'login_type' => 'student',
            'nrich_student_id' => 'NL1405',
        ])->assertRedirect(route('login.otp'));

        $this->assertGuest('student');
    }

    public function test_otp_verification_shows_a_distinct_error_when_zoho_confirms_but_no_local_account_exists(): void
    {
        Http::fake([
            '*accounts.zoho.com.au*' => Http::response(['access_token' => 'fake-access-token'], 200),
            '*zohoapis.com.au*' => Http::response(['status' => 'success', 'error' => []], 200),
        ]);

        $this->post(route('login.store'), [
            'login_type' => 'student',
            'nrich_student_id' => 'NL1405',
        ])->assertRedirect(route('login.otp'));

        $this->post(route('login.otp.verify'), ['otp' => '145263'])
            ->assertRedirect(route('login'))
            ->assertSessionHas('login_error', 'Your Student ID was verified, but no matching account exists in this system yet. Please contact your administrator.');

        $this->assertGuest('student');
    }

    public function test_student_login_shows_mapped_error_when_zoho_reports_student_not_found(): void
    {
        $branch = Branch::create(['name' => 'Mumbai Branch', 'email' => 'mumbai@example.com']);

        Student::create([
            'branch_id' => $branch->id,
            'student_name' => 'Student One',
            'guardian_name' => 'Guardian One',
            'class' => 'Class 10',
            'phone_number' => '9876543210',
            'email' => 'student@example.com',
            'zoho_student_id' => 'NL1184',
        ]);

        Http::fake([
            '*accounts.zoho.com.au*' => Http::response(['access_token' => 'fake-access-token'], 200),
            '*zohoapis.com.au*' => Http::response(['status' => 'error', 'error' => ['Student not found']], 200),
        ]);

        $this->post(route('login.store'), [
            'login_type' => 'student',
            'nrich_student_id' => 'NL1184',
        ])->assertSessionHas('login_error', 'No student account found with this Student ID.');

        $this->assertGuest('student');
    }

    public function test_student_otp_verification_shows_specific_message_for_invalid_code(): void
    {
        $branch = Branch::create(['name' => 'Mumbai Branch', 'email' => 'mumbai@example.com']);

        Student::create([
            'branch_id' => $branch->id,
            'student_name' => 'Student One',
            'guardian_name' => 'Guardian One',
            'class' => 'Class 10',
            'phone_number' => '9876543210',
            'email' => 'student@example.com',
            'zoho_student_id' => 'NL1184',
        ]);

        Http::fake([
            '*accounts.zoho.com.au*' => Http::response(['access_token' => 'fake-access-token'], 200),
            '*zohoapis.com.au*' => Http::sequence()
                ->push(['status' => 'success', 'error' => []], 200)
                ->push(['status' => 'error', 'error' => ['Invalid OTP entered.']], 200),
        ]);

        $this->post(route('login.store'), [
            'login_type' => 'student',
            'nrich_student_id' => 'NL1184',
        ])->assertRedirect(route('login.otp'));

        $this->post(route('login.otp.verify'), ['otp' => '000000'])
            ->assertSessionHas('otp_error', 'The verification code you entered is incorrect. Please try again.');

        $this->assertGuest('student');
    }

    public function test_student_otp_verification_shows_specific_message_for_expired_code(): void
    {
        $branch = Branch::create(['name' => 'Mumbai Branch', 'email' => 'mumbai@example.com']);

        Student::create([
            'branch_id' => $branch->id,
            'student_name' => 'Student One',
            'guardian_name' => 'Guardian One',
            'class' => 'Class 10',
            'phone_number' => '9876543210',
            'email' => 'student@example.com',
            'zoho_student_id' => 'NL1184',
        ]);

        Http::fake([
            '*accounts.zoho.com.au*' => Http::response(['access_token' => 'fake-access-token'], 200),
            '*zohoapis.com.au*' => Http::sequence()
                ->push(['status' => 'success', 'error' => []], 200)
                ->push(['status' => 'error', 'error' => ['OTP expired.']], 200),
        ]);

        $this->post(route('login.store'), [
            'login_type' => 'student',
            'nrich_student_id' => 'NL1184',
        ])->assertRedirect(route('login.otp'));

        $this->post(route('login.otp.verify'), ['otp' => '000000'])
            ->assertSessionHas('otp_error', 'This code has expired. Please request a new one.');

        $this->assertGuest('student');
    }

    public function test_teacher_override_logs_student_in_directly_without_sending_an_otp(): void
    {
        Setting::current()->update(['common_student_password' => 'override-secret']);

        $branch = Branch::create(['name' => 'Mumbai Branch', 'email' => 'mumbai@example.com']);

        $student = Student::create([
            'branch_id' => $branch->id,
            'student_name' => 'Student One',
            'guardian_name' => 'Guardian One',
            'class' => 'Class 10',
            'phone_number' => '9876543210',
            'email' => 'student@example.com',
            'zoho_student_id' => 'NL1184',
        ]);

        Http::fake([
            '*accounts.zoho.com.au*' => Http::response(['access_token' => 'fake-access-token'], 200),
            '*zohoapis.com.au*' => Http::response(['status' => 'success', 'error' => []], 200),
        ]);

        $this->post(route('login.store'), [
            'login_type' => 'student',
            'nrich_student_id' => 'NL1184',
            'teacher_override' => '1',
            'password' => 'override-secret',
        ])->assertRedirect(route('student.dashboard'));

        $this->assertAuthenticatedAs($student, 'student');

        // send_otp must have been sent as the literal string "false" and no
        // verification_code step ever occurred (only one Zoho call, not two).
        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'lms_portal_endpoint_1')
                && $request['send_otp'] === 'false';
        });
        Http::assertSentCount(2); // 1 token request + 1 Zoho identify call, no OTP round trip.
    }

    public function test_teacher_override_rejects_an_incorrect_common_password(): void
    {
        Setting::current()->update(['common_student_password' => 'override-secret']);

        $branch = Branch::create(['name' => 'Mumbai Branch', 'email' => 'mumbai@example.com']);

        Student::create([
            'branch_id' => $branch->id,
            'student_name' => 'Student One',
            'guardian_name' => 'Guardian One',
            'class' => 'Class 10',
            'phone_number' => '9876543210',
            'email' => 'student@example.com',
            'zoho_student_id' => 'NL1184',
        ]);

        Http::fake();

        $this->post(route('login.store'), [
            'login_type' => 'student',
            'nrich_student_id' => 'NL1184',
            'teacher_override' => '1',
            'password' => 'wrong-password',
        ])->assertSessionHas('login_error', 'Incorrect Teacher Override password.');

        $this->assertGuest('student');
        Http::assertNothingSent();
    }

    public function test_teacher_override_fails_when_no_common_password_has_been_configured(): void
    {
        $branch = Branch::create(['name' => 'Mumbai Branch', 'email' => 'mumbai@example.com']);

        Student::create([
            'branch_id' => $branch->id,
            'student_name' => 'Student One',
            'guardian_name' => 'Guardian One',
            'class' => 'Class 10',
            'phone_number' => '9876543210',
            'email' => 'student@example.com',
            'zoho_student_id' => 'NL1184',
        ]);

        Http::fake();

        $this->post(route('login.store'), [
            'login_type' => 'student',
            'nrich_student_id' => 'NL1184',
            'teacher_override' => '1',
            'password' => 'anything',
        ])->assertSessionHas('login_error', 'Incorrect Teacher Override password.');

        $this->assertGuest('student');
        Http::assertNothingSent();
    }
}
