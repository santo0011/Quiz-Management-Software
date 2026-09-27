<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Exam;
use App\Models\ExamAnswer;
use App\Models\ExamAttempt;
use App\Models\Question;
use App\Models\SchoolClass;
use App\Models\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NegativeMarksTotalTest extends TestCase
{
    use RefreshDatabase;

    public function test_total_sums_only_the_negative_per_answer_marks(): void
    {
        $attempt = $this->makeAttempt(['negative_marking_enabled' => true, 'negative_marks' => 0.25], [2, -0.25, -0.25, 0, -0.25]);

        $this->assertSame(0.75, $attempt->negativeMarksTotal());
        $this->assertSame('-0.75', $attempt->negativeMarksLabel());
        $this->assertTrue($attempt->showsNegativeMarks());
    }

    public function test_enabled_exam_without_wrong_answers_shows_zero(): void
    {
        $attempt = $this->makeAttempt(['negative_marking_enabled' => true, 'negative_marks' => 0.5], [2, 0]);

        $this->assertSame('0.00', $attempt->negativeMarksLabel());
        $this->assertTrue($attempt->showsNegativeMarks());
    }

    public function test_hidden_when_exam_has_no_negative_marking(): void
    {
        $attempt = $this->makeAttempt(['negative_marking_enabled' => false], [2, 0]);

        $this->assertSame(0.0, $attempt->negativeMarksTotal());
        $this->assertFalse($attempt->showsNegativeMarks());
    }

    /**
     * @param  array<int, float|int>  $marksAwarded  one answer per entry
     */
    private function makeAttempt(array $examOverrides, array $marksAwarded): ExamAttempt
    {
        $branch = Branch::create(['name' => 'Main Branch', 'email' => 'main@example.com']);
        $class = SchoolClass::create(['branch_id' => $branch->id, 'name' => 'Class 10']);
        $student = Student::create([
            'branch_id' => $branch->id,
            'class_id' => $class->id,
            'student_name' => 'Test Student',
            'guardian_name' => 'Test Guardian',
            'class' => $class->name,
            'phone_number' => '9876543210',
            'email' => 'student@example.com',
            'is_active' => true,
        ]);

        $exam = Exam::create(array_merge([
            'branch_id' => $branch->id,
            'school_class_id' => $class->id,
            'title' => 'Algebra Basics',
            'total_marks' => 10,
            'duration_minutes' => 30,
            'starts_at' => now()->subMinute(),
            'ends_at' => now()->addHour(),
            'maximum_attempts' => 1,
            'status' => Exam::STATUS_PUBLISHED,
        ], $examOverrides));

        $attempt = ExamAttempt::create([
            'exam_id' => $exam->id,
            'student_id' => $student->id,
            'branch_id' => $branch->id,
            'school_class_id' => $class->id,
            'attempt_number' => 1,
            'started_at' => now()->subMinutes(15),
            'expires_at' => now()->addMinutes(15),
            'submitted_at' => now(),
            'status' => 'submitted',
        ]);

        foreach ($marksAwarded as $index => $marks) {
            $question = Question::create([
                'exam_id' => $exam->id,
                'question_text' => 'Question '.($index + 1),
                'question_type' => 'mcq',
                'marks' => 2,
            ]);

            ExamAnswer::create([
                'exam_attempt_id' => $attempt->id,
                'question_id' => $question->id,
                'marks_awarded' => $marks,
            ]);
        }

        return $attempt->fresh();
    }
}
