<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class BranchTutorialsTest extends TestCase
{
    use RefreshDatabase;

    public function test_branch_sees_only_the_branch_tutorial_video_and_sidebar_link(): void
    {
        $branch = Branch::create(['name' => 'Ringwood', 'email' => 'ringwood@example.com', 'is_active' => true]);
        $user = User::create([
            'name' => 'Ringwood',
            'username' => 'ringwood',
            'email' => 'ringwood@example.com',
            'role' => 'Branch',
            'branch_id' => $branch->id,
            'password' => Hash::make('secret123'),
        ]);

        $this->actingAs($user)->get(route('branch.tutorials'))
            ->assertOk()
            ->assertSee('Branch Panel: login, teachers, exams and results')
            ->assertSee(asset('videos/tutorials/branch-panel.mp4'), false)
            ->assertDontSee('student-panel.mp4')
            ->assertDontSee('super-admin-complete-demo.mp4');

        $this->actingAs($user)->get(route('branch.dashboard'))
            ->assertSee(route('branch.tutorials'), false);
    }

    public function test_super_admin_is_kept_out_of_the_branch_page(): void
    {
        $admin = User::create([
            'name' => 'Super Admin',
            'email' => 'admin@example.com',
            'role' => 'Super Admin',
            'password' => Hash::make('secret123'),
        ]);

        $this->actingAs($admin)->get(route('branch.tutorials'))->assertRedirect();
    }
}
