<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * The Academic Session module (model, controllers, middleware, routes,
 * views, and database columns/table) has been removed from the application
 * entirely. These guard against it silently coming back or leaving dead
 * database structures behind.
 */
class AcademicSessionRemovalTest extends TestCase
{
    use RefreshDatabase;

    public function test_no_academic_session_routes_are_registered(): void
    {
        foreach (Route::getRoutes() as $route) {
            $name = $route->getName();

            $this->assertFalse(
                $name && str_contains($name, 'academic-session'),
                "Unexpected Academic Session route still registered: {$name}"
            );
        }
    }

    public function test_academic_sessions_table_no_longer_exists(): void
    {
        $this->assertFalse(Schema::hasTable('academic_sessions'));
    }

    public function test_exams_and_exam_attempts_no_longer_have_a_session_id_column(): void
    {
        $this->assertFalse(Schema::hasColumn('exams', 'session_id'));
        $this->assertFalse(Schema::hasColumn('exam_attempts', 'session_id'));
        $this->assertFalse(Schema::hasColumn('students', 'session_id'));
    }

    public function test_super_admin_sidebar_does_not_show_an_academic_sessions_link(): void
    {
        $admin = User::create([
            'name' => 'Super Admin',
            'email' => 'admin-'.uniqid().'@example.com',
            'role' => 'Super Admin',
            'password' => Hash::make('123456'),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertDontSee('Academic Sessions');
    }

    public function test_admin_exams_and_results_no_longer_prompt_to_select_a_session(): void
    {
        $admin = User::create([
            'name' => 'Super Admin',
            'email' => 'admin-'.uniqid().'@example.com',
            'role' => 'Super Admin',
            'password' => Hash::make('123456'),
        ]);
        $branch = Branch::create(['name' => 'Session Test Branch', 'email' => 'session-test-branch@example.com']);

        $this->actingAs($admin)
            ->get(route('admin.exams.index'))
            ->assertOk()
            ->assertDontSee('Select an academic session to continue');

        $this->actingAs($admin)
            ->get(route('admin.results.index'))
            ->assertOk()
            ->assertDontSee('Select an academic session to continue');
    }
}
