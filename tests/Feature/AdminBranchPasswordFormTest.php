<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminBranchPasswordFormTest extends TestCase
{
    use RefreshDatabase;

    public function test_mismatched_passwords_are_rejected_with_clear_message_and_branch_password_unchanged(): void
    {
        $admin = $this->makeAdmin();
        [$branch, $branchUser] = $this->makeBranch();

        $response = $this->actingAs($admin)->put(route('admin.branches.password.update', $branch), [
            '_drawer' => 'editBranchDrawer'.$branch->id,
            'password' => 'new-password-1',
            'password_confirmation' => 'different-password',
        ]);

        $response->assertSessionHasErrors(['password' => 'Passwords do not match.']);
        $response->assertSessionHas('_old_input._drawer', 'editBranchDrawer'.$branch->id);

        $this->assertTrue(Hash::check('secret123', $branchUser->fresh()->password));
    }

    public function test_matching_passwords_update_the_branch_password_and_flash_success(): void
    {
        $admin = $this->makeAdmin();
        [$branch, $branchUser] = $this->makeBranch();

        $response = $this->actingAs($admin)->put(route('admin.branches.password.update', $branch), [
            '_drawer' => 'editBranchDrawer'.$branch->id,
            'password' => 'new-password-1',
            'password_confirmation' => 'new-password-1',
        ]);

        $response->assertRedirect(route('admin.branches.index'));
        $response->assertSessionHas('success', 'Branch password updated successfully.');
        $response->assertSessionMissing('_old_input._drawer');

        $this->assertTrue(Hash::check('new-password-1', $branchUser->fresh()->password));
    }

    private function makeAdmin(): User
    {
        return User::create([
            'name' => 'Super Admin',
            'email' => 'admin-'.uniqid().'@example.com',
            'role' => 'Super Admin',
            'password' => Hash::make('secret123'),
        ]);
    }

    private function makeBranch(): array
    {
        $branch = Branch::create(['name' => 'Branch '.uniqid(), 'email' => 'branch-'.uniqid().'@example.com', 'is_active' => true]);
        $branchUser = User::create([
            'name' => $branch->name,
            'email' => $branch->email,
            'role' => 'Branch',
            'branch_id' => $branch->id,
            'password' => Hash::make('secret123'),
        ]);

        return [$branch, $branchUser];
    }
}
