<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_redirects_to_login(): void
    {
        $response = $this->get('/');

        $response->assertRedirect(route('login'));
    }

    public function test_super_admin_can_access_dashboard(): void
    {
        $user = User::firstOrCreate([
            'email' => 'avsbera.gpt1@gmail.com',
        ], [
            'name' => 'Super Admin',
            'role' => 'Super Admin',
            'password' => Hash::make('123456'),
        ]);

        $response = $this->actingAs($user)->get(route('admin.dashboard'));

        $response->assertOk();
    }
}
