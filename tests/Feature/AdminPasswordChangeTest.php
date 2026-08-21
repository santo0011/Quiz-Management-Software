<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminPasswordChangeTest extends TestCase
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
            'password' => Hash::make('current-password'),
        ]);
    }

    public function test_admin_can_view_change_password_section_on_settings_page(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.settings.edit'))
            ->assertOk()
            ->assertSee('Current Password')
            ->assertSee('New Password')
            ->assertSee('Confirm New Password');
    }

    public function test_admin_can_change_password_with_correct_current_password(): void
    {
        $this->actingAs($this->admin)
            ->put(route('admin.password.update'), [
                'current_password' => 'current-password',
                'password' => 'new-secure-password',
                'password_confirmation' => 'new-secure-password',
            ])
            ->assertRedirect()
            ->assertSessionHas('success', 'Password changed successfully.');

        $this->admin->refresh();
        $this->assertTrue(Hash::check('new-secure-password', $this->admin->password));
    }

    public function test_admin_cannot_change_password_with_incorrect_current_password(): void
    {
        $this->actingAs($this->admin)
            ->put(route('admin.password.update'), [
                'current_password' => 'wrong-password',
                'password' => 'new-secure-password',
                'password_confirmation' => 'new-secure-password',
            ])
            ->assertSessionHasErrors(['current_password' => 'The current password is incorrect.']);

        $this->admin->refresh();
        $this->assertTrue(Hash::check('current-password', $this->admin->password));
    }

    public function test_admin_password_confirmation_must_match(): void
    {
        $this->actingAs($this->admin)
            ->put(route('admin.password.update'), [
                'current_password' => 'current-password',
                'password' => 'new-secure-password',
                'password_confirmation' => 'different-password',
            ])
            ->assertSessionHasErrors(['password' => 'Password confirmation does not match.']);

        $this->admin->refresh();
        $this->assertTrue(Hash::check('current-password', $this->admin->password));
    }

    public function test_admin_password_must_be_at_least_six_characters(): void
    {
        $this->actingAs($this->admin)
            ->put(route('admin.password.update'), [
                'current_password' => 'current-password',
                'password' => '123',
                'password_confirmation' => '123',
            ])
            ->assertSessionHasErrors(['password' => 'New password must be at least 6 characters.']);

        $this->admin->refresh();
        $this->assertTrue(Hash::check('current-password', $this->admin->password));
    }
}