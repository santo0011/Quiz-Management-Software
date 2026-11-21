<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\SchoolClass;
use App\Models\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentTutorialsTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_sees_the_student_tutorial_video_and_sidebar_link(): void
    {
        $student = $this->student();

        $this->actingAs($student, 'student')->get(route('student.tutorials'))
            ->assertOk()
            ->assertSee('Student Panel: login, dashboard and taking an exam')
            ->assertSee(asset('videos/tutorials/student-panel.mp4'), false)
            ->assertDontSee('super-admin-complete-demo.mp4');

        $this->actingAs($student, 'student')->get(route('student.dashboard'))
            ->assertSee(route('student.tutorials'), false);
    }

    public function test_guest_is_sent_to_login(): void
    {
        $this->get(route('student.tutorials'))->assertRedirect();
    }

    private function student(): Student
    {
        $branch = Branch::create(['name' => 'Ringwood', 'email' => 'ringwood@example.com', 'is_active' => true]);
        $class = SchoolClass::create(['branch_id' => null, 'name' => 'Grade 3']);

        return Student::create([
            'branch_id' => $branch->id,
            'class_id' => $class->id,
            'student_name' => 'Test Student',
            'guardian_name' => 'Guardian',
            'class' => $class->name,
            'phone_number' => '',
            'email' => 'student@example.com',
            'is_active' => true,
        ]);
    }
}
