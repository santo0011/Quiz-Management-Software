<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Student;
use App\Models\User;
use App\Mail\BranchLoginOtpMail;
use App\Mail\StudentLoginOtpMail;
use App\Mail\StudentOtpMail;
use App\Mail\SuperAdminLoginOtpMail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
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

    public function test_student_can_login_with_six_digit_code(): void
    {
        Mail::fake();

        $branch = Branch::create(['name' => 'Mumbai Branch', 'email' => 'mumbai@example.com']);

        Student::create([
            'branch_id' => $branch->id,
            'student_name' => 'Student One',
            'guardian_name' => 'Guardian One',
            'class' => 'Class 10',
            'phone_number' => '9876543210',
            'email' => 'student@example.com',
            'login_code_hash' => Hash::make('654321'),
        ]);

        $this->post(route('login.store'), [
            'login_type' => 'student',
            'email' => 'student@example.com',
            'password' => '654321',
        ])->assertRedirect(route('login.otp'));

        $this->assertGuest('student');

        $otp = null;
        Mail::assertSent(StudentLoginOtpMail::class, function (StudentLoginOtpMail $mail) use (&$otp): bool {
            $otp = $mail->otp;

            return true;
        });

        $this->post(route('login.otp.verify'), ['otp' => $otp])
            ->assertRedirect(route('student.dashboard'));

        $this->assertAuthenticated('student');
    }

    public function test_student_email_check_requires_existing_student_account(): void
    {
        $this->postJson(route('student-login.check-email'), [
            'email' => 'missing@example.com',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['email'])
            ->assertJsonPath('errors.email.0', 'No student account found with this email address.');
    }

    public function test_student_without_password_can_create_password_with_single_use_otp(): void
    {
        Mail::fake();

        $branch = Branch::create(['name' => 'Mumbai Branch', 'email' => 'mumbai@example.com']);

        $student = Student::create([
            'branch_id' => $branch->id,
            'student_name' => 'Student One',
            'guardian_name' => 'Guardian One',
            'class' => 'Class 10',
            'phone_number' => '9876543210',
            'email' => 'student@example.com',
        ]);

        $this->postJson(route('student-login.check-email'), [
            'email' => $student->email,
        ])->assertOk()
            ->assertJsonPath('status', 'password_setup_required');

        $this->postJson(route('student-login.send-otp'), [
            'email' => $student->email,
        ])->assertOk();

        $otp = null;
        Mail::assertSent(StudentOtpMail::class, function (StudentOtpMail $mail) use (&$otp): bool {
            $otp = $mail->otp;

            return strlen($mail->otp) === 6;
        });

        $this->postJson(route('student-login.verify-otp'), [
            'email' => $student->email,
            'otp' => $otp,
        ])->assertOk();

        $this->postJson(route('student-login.create-password'), [
            'password' => 'secure-password',
            'password_confirmation' => 'secure-password',
        ])->assertOk()
            ->assertJsonPath('redirect', route('student.dashboard'));

        $student->refresh();

        $this->assertTrue(Hash::check('secure-password', $student->password));
        $this->assertDatabaseMissing('password_reset_otps', [
            'email' => $student->email,
            'used_at' => null,
        ]);
        $this->assertAuthenticatedAs($student, 'student');
    }
}
