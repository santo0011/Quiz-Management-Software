<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminEmailChangeTest extends TestCase
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

    public function test_admin_can_view_email_section_on_settings_page(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.settings.edit'))
            ->assertOk()
            ->assertSee('Super Admin Email')
            ->assertSee($this->admin->email);
    }

    public function test_admin_can_update_email_with_correct_current_password(): void
    {
        $this->actingAs($this->admin)
            ->put(route('admin.account.email.update'), [
                'email' => 'new-admin@example.com',
                'current_password' => 'current-password',
            ])
            ->assertRedirect()
            ->assertSessionHas('success', 'Email address updated successfully.');

        $this->admin->refresh();
        $this->assertSame('new-admin@example.com', $this->admin->email);
    }

    public function test_admin_cannot_update_email_with_incorrect_current_password(): void
    {
        $this->actingAs($this->admin)
            ->put(route('admin.account.email.update'), [
                'email' => 'new-admin@example.com',
                'current_password' => 'wrong-password',
            ])
            ->assertSessionHasErrors(['current_password' => 'The current password is incorrect.']);

        $this->admin->refresh();
        $this->assertSame('admin@example.com', $this->admin->email);
    }

    public function test_admin_cannot_update_email_to_an_invalid_address(): void
    {
        $this->actingAs($this->admin)
            ->put(route('admin.account.email.update'), [
                'email' => 'not-an-email',
                'current_password' => 'current-password',
            ])
            ->assertSessionHasErrors(['email' => 'Please enter a valid email address.']);

        $this->admin->refresh();
        $this->assertSame('admin@example.com', $this->admin->email);
    }

    public function test_admin_cannot_update_email_to_one_already_in_use(): void
    {
        User::create([
            'name' => 'Other Admin',
            'email' => 'taken@example.com',
            'role' => 'Super Admin',
            'password' => Hash::make('123456'),
        ]);

        $this->actingAs($this->admin)
            ->put(route('admin.account.email.update'), [
                'email' => 'taken@example.com',
                'current_password' => 'current-password',
            ])
            ->assertSessionHasErrors(['email' => 'This email address is already in use.']);

        $this->admin->refresh();
        $this->assertSame('admin@example.com', $this->admin->email);
    }
}
