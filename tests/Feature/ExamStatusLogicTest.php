<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\SchoolClass;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExamStatusLogicTest extends TestCase
{
    use RefreshDatabase;

    private Branch $branch;
    private SchoolClass $schoolClass;
    private Student $student;

    protected function setUp(): void
    {
        parent::setUp();

        $this->branch = Branch::create([
            'name' => 'Kolkata Branch',
            'email' => 'branch@example.com',
        ]);

        $this->schoolClass = SchoolClass::create([
            'branch_id' => $this->branch->id,
            'name' => 'Class 10',
        ]);

        $this->student = Student::create([
            'branch_id' => $this->branch->id,
            'class_id' => $this->schoolClass->id,
            'student_name' => 'Rahul Kumar',
            'guardian_name' => 'Guardian',
            'class' => 'Class 10',
            'phone_number' => '9876543210',
            'email' => 'student@example.com',
            'is_active' => true,
        ]);
    }

    private function createExam(array $overrides = []): Exam
    {
        return Exam::create(array_merge([
            'branch_id' => $this->branch->id,
            'school_class_id' => $this->schoolClass->id,
            'title' => 'Math Test',
            'total_marks' => 100,
            'marks_per_question' => 1,
            'duration_minutes' => 60,
            'passing_marks' => 40,
            'maximum_attempts' => 1,
            'status' => Exam::STATUS_PUBLISHED,
        ], $overrides));
    }

    public function test_exam_with_future_start_time_is_upcoming(): void
    {
        $exam = $this->createExam([
            'starts_at' => Carbon::now()->addDay(),
            'ends_at' => Carbon::now()->addDays(2),
        ]);

        $this->assertEquals('upcoming', $exam->dynamicStatus($this->student));
        $this->assertFalse($exam->isOpen());
    }

    public function test_exam_within_time_window_is_available(): void
    {
        $exam = $this->createExam([
            'starts_at' => Carbon::now()->subHour(),
            'ends_at' => Carbon::now()->addHour(),
        ]);

        $this->assertEquals('available', $exam->dynamicStatus($this->student));
        $this->assertTrue($exam->isOpen());
    }

    public function test_exam_with_passed_end_time_is_expired(): void
    {
        $exam = $this->createExam([
            'starts_at' => Carbon::now()->subDays(2),
            'ends_at' => Carbon::now()->subHour(),
        ]);

        $this->assertEquals('expired', $exam->dynamicStatus($this->student));
        $this->assertFalse($exam->isOpen());
    }

    public function test_exam_with_submitted_attempt_is_completed(): void
    {
        $exam = $this->createExam([
            'starts_at' => Carbon::now()->subHour(),
            'ends_at' => null,
            'starts_at' => null,
        ]);

        $exam->update([
            'starts_at' => Carbon::now()->subHour(),
            'starts_at' => Carbon::now()->subHour(),
        ]);

        ExamAttempt::create([
            'student_id' => $this->student->id,
            'student_id' => $this->student->id,
        ]);
    }
