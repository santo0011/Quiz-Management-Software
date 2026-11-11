<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminManualTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_open_the_user_manual(): void
    {
        $admin = User::create([
            'name' => 'Super Admin',
            'email' => 'admin@example.com',
            'role' => 'Super Admin',
            'password' => Hash::make('secret123'),
        ]);

        $this->actingAs($admin)->get(route('admin.manual'))
            ->assertOk()
            ->assertSee('Complete User Manual')
            ->assertSee('Teacher Override')
            ->assertSee('Branch panel')
            ->assertSee('Student panel');

        $this->actingAs($admin)->get(route('admin.dashboard'))
            ->assertSee(route('admin.manual'), false);
    }

    public function test_branch_user_cannot_open_the_user_manual(): void
    {
        $branch = Branch::create(['name' => 'Main', 'email' => 'main@example.com']);
        $user = User::create([
            'name' => 'Main Branch',
            'email' => 'main@example.com',
            'role' => 'Branch',
            'branch_id' => $branch->id,
            'password' => Hash::make('secret123'),
        ]);

        $this->actingAs($user)->get(route('admin.manual'))->assertRedirect();
    }
}
