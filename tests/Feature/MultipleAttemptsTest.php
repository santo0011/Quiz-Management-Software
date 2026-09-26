<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\Guardian;
use App\Models\Question;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use App\Services\ExamAttemptService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * Attempt Limit: a Student may take an Exam up to its limit, sees results
 * only once every attempt is done, and every attempt is kept and shown
 * separately (Student, Guardian, Branch and Super Admin).
 */
class MultipleAttemptsTest extends TestCase
{
    use RefreshDatabase;

    private Branch $branch;

    private SchoolClass $grade;

    private Subject $subject;

    private Student $student;

    private Question $question;

    protected function setUp(): void
    {
        parent::setUp();

        Http::fake();
        Storage::fake('public');

        $this->branch = Branch::create(['name' => 'North', 'email' => 'north@example.com', 'is_active' => true]);
        $this->grade = SchoolClass::create(['branch_id' => null, 'name' => 'Grade 5']);
        $this->subject = Subject::create(['name' => 'Maths']);

        $this->student = Student::create([
            'branch_id' => $this->branch->id,
            'class_id' => $this->grade->id,
            'student_name' => 'Asha',
            'guardian_name' => 'Parent',
            'guardian_email' => 'parent@example.com',
            'class' => 'Grade 5',
            'phone_number' => '9876543210',
            'email' => 'asha@example.com',
            'is_active' => true,
        ]);
        $this->student->subjects()->attach($this->subject->id);
    }

    // --- Exam creation ---

    public function test_exam_form_shows_attempt_limit_and_saves_it(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)->get(route('admin.exams.index'))->assertSee('Attempt Limit');

        $this->actingAs($admin)->post(route('admin.exams.store'), [
            'title' => 'Three Tries',
            'school_class_ids' => [$this->grade->id],
            'subject_id' => $this->subject->id,
            'starts_at' => now()->addDay()->format('Y-m-d H:i:s'),
            'ends_at' => now()->addDays(2)->format('Y-m-d H:i:s'),
            'duration_minutes' => 30,
            'maximum_attempts' => 3,
        ])->assertSessionDoesntHaveErrors();

