<?php

namespace Tests\Feature;

use App\Mail\BranchLoginOtpMail;
use App\Mail\StudentLoginOtpMail;
use App\Mail\SuperAdminLoginOtpMail;
use App\Models\Branch;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class SingleActiveSessionTest extends TestCase
{
    use RefreshDatabase;

    private const KICKED_MESSAGE = 'Your account was logged in from another device, so this session has been logged out.';

    public function test_student_login_stores_a_session_token_on_the_account(): void
    {
        Mail::fake();

        $branch = Branch::create(['name' => 'Pune Branch', 'email' => 'pune@example.com']);

        $student = Student::create([
            'branch_id' => $branch->id,
            'student_name' => 'Student One',
            'guardian_name' => 'Guardian One',
            'class' => 'Class 10',
            'phone_number' => '9876543210',
            'email' => 'student@example.com',
            'login_code_hash' => Hash::make('654321'),
        ]);

        $this->assertNull($student->current_session_id);

        $this->post(route('login.store'), [
            'login_type' => 'student',
            'email' => 'student@example.com',
            'password' => '654321',
        ])->assertRedirect(route('login.otp'));

        $otp = null;
        Mail::assertSent(StudentLoginOtpMail::class, function (StudentLoginOtpMail $mail) use (&$otp): bool {
            $otp = $mail->otp;

            return true;
        });

        $this->post(route('login.otp.verify'), ['otp' => $otp])
            ->assertRedirect(route('student.dashboard'));

        $this->assertNotNull($student->fresh()->current_session_id);
    }

    public function test_branch_login_stores_a_session_token_on_the_account(): void
    {
        Mail::fake();

        $branch = Branch::create(['name' => 'Nagpur Branch', 'email' => 'nagpur@example.com']);

        $user = User::create([
            'name' => $branch->name,
            'email' => $branch->email,
            'role' => 'Branch',
            'branch_id' => $branch->id,
            'password' => Hash::make('123456'),
        ]);

        $this->assertNull($user->current_session_id);

        $this->post(route('login.store'), [
            'login_type' => 'branch',
            'email' => 'nagpur@example.com',
            'password' => '123456',
        ])->assertRedirect(route('login.otp'));

        $otp = null;
        Mail::assertSent(BranchLoginOtpMail::class, function (BranchLoginOtpMail $mail) use (&$otp): bool {
            $otp = $mail->otp;

            return true;
        });

        $this->post(route('login.otp.verify'), ['otp' => $otp])
            ->assertRedirect(route('branch.dashboard'));

        $this->assertNotNull($user->fresh()->current_session_id);
    }

    public function test_super_admin_login_does_not_store_a_session_token(): void
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

        $otp = null;
        Mail::assertSent(SuperAdminLoginOtpMail::class, function (SuperAdminLoginOtpMail $mail) use (&$otp): bool {
            $otp = $mail->otp;

            return true;
        });

        $this->post(route('login.otp.verify'), ['otp' => $otp])
            ->assertRedirect(route('admin.dashboard'));

        $this->assertNull(User::where('email', 'admin@example.com')->first()->current_session_id);
    }

    public function test_student_session_with_a_stale_token_is_logged_out_on_its_next_request(): void
    {
        $branch = Branch::create(['name' => 'Pune Branch', 'email' => 'pune@example.com']);

        $student = Student::create([
            'branch_id' => $branch->id,
            'student_name' => 'Student One',
            'guardian_name' => 'Guardian One',
            'class' => 'Class 10',
            'phone_number' => '9876543210',
            'email' => 'student@example.com',
            'login_code_hash' => Hash::make('654321'),
            'is_active' => true,
        ]);

        // Device A's session token still matches the account's active token.
        $student->forceFill(['current_session_id' => 'device-a-token'])->save();

        $this->actingAs($student, 'student')
            ->withSession(['single_session_token_student' => 'device-a-token'])
            ->get(route('student.dashboard'))
            ->assertOk();

        // Device B logs in and becomes the account's active session.
        $student->forceFill(['current_session_id' => 'device-b-token'])->save();

        // Device A makes its next request still carrying its old (now stale) token.
        $this->actingAs($student, 'student')
            ->withSession(['single_session_token_student' => 'device-a-token'])
            ->get(route('student.dashboard'))
            ->assertRedirect(route('login'))
            ->assertSessionHas('login_error', self::KICKED_MESSAGE);

        $this->assertGuest('student');
    }

    public function test_branch_session_with_a_stale_token_is_logged_out_on_its_next_request(): void
    {
        $branch = Branch::create(['name' => 'Nagpur Branch', 'email' => 'nagpur@example.com']);

        $user = User::create([
            'name' => $branch->name,
            'email' => $branch->email,
            'role' => 'Branch',
            'branch_id' => $branch->id,
            'password' => Hash::make('123456'),
        ]);

        $user->forceFill(['current_session_id' => 'device-a-token'])->save();

        $this->actingAs($user)
            ->withSession(['single_session_token_web' => 'device-a-token'])
            ->get(route('branch.dashboard'))
            ->assertOk();

        $user->forceFill(['current_session_id' => 'device-b-token'])->save();

        $this->actingAs($user)
            ->withSession(['single_session_token_web' => 'device-a-token'])
            ->get(route('branch.dashboard'))
            ->assertRedirect(route('login'))
            ->assertSessionHas('login_error', self::KICKED_MESSAGE);

        $this->assertGuest('web');
    }

    public function test_super_admin_is_not_restricted_to_a_single_session(): void
    {
        $admin = User::create([
            'name' => 'Super Admin',
            'email' => 'admin@example.com',
            'role' => 'Super Admin',
            'password' => Hash::make('123456'),
        ]);

        // A Super Admin's token is never set, and the admin routes don't
        // carry the single_session middleware at all — confirm access still
        // works regardless of any (irrelevant) stored session token state.
        $this->actingAs($admin)
            ->withSession(['single_session_token_web' => 'anything'])
            ->get(route('admin.dashboard'))
            ->assertOk();
    }

    public function test_a_session_that_never_recorded_a_token_is_left_alone(): void
    {
        // Covers programmatic auth (e.g. actingAs in other tests) that never
        // goes through the login flow at all: current_session_id and the
        // session key are both empty, so the request must not be kicked out.
        $branch = Branch::create(['name' => 'Pune Branch', 'email' => 'pune@example.com']);

        $student = Student::create([
            'branch_id' => $branch->id,
            'student_name' => 'Student One',
            'guardian_name' => 'Guardian One',
            'class' => 'Class 10',
            'phone_number' => '9876543210',
            'email' => 'student@example.com',
            'login_code_hash' => Hash::make('654321'),
            'is_active' => true,
        ]);

        $this->assertNull($student->current_session_id);

        $this->actingAs($student, 'student')
            ->get(route('student.dashboard'))
            ->assertOk();
    }
}
