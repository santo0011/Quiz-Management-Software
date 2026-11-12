<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminTutorialsTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_sees_the_tutorials_page_with_the_super_admin_video(): void
    {
        $admin = User::create([
            'name' => 'Super Admin',
            'email' => 'admin@example.com',
            'role' => 'Super Admin',
            'password' => Hash::make('secret123'),
        ]);

        $this->actingAs($admin)->get(route('admin.tutorials'))
            ->assertOk()
            ->assertSee('Super Admin Panel: complete demo')
            ->assertSee(asset('videos/tutorials/super-admin-complete-demo.mp4'), false)
            ->assertSee('Branch videos are coming soon.')
            ->assertSee('Teacher videos are coming soon.')
            ->assertSee('Student Panel: login, dashboard and taking an exam');

        $this->actingAs($admin)->get(route('admin.dashboard'))
            ->assertSee(route('admin.tutorials'), false);
    }

    public function test_configured_video_files_exist(): void
    {
        foreach (config('tutorials.panels') as $panel) {
            foreach ($panel['videos'] as $video) {
                $this->assertFileExists(public_path($video['file']));
                if (! empty($video['poster'])) {
                    $this->assertFileExists(public_path($video['poster']));
                }
            }
        }
    }

    public function test_branch_user_cannot_open_the_tutorials_page(): void
    {
        $branch = Branch::create(['name' => 'Main', 'email' => 'main@example.com']);
        $user = User::create([
            'name' => 'Main',
            'username' => 'main',
            'email' => 'main@example.com',
            'role' => 'Branch',
            'branch_id' => $branch->id,
            'password' => Hash::make('secret123'),
        ]);

        $this->actingAs($user)->get(route('admin.tutorials'))->assertRedirect();
    }
}
