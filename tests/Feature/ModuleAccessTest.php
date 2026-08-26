<?php

namespace Tests\Feature;

use App\Models\AcademicSession;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ModuleAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_branch_user_can_open_branch_modules_without_selecting_branch(): void
    {
        [$branch, $branchUser] = $this->makeBranchUser();

        foreach (['branch.exams.index', 'branch.questions.index', 'branch.results.index'] as $route) {
            $this->actingAs($branchUser)
                ->get(route($route))
                ->assertOk()
                ->assertSee($branch->name);
        }
    }

    public function test_super_admin_branch_modules_require_selected_branch(): void
    {
        $admin = $this->makeSuperAdmin();

        foreach (['admin.exams.index', 'admin.questions.index', 'admin.results.index'] as $route) {
            $this->actingAs($admin)
                ->get(route($route))
                ->assertRedirect(route('admin.branch-selection.index'))
                ->assertSessionHas('success', 'Please select a branch first to manage branch-related data.');
        }
    }

    public function test_super_admin_can_open_branch_modules_after_selecting_branch(): void
    {
        $branch = Branch::create(['name' => 'Kolkata Branch', 'email' => 'kolkata@example.com']);
        $session = $this->makeAcademicSession();
        $admin = $this->makeSuperAdmin();

        foreach (['admin.exams.index', 'admin.questions.index', 'admin.results.index'] as $route) {
            $this->actingAs($admin)
                ->withSession([
                    'admin_selected_branch_id' => $branch->id,
                    'admin_selected_academic_session_id' => $session->id,
                ])
                ->get(route($route))
                ->assertOk()
                ->assertSee($branch->name);
        }
    }

    private function makeBranchUser(): array
    {
        $branch = Branch::create(['name' => 'Delhi Branch', 'email' => 'delhi@example.com']);

        $branchUser = User::create([
            'name' => $branch->name,
            'email' => $branch->email,
            'role' => 'Branch',
            'branch_id' => $branch->id,
            'password' => Hash::make('123456'),
        ]);

        return [$branch, $branchUser];
    }

    private function makeSuperAdmin(): User
    {
        return User::create([
            'name' => 'Super Admin',
            'email' => fake()->unique()->safeEmail(),
            'role' => 'Super Admin',
            'password' => Hash::make('123456'),
        ]);
    }

    private function makeAcademicSession(): AcademicSession
    {
        return AcademicSession::create([
            'name' => '2026-2027',
            'start_date' => '2026-06-01',
            'end_date' => '2027-05-31',
            'is_active' => true,
        ]);
    }
}
