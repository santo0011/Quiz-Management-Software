<?php

namespace Tests\Feature;

use App\Mail\BranchOtpMail;
use App\Mail\StudentOtpMail;
use App\Mail\SuperAdminOtpMail;
use App\Models\Branch;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ForgotPasswordTypeValidationTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;
    private User $branchUser;
    private Student $student;

    protected function setUp(): void
    {
        parent::setUp();

        // Super Admin account
        $this->superAdmin = User::create([
            'name' => 'Super Admin',
            'email' => 'admin@example.com',
            'role' => 'Super Admin',
            'password' => Hash::make('123456'),
        ]);

        // Branch account (with matching Branch model)
        $branch = Branch::create([
            'name' => 'Kolkata Branch',
            'email' => 'branch@example.com',
        ]);

        $this->branchUser = User::create([
            'name' => $branch->name,
            'email' => $branch->email,
            'role' => 'Branch',
            'branch_id' => $branch->id,
            'password' => Hash::make('123456'),
        ]);

        // Student account
        $this->student = Student::create([
            'branch_id' => $branch->id,
            'student_name' => 'Rahul Kumar',
            'guardian_name' => 'Guardian',
            'class' => 'Class 10',
            'phone_number' => '9876543210',
            'email' => 'student@example.com',
        ]);
    }

    public function test_super_admin_email_with_super_admin_type_succeeds(): void
    {
        Mail::fake();

        $this->post(route('password.email'), [
            'type' => 'super_admin',
            'email' => $this->superAdmin->email,
        ])->assertRedirect(route('password.otp', ['type' => 'super_admin']));

        Mail::assertSent(SuperAdminOtpMail::class, 1);
        $this->assertDatabaseHas('password_reset_otps', [
            'email' => $this->superAdmin->email,
            'user_type' => 'super_admin',
            'used_at' => null,
        ]);
    }

    public function test_branch_email_with_branch_type_succeeds_and_sends_branch_otp_mail(): void
    {
        Mail::fake();

        $this->post(route('password.email'), [
            'type' => 'branch',
            'email' => $this->branchUser->email,
        ])->assertRedirect(route('password.otp', ['type' => 'branch']));

        Mail::assertSent(BranchOtpMail::class, function (BranchOtpMail $mail): bool {
            return $mail->branch->name === 'Kolkata Branch';
        });
        Mail::assertNotSent(SuperAdminOtpMail::class);
        $this->assertDatabaseHas('password_reset_otps', [
            'email' => $this->branchUser->email,
            'user_type' => 'branch',
            'used_at' => null,
        ]);
    }

    public function test_student_email_with_student_type_succeeds(): void
    {
        Mail::fake();

        $this->post(route('password.email'), [
            'type' => 'student',
            'email' => $this->student->email,
        ])->assertRedirect(route('password.otp', ['type' => 'student']));

        Mail::assertSent(StudentOtpMail::class, function (StudentOtpMail $mail): bool {
            return $mail->student?->student_name === 'Rahul Kumar';
        });
        $this->assertDatabaseHas('password_reset_otps', [
            'email' => $this->student->email,
            'user_type' => 'student',
            'used_at' => null,
        ]);
    }

    public function test_super_admin_email_with_branch_type_is_rejected(): void
    {
        Mail::fake();

        $this->post(route('password.email'), [
            'type' => 'branch',
            'email' => $this->superAdmin->email,
        ])->assertSessionHasErrors(['email' => 'No Branch account found with this email address.']);

        Mail::assertNothingSent();
        $this->assertDatabaseMissing('password_reset_otps', [
            'email' => $this->superAdmin->email,
            'user_type' => 'branch',
        ]);
    }

    public function test_super_admin_email_with_student_type_is_rejected(): void
    {
        Mail::fake();

        $this->post(route('password.email'), [
            'type' => 'student',
            'email' => $this->superAdmin->email,
        ])->assertSessionHasErrors(['email' => 'No Student account found with this email address.']);

        Mail::assertNothingSent();
        $this->assertDatabaseMissing('password_reset_otps', [
            'email' => $this->superAdmin->email,
            'user_type' => 'student',
        ]);
    }

    public function test_branch_email_with_super_admin_type_is_rejected(): void
    {
        Mail::fake();

        $this->post(route('password.email'), [
            'type' => 'super_admin',
            'email' => $this->branchUser->email,
        ])->assertSessionHasErrors(['email' => 'No Super Admin account found with this email address.']);

        Mail::assertNothingSent();
        $this->assertDatabaseMissing('password_reset_otps', [
            'email' => $this->branchUser->email,
            'user_type' => 'super_admin',
        ]);
    }

    public function test_branch_email_with_student_type_is_rejected(): void
    {
        Mail::fake();

        $this->post(route('password.email'), [
            'type' => 'student',
            'email' => $this->branchUser->email,
        ])->assertSessionHasErrors(['email' => 'No Student account found with this email address.']);

        Mail::assertNothingSent();
        $this->assertDatabaseMissing('password_reset_otps', [
            'email' => $this->branchUser->email,
            'user_type' => 'student',
        ]);
    }

    public function test_student_email_with_super_admin_type_is_rejected(): void
    {
        Mail::fake();

        $this->post(route('password.email'), [
            'type' => 'super_admin',
            'email' => $this->student->email,
        ])->assertSessionHasErrors(['email' => 'No Super Admin account found with this email address.']);

        Mail::assertNothingSent();
        $this->assertDatabaseMissing('password_reset_otps', [
            'email' => $this->student->email,
            'user_type' => 'super_admin',
        ]);
    }

    public function test_student_email_with_branch_type_is_rejected(): void
    {
        Mail::fake();

        $this->post(route('password.email'), [
            'type' => 'branch',
            'email' => $this->student->email,
        ])->assertSessionHasErrors(['email' => 'No Branch account found with this email address.']);

        Mail::assertNothingSent();
        $this->assertDatabaseMissing('password_reset_otps', [
            'email' => $this->student->email,
            'user_type' => 'branch',
        ]);
    }

    public function test_otp_verification_is_type_scoped(): void
    {
        Mail::fake();

        // Student requests an OTP
        $this->post(route('password.email'), [
            'type' => 'student',
            'email' => $this->student->email,
        ])->assertRedirect(route('password.otp', ['type' => 'student']));

        $record = DB::table('password_reset_otps')
            ->where('email', $this->student->email)
            ->where('user_type', 'student')
            ->first();

        $this->assertNotNull($record);

        // Try to verify the same OTP as branch type → should fail
        $this->post(route('password.otp.verify'), [
            'type' => 'branch',
            'email' => $this->student->email,
            'otp' => '000000',
        ])->assertSessionHas('reset_error');

        // Try to verify as super_admin type → should fail
        $this->post(route('password.otp.verify'), [
            'type' => 'super_admin',
            'email' => $this->student->email,
            'otp' => '000000',
        ])->assertSessionHas('reset_error');
    }
}