<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use App\Services\ExamAttemptService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ExamFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_can_resume_existing_in_progress_attempt_when_attempt_limit_is_one(): void
    {
        [$branch, $class, $student, $exam] = $this->makeExamFixture();

        $attempt = ExamAttempt::create([
            'exam_id' => $exam->id,
            'student_id' => $student->id,
            'branch_id' => $branch->id,
            'school_class_id' => $class->id,
            'attempt_number' => 1,
            'started_at' => now()->subMinute(),
            'expires_at' => now()->addMinutes(20),
            'status' => 'in_progress',
        ]);

        $resumed = app(ExamAttemptService::class)->start($exam, $student);

        $this->assertTrue($attempt->is($resumed));
        $this->assertSame(1, ExamAttempt::where('exam_id', $exam->id)->where('student_id', $student->id)->count());
    }

    public function test_attempt_expiry_does_not_exceed_exam_end_time(): void
    {
        [, , $student, $exam] = $this->makeExamFixture([
            'duration_minutes' => 60,
            'ends_at' => now()->addMinutes(10),
        ]);

        $attempt = app(ExamAttemptService::class)->start($exam, $student);

        $this->assertTrue($attempt->expires_at->lessThanOrEqualTo($exam->ends_at));
    }

    public function test_branch_result_search_stays_scoped_to_authenticated_branch(): void
    {
        [$branch, $class, $student, $exam] = $this->makeExamFixture(['title' => 'Algebra Basics']);
        [$otherBranch, $otherClass, $otherStudent, $otherExam] = $this->makeExamFixture([
            'title' => 'Secret Physics',
        ], [
            'branch_name' => 'Other Branch',
            'branch_email' => 'other@example.com',
            'class_name' => 'Class 9',
            'student_email' => 'other-student@example.com',
        ]);

        $ownAttempt = $this->makeSubmittedAttempt($exam, $student, $branch, $class);
        $this->makeSubmittedAttempt($otherExam, $otherStudent, $otherBranch, $otherClass);

        $branchUser = User::create([
            'name' => 'Branch User',
            'email' => 'branch-user@example.com',
            'role' => 'Branch',
            'branch_id' => $branch->id,
            'password' => Hash::make('123456'),
        ]);

        $response = $this->actingAs($branchUser)->get(route('branch.results.index', ['search' => 'Secret']));

        $response->assertOk();
        $response->assertDontSee($otherStudent->student_name);
        $response->assertDontSee($otherExam->title);
        $response->assertDontSee($ownAttempt->student->student_name);
    }

    private function makeExamFixture(array $examOverrides = [], array $fixtureOverrides = []): array
    {
        $branch = Branch::create([
            'name' => $fixtureOverrides['branch_name'] ?? 'Main Branch',
            'email' => $fixtureOverrides['branch_email'] ?? 'main@example.com',
        ]);

        $class = SchoolClass::create([
            'branch_id' => $branch->id,
            'name' => $fixtureOverrides['class_name'] ?? 'Class 10',
        ]);

        $student = Student::create([
            'branch_id' => $branch->id,
            'class_id' => $class->id,
            'student_name' => $fixtureOverrides['student_name'] ?? 'Test Student',
            'guardian_name' => 'Test Guardian',
            'class' => $class->name,
            'phone_number' => $fixtureOverrides['student_phone'] ?? '9876543210',
            'email' => $fixtureOverrides['student_email'] ?? 'student@example.com',
        ]);

        $exam = Exam::create(array_merge([
            'branch_id' => $branch->id,
            'school_class_id' => $class->id,
            'title' => 'Algebra Basics',
            'total_marks' => 10,
            'duration_minutes' => 30,
            'starts_at' => now()->subMinute(),
            'ends_at' => now()->addHour(),
            'passing_marks' => 5,
            'maximum_attempts' => 1,
            'status' => Exam::STATUS_PUBLISHED,
        ], $examOverrides));

        $question = Question::create([
            'exam_id' => $exam->id,
            'question_text' => '2 + 2 = ?',
            'question_type' => 'mcq',
            'marks' => 10,
        ]);

        QuestionOption::create([
            'question_id' => $question->id,
            'option_text' => '4',
            'is_correct' => true,
            'position' => 0,
        ]);

        QuestionOption::create([
            'question_id' => $question->id,
            'option_text' => '5',
            'is_correct' => false,
            'position' => 1,
        ]);

        return [$branch, $class, $student, $exam];
    }

    private function makeSubmittedAttempt(Exam $exam, Student $student, Branch $branch, SchoolClass $class): ExamAttempt
    {
        return ExamAttempt::create([
            'exam_id' => $exam->id,
            'student_id' => $student->id,
            'branch_id' => $branch->id,
            'school_class_id' => $class->id,
            'attempt_number' => 1,
            'started_at' => now()->subMinutes(15),
            'expires_at' => now()->addMinutes(15),
            'submitted_at' => now(),
            'obtained_marks' => 8,
            'percentage' => 80,
            'correct_count' => 1,
            'wrong_count' => 0,
            'unanswered_count' => 0,
            'is_passed' => true,
            'status' => 'submitted',
        ]);
    }
}