        $this->assertSame(3, Exam::where('title', 'Three Tries')->value('maximum_attempts'));
    }

    // --- Limit enforcement ---

    public function test_student_can_take_up_to_the_limit_and_no_more(): void
    {
        $exam = $this->makeExam(3);

        foreach ([1, 2, 3] as $expected) {
            $attempt = $this->takeAttempt($exam, correct: false);
            $this->assertSame($expected, $attempt->attempt_number);
        }

        $this->expectException(ValidationException::class);
        app(ExamAttemptService::class)->start($exam->fresh(), $this->student);
    }

    public function test_exam_stays_available_until_all_attempts_are_used(): void
    {
        $exam = $this->makeExam(3);

        $this->takeAttempt($exam);
        $this->assertSame('available', $exam->fresh()->dynamicStatus($this->student));
        $this->actingAs($this->student, 'student')->get(route('student.exams.available'))->assertSee('Limit Exam');

        $this->takeAttempt($exam);
        $this->takeAttempt($exam);
        $this->assertSame('completed', $exam->fresh()->dynamicStatus($this->student));
        $this->actingAs($this->student, 'student')->get(route('student.exams.available'))->assertDontSee('Limit Exam');
    }

    // --- Results held back until all attempts are done ---

    public function test_result_is_pending_until_every_attempt_is_completed(): void
    {
        $exam = $this->makeExam(3);
        $first = $this->takeAttempt($exam, correct: true);

        $this->actingAs($this->student, 'student')
            ->get(route('student.results.show', $first))
            ->assertOk()
            ->assertSee('Attempt 1/3 submitted')
            ->assertSee('Your results will be shown after you complete all 3 attempts')
            ->assertSee('Take Attempt 2 of 3')
            ->assertDontSee('Marks Obtained');

        $this->actingAs($this->student, 'student')
            ->get(route('student.results.index'))
            ->assertSee('Result Pending')
            ->assertSee('1/3 attempts done')
            ->assertDontSee('Marks Obtained');

        $this->actingAs($this->student, 'student')
            ->get(route('student.dashboard'))
            ->assertOk()
            ->assertSee('No results yet');
    }

    public function test_after_all_attempts_each_attempt_is_shown_separately(): void
    {
        $exam = $this->makeExam(3);
        $this->takeAttempt($exam, correct: false);
        $second = $this->takeAttempt($exam, correct: true);
        $this->takeAttempt($exam, correct: false);

        $response = $this->actingAs($this->student, 'student')
            ->get(route('student.results.show', $second))
            ->assertOk()
            ->assertSee('Attempt History')
            ->assertSee('3 of 3 attempts completed')
            ->assertSeeInOrder(['1/3', '2/3', '3/3'])
            ->assertSee('Marks Obtained')
            // Each attempt's own result opens inline under its row.
            ->assertSeeInOrder(['Attempt 1/3 · Answer Review', 'Attempt 2/3 · Answer Review', 'Attempt 3/3 · Answer Review'])
            ->assertSee('What is 5 + 5?')
            ->assertSee('Your answer:')
            ->assertSee('data-bs-parent="#attemptHistory'.$second->id.'"', false);

        // Attempt-wise marks: attempt 2 scored 10, the others 0.
        $this->assertSame(['0.00', '10.00', '0.00'], ExamAttempt::orderBy('attempt_number')->pluck('obtained_marks')->all());
        $response->assertSee('100.00%');

        $this->actingAs($this->student, 'student')
            ->get(route('student.results.index'))
            ->assertSee('Attempt 1/3')
            ->assertSee('Attempt 2/3')
            ->assertSee('Attempt 3/3')
            ->assertDontSee('Result Pending');
    }

    public function test_results_are_released_when_the_exam_window_ends_even_with_attempts_left(): void
    {
        $exam = $this->makeExam(3);
        $attempt = $this->takeAttempt($exam, correct: true);

        $exam->update(['ends_at' => now()->subMinute()]);

        $this->actingAs($this->student, 'student')
            ->get(route('student.results.show', $attempt))
            ->assertOk()
            ->assertSee('Marks Obtained')
            ->assertDontSee('Result Pending');
    }

    public function test_guardian_cannot_see_results_before_all_attempts_are_done(): void
    {
        $guardian = Guardian::create(['email' => 'parent@example.com', 'password' => Hash::make('secret123')]);
        $exam = $this->makeExam(2);
        $first = $this->takeAttempt($exam);

        $this->actingAs($guardian, 'guardian')->get(route('guardian.students.show', $this->student))->assertDontSee('Limit Exam');
        $this->actingAs($guardian, 'guardian')->get(route('guardian.students.results.show', [$this->student, $first]))->assertForbidden();
        $this->actingAs($guardian, 'guardian')->get(route('guardian.students.results.details', [$this->student, $first]))->assertForbidden();

        $this->takeAttempt($exam);

        $this->actingAs($guardian, 'guardian')->get(route('guardian.students.show', $this->student))->assertSee('Attempt 1/2')->assertSee('Attempt 2/2');
        // The guardian summary page has no answer review of its own — the
        // question/answer text here comes from the Attempt History panels.
        $this->actingAs($guardian, 'guardian')->get(route('guardian.students.results.show', [$this->student, $first]))
            ->assertOk()
            ->assertSee('Attempt History')
            ->assertSeeInOrder(['Attempt 1/2 · Answer Review', 'What is 5 + 5?', 'Student answer:', 'Attempt 2/2 · Answer Review', 'What is 5 + 5?']);
    }

    // --- Single-attempt exams unchanged ---

    public function test_single_attempt_exam_shows_the_result_immediately(): void
    {
        $exam = $this->makeExam(1);
        $attempt = $this->takeAttempt($exam, correct: true);

        $this->actingAs($this->student, 'student')
            ->get(route('student.results.show', $attempt))
            ->assertOk()
            ->assertSee('Marks Obtained')
            ->assertDontSee('Attempt History')
            ->assertDontSee('Result Pending');

        $this->assertSame('completed', $exam->fresh()->dynamicStatus($this->student));

        $this->expectException(ValidationException::class);
        app(ExamAttemptService::class)->start($exam->fresh(), $this->student);
    }

    // --- Staff result sections ---

    public function test_super_admin_and_branch_see_attempts_column_and_history_immediately(): void
    {
        $admin = $this->makeAdmin();
        $branchUser = User::create([
            'name' => 'North',
            'email' => 'north-user@example.com',
            'role' => 'Branch',
            'branch_id' => $this->branch->id,
            'password' => Hash::make('123456'),
        ]);

        $exam = $this->makeExam(3);
        $this->takeAttempt($exam);
        $second = $this->takeAttempt($exam);

        foreach ([[$admin, 'admin'], [$branchUser, 'branch']] as [$user, $prefix]) {
            $this->actingAs($user)->get(route($prefix.'.results.index'))
                ->assertOk()
                ->assertSee('Attempts')
                ->assertSee('1/3')
                ->assertSee('2/3');

            $this->actingAs($user)->get(route($prefix.'.results.show', $second))
                ->assertOk()
                ->assertSee('Attempt 2/3')
                ->assertSee('Attempt History')
                ->assertSee('2 of 3 attempts completed')
                // Answers are reviewed per attempt inside the history instead.
                ->assertDontSee('Correct answers are shown for management review.');
        }

        // A single-attempt exam keeps its Answer Review card.
        $single = $this->makeExam(1);
        $singleAttempt = $this->takeAttempt($single);
        $this->actingAs($admin)->get(route('admin.results.show', $singleAttempt))
            ->assertOk()
            ->assertSee('Correct answers are shown for management review.')
            ->assertDontSee('Attempt History');
    }

    // --- Helpers ---

    private function makeAdmin(): User
    {
        return User::create([
            'name' => 'Super Admin',
            'email' => 'admin-'.uniqid().'@example.com',
            'role' => 'Super Admin',
            'password' => Hash::make('123456'),
        ]);
    }

    private function makeExam(int $limit): Exam
    {
        $exam = Exam::create([
            'branch_id' => null,
            'school_class_id' => $this->grade->id,
            'subject_id' => $this->subject->id,
            'title' => 'Limit Exam',
            'total_marks' => 10,
            'duration_minutes' => 30,
            'maximum_attempts' => $limit,
            'status' => Exam::STATUS_PUBLISHED,
            'starts_at' => now()->subMinute(),
            'ends_at' => now()->addDay(),
        ]);

        $this->question = Question::create([
            'exam_id' => $exam->id,
            'question_text' => 'What is 5 + 5?',
            'marks' => 10,
            'position' => 1,
        ]);
        $this->question->options()->createMany([
            ['option_text' => '10', 'is_correct' => true, 'position' => 1],
            ['option_text' => '11', 'is_correct' => false, 'position' => 2],
        ]);

        return $exam;
    }

    private function takeAttempt(Exam $exam, bool $correct = true): ExamAttempt
    {
        $service = app(ExamAttemptService::class);
        $attempt = $service->start($exam->fresh(), $this->student);

        $option = $this->question->options()->where('is_correct', $correct)->first();
        $service->saveAnswer($attempt, $this->student, $this->question->id, $option->id);

        return $service->submit($attempt, $this->student);
    }
}
