<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Teacher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TeacherTutorialsTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_sees_only_the_teacher_tutorial_video_and_sidebar_link(): void
    {
        $branch = Branch::create(['name' => 'Ringwood', 'email' => 'ringwood@example.com', 'is_active' => true]);
        $teacher = Teacher::create([
            'branch_id' => $branch->id,
            'name' => 'Ringwood Teacher',
            'email' => 'teacher@example.com',
            'phone_number' => '111',
            'password' => Hash::make('secret123'),
        ]);

        $this->actingAs($teacher, 'teacher')->get(route('teacher.tutorials'))
            ->assertOk()
            ->assertSee('Teacher Panel: login, results and remarks')
            ->assertSee(asset('videos/tutorials/teacher-panel.mp4'), false)
            ->assertDontSee('branch-panel.mp4')
            ->assertDontSee('student-panel.mp4');

        $this->actingAs($teacher, 'teacher')->get(route('teacher.dashboard'))
            ->assertSee(route('teacher.tutorials'), false);
    }

    public function test_guest_is_sent_to_login(): void
    {
        $this->get(route('teacher.tutorials'))->assertRedirect();
    }
}
