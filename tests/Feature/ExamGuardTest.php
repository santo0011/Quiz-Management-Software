<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Exam;
use App\Models\Question;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Services\ExamAttemptService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExamGuardTest extends TestCase
{
    use RefreshDatabase;

    public function test_exam_screen_includes_the_lock_down_guard(): void
    {
        [$student, $exam] = $this->fixture();
        $attempt = app(ExamAttemptService::class)->start($exam, $student);

        $this->actingAs($student, 'student')->get(route('student.attempts.show', $attempt))
            ->assertOk()
            ->assertSee('id="examLeaveModal"', false)
            ->assertSee('id="examTabWarningModal"', false)
            ->assertSee('id="examSubmittedModal"', false)
            ->assertSee('your exam will end and be submitted now', false)
            ->assertSee("classList.add('sidebar-collapsed', 'exam-in-progress')", false);
    }

    public function test_other_student_pages_are_not_locked(): void
    {
        [$student] = $this->fixture();

        $this->actingAs($student, 'student')->get(route('student.dashboard'))
            ->assertOk()
            ->assertDontSee('id="examLeaveModal"', false)
            ->assertDontSee('exam-in-progress', false);
    }

    private function fixture(): array
    {
        $branch = Branch::create(['name' => 'Main Branch', 'email' => 'main@example.com']);
        $class = SchoolClass::create(['branch_id' => $branch->id, 'name' => 'Class 10']);

        $student = Student::create([
            'branch_id' => $branch->id,
            'class_id' => $class->id,
            'student_name' => 'Test Student',
            'guardian_name' => 'Guardian',
            'class' => $class->name,
            'phone_number' => '9876543210',
            'email' => 'guard-student@example.com',
            'is_active' => true,
        ]);

        $exam = Exam::create([
            'branch_id' => $branch->id,
            'school_class_id' => $class->id,
            'title' => 'Guard Test Exam',
            'total_marks' => 10,
            'duration_minutes' => 10,
            'starts_at' => now()->subMinute(),
            'ends_at' => now()->addDay(),
            'maximum_attempts' => 1,
            'status' => Exam::STATUS_PUBLISHED,
        ]);

        Question::create([
            'exam_id' => $exam->id,
            'question_text' => '2 + 2 = ?',
            'question_type' => 'mcq',
            'marks' => 10,
        ])->options()->createMany([
            ['option_text' => '4', 'is_correct' => true, 'position' => 0],
            ['option_text' => '5', 'is_correct' => false, 'position' => 1],
        ]);

        return [$student, $exam];
    }
}
