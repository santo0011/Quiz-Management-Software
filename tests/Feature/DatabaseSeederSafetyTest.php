<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Locks in that DatabaseSeeder can never overwrite live data — it's run with
 * --force as part of local setup, so it must be safe to re-run against a
 * database that already has real records without resetting anything.
 */
class DatabaseSeederSafetyTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_does_not_overwrite_an_existing_super_admin(): void
    {
        $admin = User::create([
            'name' => 'Real Admin',
            'email' => 'avsbera.gpt1@gmail.com',
            'role' => 'Super Admin',
            'password' => Hash::make('a-real-changed-password'),
        ]);

        $this->seed(DatabaseSeeder::class);

        $admin->refresh();

        $this->assertSame('Real Admin', $admin->name);
        $this->assertTrue(Hash::check('a-real-changed-password', $admin->password));
        $this->assertSame(1, User::where('email', 'avsbera.gpt1@gmail.com')->count());
    }

    public function test_seeder_creates_the_default_super_admin_when_none_exists(): void
    {
        $this->assertSame(0, User::count());

        $this->seed(DatabaseSeeder::class);

        $admin = User::where('email', 'avsbera.gpt1@gmail.com')->first();
        $this->assertNotNull($admin);
        $this->assertSame('Super Admin', $admin->role);
    }

    public function test_seeder_is_safe_to_run_twice(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(1, User::where('email', 'avsbera.gpt1@gmail.com')->count());
    }
}
