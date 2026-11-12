<?php

namespace Tests\Feature;

use App\Mail\BranchCredentialsMail;
use App\Mail\BranchLoginOtpMail;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class BranchUserIdLoginTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'name' => 'Super Admin',
            'email' => 'admin@example.com',
            'role' => 'Super Admin',
            'password' => Hash::make('secret123'),
        ]);
    }

    public function test_two_branches_can_share_one_email_with_different_user_ids(): void
    {
        Mail::fake();

        $this->actingAs($this->admin)->post(route('admin.branches.store'), [
            'name' => 'Kellyville',
            'username' => 'Kellyville',
            'email' => 'owner@example.com',
        ])->assertSessionHasNoErrors();

        $this->actingAs($this->admin)->post(route('admin.branches.store'), [
            'name' => 'Ringwood',
            'username' => 'ringwood',
            'email' => 'owner@example.com',
        ])->assertSessionHasNoErrors();

        $this->assertSame(2, Branch::where('email', 'owner@example.com')->count());
        // Stored lowercase.
        $this->assertSame('kellyville', Branch::where('name', 'Kellyville')->first()->user->username);
        $this->assertSame('ringwood', Branch::where('name', 'Ringwood')->first()->user->username);

        Mail::assertSent(BranchCredentialsMail::class, fn (BranchCredentialsMail $mail) => $mail->username === 'ringwood');
    }

    public function test_duplicate_user_id_is_rejected(): void
    {
        $this->makeBranch('Kellyville', 'kellyville', 'a@example.com');

        $this->actingAs($this->admin)->post(route('admin.branches.store'), [
            'name' => 'Another',
            'username' => 'KELLYVILLE',
            'email' => 'b@example.com',
        ])->assertSessionHasErrors(['username' => 'This User ID is already taken. Please choose another one.']);

        $this->assertSame(1, Branch::count());
    }

    public function test_user_id_with_spaces_is_rejected(): void
    {
        $this->actingAs($this->admin)->post(route('admin.branches.store'), [
            'name' => 'Spaced',
            'username' => 'my branch',
            'email' => 'b@example.com',
        ])->assertSessionHasErrors('username');
    }

    public function test_live_check_reports_availability_and_ignores_the_branch_being_edited(): void
    {
        $branch = $this->makeBranch('Kellyville', 'kellyville', 'a@example.com');

        $this->actingAs($this->admin)
            ->getJson(route('admin.branches.check-username', ['username' => 'Kellyville']))
            ->assertOk()
            ->assertJson(['available' => false, 'message' => 'This User ID is already taken. Please choose another one.']);

        $this->actingAs($this->admin)
            ->getJson(route('admin.branches.check-username', ['username' => 'new_branch']))
            ->assertJson(['available' => true]);

        $this->actingAs($this->admin)
            ->getJson(route('admin.branches.check-username', ['username' => 'kellyville', 'ignore' => $branch->id]))
            ->assertJson(['available' => true]);
    }

    public function test_branch_users_cannot_use_the_live_check(): void
    {
        $branch = $this->makeBranch('Kellyville', 'kellyville', 'a@example.com');

        $this->actingAs($branch->user)
            ->getJson(route('admin.branches.check-username', ['username' => 'x']))
            ->assertForbidden();
    }

    public function test_edit_can_change_the_user_id(): void
    {
        $branch = $this->makeBranch('Kellyville', 'kellyville', 'a@example.com');

        $this->actingAs($this->admin)->put(route('admin.branches.update', $branch), [
            'name' => 'Kellyville',
            'username' => 'kv_branch',
            'email' => 'a@example.com',
        ])->assertSessionHasNoErrors();

        $this->assertSame('kv_branch', $branch->fresh()->user->username);
    }

    public function test_branch_logs_in_with_user_id_into_the_right_branch_when_email_is_shared(): void
    {
        Mail::fake();

        $this->makeBranch('Kellyville', 'kellyville', 'owner@example.com', 'pass-one');
        $ringwood = $this->makeBranch('Ringwood', 'ringwood', 'owner@example.com', 'pass-two');

        $this->post(route('login.store'), [
            'login_type' => 'branch',
            'username' => 'Ringwood',
            'password' => 'pass-two',
        ])->assertRedirect(route('login.otp'));

        $otp = null;
        Mail::assertSent(BranchLoginOtpMail::class, function (BranchLoginOtpMail $mail) use (&$otp): bool {
            $otp = $mail->otp;

            return $mail->hasTo('owner@example.com') && $mail->branch->name === 'Ringwood';
        });

        $this->post(route('login.otp.verify'), ['otp' => $otp])->assertRedirect(route('branch.dashboard'));

        $this->assertAuthenticatedAs($ringwood->user);
    }

    public function test_branch_login_with_wrong_password_fails(): void
    {
        $this->makeBranch('Kellyville', 'kellyville', 'a@example.com', 'right-pass');

        $this->post(route('login.store'), [
            'login_type' => 'branch',
            'username' => 'kellyville',
            'password' => 'wrong-pass',
        ])->assertSessionHas('login_error', 'Invalid User ID or password. Please check your credentials and try again.');
    }

    public function test_branch_login_requires_the_user_id(): void
    {
        $this->post(route('login.store'), [
            'login_type' => 'branch',
            'email' => 'a@example.com',
            'password' => 'whatever',
        ])->assertSessionHasErrors(['username' => 'User ID is required.']);
    }

    private function makeBranch(string $name, string $username, string $email, string $password = 'secret123'): Branch
    {
        $branch = Branch::create(['name' => $name, 'email' => $email, 'is_active' => true]);

        User::create([
            'name' => $name,
            'username' => $username,
            'email' => $email,
            'role' => 'Branch',
            'branch_id' => $branch->id,
            'password' => Hash::make($password),
        ]);

        return $branch->load('user');
    }
}
