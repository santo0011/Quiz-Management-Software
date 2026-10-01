<?php

namespace Tests\Feature;

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

    /**
     * Super Admin manages every branch at once — Exams, Questions, Results,
     * and Students all show data across every branch directly, with no
     * branch-selection step required first (that gate was removed).
     */
    public function test_super_admin_can_open_branch_modules_without_selecting_a_branch(): void
    {
        $admin = $this->makeSuperAdmin();

        foreach (['admin.exams.index', 'admin.questions.index', 'admin.results.index', 'admin.students.index', 'admin.students.create'] as $route) {
            $this->actingAs($admin)
                ->get(route($route))
                ->assertOk();
        }
    }

    /**
     * The optional ?branch_id= filter still narrows Students to one branch
     * when a Super Admin wants that, without being required to see anything.
     */
    public function test_super_admin_can_filter_students_by_branch(): void
    {
        $branch = Branch::create(['name' => 'Kolkata Branch', 'email' => 'kolkata@example.com']);
        $admin = $this->makeSuperAdmin();

        $this->actingAs($admin)
            ->get(route('admin.students.index', ['branch_id' => $branch->id]))
            ->assertOk();
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

}
