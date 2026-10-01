<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\PassageGroup;
use App\Models\Question;
use App\Models\QuestionCategory;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Question serials on the exam question-management pages are one
 * continuous numeric sequence: Summary questions are 1, 2, 3… (never
 * A, B, C) and the next normal question continues from the next number.
 */
class QuestionNumberingTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'name' => 'Super Admin',
            'email' => 'numbering-admin@example.com',
            'role' => 'Super Admin',
            'password' => Hash::make('123456'),
        ]);
    }

    public function test_summary_questions_are_numbered_1_to_5_and_normal_questions_continue_at_6(): void
    {
        [$exam, $summaryQuestions, $normalQuestions] = $this->makeSummaryThenNormalExam();

        $numbers = $exam->questionNumbers();

        $this->assertSame([1, 2, 3, 4, 5], array_map(fn (Question $q) => $numbers[$q->id], $summaryQuestions));
        $this->assertSame([6, 7], array_map(fn (Question $q) => $numbers[$q->id], $normalQuestions));
    }

    public function test_super_admin_page_shows_numeric_continuous_serials_and_no_letters(): void
    {
        [$exam] = $this->makeSummaryThenNormalExam();

        $badges = $this->badgeNumbers($this->actingAs($this->admin)->get(route('admin.questions.create', $exam))->assertOk()->getContent());

        $this->assertSame(['1', '2', '3', '4', '5', '6', '7'], $badges);
    }

    public function test_answer_review_uses_the_same_numeric_serials(): void
    {
        [$exam] = $this->makeSummaryThenNormalExam();
        $branch = Branch::create(['name' => 'Review Branch', 'email' => 'review-branch@example.com']);
        $grade = SchoolClass::create(['branch_id' => $branch->id, 'name' => 'Grade Review']);
        $student = Student::create([
            'branch_id' => $branch->id,
            'class_id' => $grade->id,
            'student_name' => 'Review Student',
            'guardian_name' => 'Guardian',
            'class' => $grade->name,
            'phone_number' => '9876543210',
            'email' => 'review-student@example.com',
            'is_active' => true,
        ]);
        $attempt = ExamAttempt::create([
            'exam_id' => $exam->id,
            'student_id' => $student->id,
            'branch_id' => $branch->id,
            'school_class_id' => $grade->id,
            'attempt_number' => 1,
            'started_at' => now()->subMinutes(10),
            'expires_at' => now(),
            'submitted_at' => now(),
            'status' => 'submitted',
        ]);

        $badges = $this->badgeNumbers($this->actingAs($this->admin)->get(route('admin.results.show', $attempt))->assertOk()->getContent());

        // The page renders the review list (and the same list again inside
        // the attempt history); each must read 1-7 with no A, B, C labels.
        $this->assertNotEmpty($badges);
        $this->assertSame(['1', '2', '3', '4', '5', '6', '7'], array_slice($badges, 0, 7));
        $this->assertSame([], array_values(array_filter($badges, fn ($badge) => ! ctype_digit($badge))));
    }

    public function test_branch_page_shows_the_same_serials(): void
    {
        $branch = Branch::create(['name' => 'North', 'email' => 'north@example.com', 'is_active' => true]);
        $branchUser = User::create([
            'name' => 'North',
            'email' => 'north-user@example.com',
            'role' => 'Branch',
            'branch_id' => $branch->id,
            'password' => Hash::make('123456'),
        ]);
        [$exam] = $this->makeSummaryThenNormalExam($branch);

        $badges = $this->badgeNumbers($this->actingAs($branchUser)->get(route('branch.questions.create', $exam))->assertOk()->getContent());

        $this->assertSame(['1', '2', '3', '4', '5', '6', '7'], $badges);
    }

    public function test_add_question_forms_label_new_questions_with_the_next_serial(): void
    {
        [$exam, , , $group] = $this->makeSummaryThenNormalExam();

        $html = $this->actingAs($this->admin)->get(route('admin.questions.create', $exam))->getContent();

        // Main form appends after all 7 questions; Summary form appends after its 5th.
        $this->assertMatchesRegularExpression('/data-form-id="main"[^>]*data-number-start="7"/', $html);
        $this->assertMatchesRegularExpression('/data-form-id="summary-'.$group->id.'"[^>]*data-number-start="5"/', $html);
        $this->assertStringContainsString('Question 8</h3>', $html);
        $this->assertStringContainsString('Question 6</h3>', $html);
    }

    public function test_passage_questions_page_continues_the_exam_serial(): void
    {
        [$exam] = $this->makeSummaryThenNormalExam();
        // A second Summary after the two normal questions.
        $second = PassageGroup::create(['exam_id' => $exam->id, 'title' => 'Summary 2', 'content' => '<p>Two</p>', 'position' => 4]);
        $this->makeQuestion($exam, 1, $second);

        $html = $this->actingAs($this->admin)
            ->get(route('admin.passage-groups.questions.create', [$exam, $second]))
            ->assertOk()
            ->getContent();

        $this->assertMatchesRegularExpression('/data-number-start="7"/', $html);
        $this->assertStringContainsString('Question 8 <span class="badge-saved">', $html);
        $this->assertStringContainsString('Question 9</h3>', $html);
    }

    public function test_numbering_updates_after_reordering(): void
    {
        [$exam, , $normalQuestions] = $this->makeSummaryThenNormalExam();

        // Move the first normal question above the Summary.
        $this->actingAs($this->admin)->post(route('admin.exams.reorder', $exam), [
            'type' => 'question',
            'id' => $normalQuestions[0]->id,
            'direction' => 'up',
        ]);

        $numbers = $exam->fresh()->questionNumbers();
        $this->assertSame(1, $numbers[$normalQuestions[0]->id]);
        $this->assertSame(7, $numbers[$normalQuestions[1]->id]);

        $badges = $this->badgeNumbers($this->actingAs($this->admin)->get(route('admin.questions.create', $exam))->getContent());
        $this->assertSame(['1', '2', '3', '4', '5', '6', '7'], $badges);
    }

    public function test_numbering_closes_gaps_after_deleting_a_summary_question(): void
    {
        [$exam, $summaryQuestions, $normalQuestions] = $this->makeSummaryThenNormalExam();

        $this->actingAs($this->admin)->delete(route('admin.questions.destroy', $summaryQuestions[2]));

        $numbers = $exam->fresh()->questionNumbers();
        $this->assertSame([5, 6], [$numbers[$normalQuestions[0]->id], $numbers[$normalQuestions[1]->id]]);
    }

    /**
     * Summary (5 questions) at position 1, then two normal questions.
     *
     * @return array{0: Exam, 1: array<int, Question>, 2: array<int, Question>, 3: PassageGroup}
     */
    private function makeSummaryThenNormalExam(?Branch $branch = null): array
    {
        // The add-question forms only render once a question category exists.
        QuestionCategory::firstOrCreate(['branch_id' => null, 'name' => 'General']);
        $grade = SchoolClass::create(['branch_id' => null, 'name' => 'Grade '.uniqid()]);
        $subject = Subject::create(['name' => 'English '.uniqid()]);

        $exam = Exam::create([
            'branch_id' => $branch?->id,
            'school_class_id' => $grade->id,
            'subject_id' => $subject->id,
            'title' => 'Numbering Exam',
            'total_marks' => 7,
            'duration_minutes' => 30,
            'maximum_attempts' => 1,
            'status' => Exam::STATUS_DRAFT,
        ]);

        $group = PassageGroup::create(['exam_id' => $exam->id, 'title' => 'Summary 1', 'content' => '<p>Passage</p>', 'position' => 1]);

        $summaryQuestions = [];
        foreach (range(1, 5) as $position) {
            $summaryQuestions[] = $this->makeQuestion($exam, $position, $group);
        }

        $normalQuestions = [
            $this->makeQuestion($exam, 2),
            $this->makeQuestion($exam, 3),
        ];

        return [$exam, $summaryQuestions, $normalQuestions, $group];
    }

    private function makeQuestion(Exam $exam, int $position, ?PassageGroup $group = null): Question
    {
        $question = Question::create([
            'exam_id' => $exam->id,
            'passage_group_id' => $group?->id,
            'question_text' => 'Question at '.$position,
            'marks' => 1,
            'position' => $position,
        ]);

        $question->options()->createMany([
            ['option_text' => 'Yes', 'is_correct' => true, 'position' => 1],
            ['option_text' => 'No', 'is_correct' => false, 'position' => 2],
        ]);

        return $question;
    }

    /**
     * The text of every numbered question badge (Summary header badges
     * hold an icon, not a number, and are skipped).
     *
     * @return array<int, string>
     */
    private function badgeNumbers(string $html): array
    {
        preg_match_all('/<div class="question-number-badge">\s*([^<\s][^<]*?)\s*<\/div>/', $html, $matches);

        return $matches[1];
    }
}
