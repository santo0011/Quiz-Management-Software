<?php

namespace Tests\Feature;

use App\Console\Commands\SubmitExpiredExamAttempts;
use App\Models\AcademicSession;
use App\Models\Branch;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Services\ExamAttemptService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ExamAttemptResumeTest extends TestCase
{
    use RefreshDatabase;

    public function test_leaving_before_expiry_does_not_submit_the_attempt(): void
    {
        [$student, $exam] = $this->makeFixture(['duration_minutes' => 10]);

        $attempt = app(ExamAttemptService::class)->start($exam, $student);

        // Simulate the student closing the tab and the client checking state
        // again shortly after (well before the 10-minute timer runs out).
        $response = $this->actingAs($student, 'student')
            ->getJson(route('student.attempts.state', $attempt));

        $response->assertOk();
        $response->assertJsonPath('attempt.status', 'in_progress');

        $attempt->refresh();
        $this->assertSame('in_progress', $attempt->status);
        $this->assertNull($attempt->submitted_at);
    }

    public function test_student_can_resume_and_previous_answers_are_preserved(): void
    {
        [$student, $exam, $question, $correctOption] = $this->makeFixtureWithQuestion();

        $service = app(ExamAttemptService::class);
        $attempt = $service->start($exam, $student);
        $service->saveAnswer($attempt, $student, $question->id, $correctOption->id);

        // "Leaving" the page is just not touching the attempt again; resuming
        // is calling start() again for the same exam.
        $resumed = $service->start($exam, $student);

        $this->assertTrue($attempt->is($resumed));
        $this->assertSame(1, ExamAttempt::where('exam_id', $exam->id)->where('student_id', $student->id)->count());

        $response = $this->actingAs($student, 'student')->getJson(route('student.attempts.state', $resumed));
        $response->assertOk();
        $response->assertJsonPath('items.0.question.selected_option_id', $correctOption->id);
    }

    public function test_timer_keeps_the_original_start_time_across_requests(): void
    {
        [$student, $exam] = $this->makeFixture(['duration_minutes' => 10]);

        $attempt = app(ExamAttemptService::class)->start($exam, $student);
        $originalExpiresAt = $attempt->expires_at->toIso8601String();

        $this->travel(3)->minutes();

        $response = $this->actingAs($student, 'student')->getJson(route('student.attempts.state', $attempt));
        $response->assertOk();
        $this->assertSame($originalExpiresAt, $response->json('attempt.expires_at'));
    }

    public function test_state_endpoint_auto_submits_once_time_expires(): void
    {
        [$student, $exam] = $this->makeFixture(['duration_minutes' => 10]);

        $attempt = app(ExamAttemptService::class)->start($exam, $student);

        $this->travel(11)->minutes();

        $response = $this->actingAs($student, 'student')->getJson(route('student.attempts.state', $attempt));
        $response->assertOk();
        $response->assertJsonPath('attempt.status', 'submitted');
        $response->assertJsonPath('attempt.result_url', route('student.results.show', $attempt));

        $attempt->refresh();
        $this->assertSame('submitted', $attempt->status);
        $this->assertNotNull($attempt->submitted_at);
    }

    public function test_scheduled_command_submits_expired_attempts_the_student_never_returned_to(): void
    {
        [$student, $exam] = $this->makeFixture(['duration_minutes' => 10]);

        $attempt = app(ExamAttemptService::class)->start($exam, $student);
        $this->travel(11)->minutes();

        $this->artisan(SubmitExpiredExamAttempts::class)->assertSuccessful();

        $attempt->refresh();
        $this->assertSame('submitted', $attempt->status);
        $this->assertNotNull($attempt->submitted_at);
    }

    public function test_starting_again_after_expiry_finalizes_the_stale_attempt_instead_of_leaving_it_orphaned(): void
    {
        [$student, $exam] = $this->makeFixture(['duration_minutes' => 10, 'maximum_attempts' => 2]);

        $stale = app(ExamAttemptService::class)->start($exam, $student);
        $this->travel(11)->minutes();

        // Student never touched the state endpoint before trying to start again.
        $fresh = app(ExamAttemptService::class)->start($exam, $student);

        $stale->refresh();
        $this->assertSame('submitted', $stale->status);
        $this->assertNotNull($stale->submitted_at);

        $this->assertFalse($fresh->is($stale));
        $this->assertSame('in_progress', $fresh->status);
        $this->assertSame(2, ExamAttempt::where('exam_id', $exam->id)->where('student_id', $student->id)->count());
    }

    public function test_cannot_start_a_new_attempt_while_one_is_already_active(): void
    {
        [$student, $exam] = $this->makeFixture(['duration_minutes' => 10, 'maximum_attempts' => 1]);

        $first = app(ExamAttemptService::class)->start($exam, $student);
        $second = app(ExamAttemptService::class)->start($exam, $student);

        $this->assertTrue($first->is($second));
        $this->assertSame(1, ExamAttempt::where('exam_id', $exam->id)->where('student_id', $student->id)->count());
    }

    public function test_active_attempt_does_not_block_the_students_own_remaining_attempts_count(): void
    {
        [$student, $exam] = $this->makeFixture(['duration_minutes' => 10, 'maximum_attempts' => 1]);

        app(ExamAttemptService::class)->start($exam, $student);

        // With the (buggy) old behavior this would be 0, hiding the exam
        // from "Available Exams" and disabling "Begin Exam" while the
        // student's own attempt is still actively running.
        $this->assertSame(1, $exam->remainingAttemptsFor($student));
    }

    public function test_expired_unsubmitted_attempt_counts_against_the_limit(): void
    {
        [$student, $exam] = $this->makeFixture(['duration_minutes' => 10, 'maximum_attempts' => 1]);

        app(ExamAttemptService::class)->start($exam, $student);
        $this->travel(11)->minutes();

        $this->assertSame(0, $exam->remainingAttemptsFor($student));
    }

    public function test_cannot_re_enter_after_submission_when_at_the_attempt_limit(): void
    {
        [$student, $exam] = $this->makeFixture(['duration_minutes' => 10, 'maximum_attempts' => 1]);

        $service = app(ExamAttemptService::class);
        $attempt = $service->start($exam, $student);
        $service->submit($attempt, $student);

        $this->expectException(ValidationException::class);
        $service->start($exam, $student);
    }

    public function test_exam_instructions_page_shows_continue_when_an_attempt_is_active(): void
    {
        [$student, $exam] = $this->makeFixture(['duration_minutes' => 10, 'maximum_attempts' => 1]);

        app(ExamAttemptService::class)->start($exam, $student);

        $response = $this->actingAs($student, 'student')->get(route('student.exams.show', $exam));

        $response->assertOk();
        $response->assertSee('Continue Exam');
    }

    private function makeFixture(array $examOverrides = []): array
    {
        $branch = Branch::create(['name' => 'Main Branch', 'email' => 'main@example.com']);
        $class = SchoolClass::create(['branch_id' => $branch->id, 'name' => 'Class 10']);
        $session = AcademicSession::create([
            'name' => '2026-2027',
            'start_date' => '2026-06-01',
            'end_date' => '2027-05-31',
            'is_active' => true,
        ]);

        $student = Student::create([
            'branch_id' => $branch->id,
            'class_id' => $class->id,
            'session_id' => $session->id,
            'student_name' => 'Test Student',
            'guardian_name' => 'Guardian',
            'class' => $class->name,
            'phone_number' => '9876543210',
            'email' => 'resume-student@example.com',
            'is_active' => true,
        ]);

        $exam = Exam::create(array_merge([
            'branch_id' => $branch->id,
            'school_class_id' => $class->id,
            'title' => 'Resume Test Exam',
            'total_marks' => 10,
            'duration_minutes' => 10,
            'starts_at' => now()->subMinute(),
            'ends_at' => now()->addDay(),
            'maximum_attempts' => 1,
            'status' => Exam::STATUS_PUBLISHED,
        ], $examOverrides));

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

    private function makeFixtureWithQuestion(): array
    {
        [$student, $exam] = $this->makeFixture();
        $question = $exam->questions()->first();
        $correctOption = $question->options()->where('is_correct', true)->first();

        return [$student, $exam, $question, $correctOption];
    }
}
