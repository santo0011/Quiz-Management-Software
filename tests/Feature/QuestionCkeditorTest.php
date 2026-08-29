<?php

namespace Tests\Feature;

use App\Models\AcademicSession;
use App\Models\Branch;
use App\Models\Exam;
use App\Models\ExamAnswer;
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

class QuestionCkeditorTest extends TestCase
{
    use RefreshDatabase;

    // --- Standalone questions ("Add Another Question") now use CKEditor too ---

    public function test_standalone_multi_question_store_sanitizes_rich_html_question_text(): void
    {
        [$admin, $exam, $category] = $this->makeExamFixture();

        $this->actingAs($admin)->post(route('admin.questions.store', $exam), [
            'questions' => [
                [
                    'question_text' => '<p>If x &lt; 5 and x &gt; 2, find x.</p><script>alert(1)</script><img src="data:image/png;base64,aGVsbG8=" onerror="alert(2)">',
                    'marks' => 5,
                    'question_category_id' => $category->id,
                    'options' => ['3', '4', '6', '7'],
                    'correct_option' => 0,
                ],
            ],
        ])->assertRedirect();

        $question = Question::where('exam_id', $exam->id)->firstOrFail();

        $this->assertNull($question->passage_group_id);
        $this->assertStringContainsString('<p>If x', $question->question_text);
        $this->assertStringContainsString('<img src="data:image/png;base64,aGVsbG8="', $question->question_text);
        $this->assertStringNotContainsString('<script>', $question->question_text);
        $this->assertStringNotContainsString('onerror', $question->question_text);
    }

    public function test_standalone_single_question_update_sanitizes_rich_html_question_text(): void
    {
        [$admin, $exam, $category] = $this->makeExamFixture();

        $question = $this->makeStandaloneQuestion($exam, $category, 'Original question');

        $this->actingAs($admin)->put(route('admin.questions.update', $question), [
            'question_text' => '<p>Updated <em>question</em></p><script>alert(1)</script>',
            'marks' => 5,
            'question_category_id' => $category->id,
            'options' => ['A', 'B'],
            'correct_option' => 0,
        ])->assertRedirect();

        $question->refresh();
        $this->assertStringContainsString('<em>question</em>', $question->question_text);
        $this->assertStringNotContainsString('<script>', $question->question_text);
    }

    public function test_standalone_question_edit_form_uses_ckeditor(): void
    {
        [$admin, $exam, $category] = $this->makeExamFixture();

        $question = $this->makeStandaloneQuestion($exam, $category, 'Plain question text');

        $response = $this->actingAs($admin)->get(route('admin.questions.edit', $question));

        $response->assertOk();
        $response->assertSee('data-summary-editor', false);
        $response->assertSee('name="question_text"', false);
    }

    public function test_question_management_page_renders_standalone_question_as_html(): void
    {
        [$admin, $exam, $category] = $this->makeExamFixture();

        $this->makeStandaloneQuestion($exam, $category, '<p>What is <strong>H2O</strong>?</p>');

        $response = $this->actingAs($admin)
            ->withSession(['admin_selected_academic_session_id' => $exam->session_id])
            ->get(route('admin.questions.create', $exam));

        $response->assertOk();
        $response->assertSee('<strong>H2O</strong>', false);
        $response->assertDontSee('&lt;strong&gt;', false);
    }

    public function test_result_review_page_renders_standalone_question_as_html(): void
    {
        [$admin, $exam, $category] = $this->makeExamFixture();

        $question = $this->makeStandaloneQuestion($exam, $category, '<p>What is <strong>2+2</strong>?</p>');
        $attempt = $this->makeSubmittedAttempt($exam, $question);

        $response = $this->actingAs($admin)->get(route('admin.results.show', $attempt));

        $response->assertOk();
        $response->assertSee('<strong>2+2</strong>', false);
        $response->assertDontSee('&lt;strong&gt;', false);
    }

    // --- Questions added under a Summary keep using the rich-text (CKEditor) editor ---

    public function test_summary_multi_question_store_sanitizes_rich_html_question_text(): void
    {
        [$admin, $exam, $category] = $this->makeExamFixture();
        $group = $this->makePassageGroup($exam);

        $this->actingAs($admin)->post(route('admin.passage-groups.questions.store', [$exam, $group]), [
            'questions' => [
                [
                    'question_text' => '<p>What is <strong>2 + 2</strong>?</p><script>alert(1)</script><img src="data:image/png;base64,aGVsbG8=" onerror="alert(2)">',
                    'marks' => 5,
                    'question_category_id' => $category->id,
                    'options' => ['4', '5', '6', '7'],
                    'correct_option' => 0,
                ],
            ],
        ])->assertRedirect();

        $question = Question::where('exam_id', $exam->id)->where('passage_group_id', $group->id)->firstOrFail();

        $this->assertStringContainsString('<strong>2 + 2</strong>', $question->question_text);
        $this->assertStringContainsString('<img src="data:image/png;base64,aGVsbG8="', $question->question_text);
        $this->assertStringNotContainsString('<script>', $question->question_text);
        $this->assertStringNotContainsString('onerror', $question->question_text);
    }

    public function test_summary_single_question_update_sanitizes_rich_html_question_text(): void
    {
        [$admin, $exam, $category] = $this->makeExamFixture();
        $group = $this->makePassageGroup($exam);
        $question = $this->makeSummaryQuestion($exam, $group, $category, 'Original question');

        $this->actingAs($admin)->put(route('admin.questions.update', $question), [
            'question_text' => '<p>Updated <em>question</em></p><script>alert(1)</script>',
            'marks' => 5,
            'question_category_id' => $category->id,
            'options' => ['A', 'B'],
            'correct_option' => 0,
        ])->assertRedirect();

        $question->refresh();
        $this->assertStringContainsString('<em>question</em>', $question->question_text);
        $this->assertStringNotContainsString('<script>', $question->question_text);
    }

    public function test_summary_question_edit_form_uses_ckeditor(): void
    {
        [$admin, $exam, $category] = $this->makeExamFixture();
        $group = $this->makePassageGroup($exam);
        $question = $this->makeSummaryQuestion($exam, $group, $category, 'Plain question text');

        $response = $this->actingAs($admin)->get(route('admin.questions.edit', $question));

        $response->assertOk();
        $response->assertSee('data-summary-editor', false);
        $response->assertSee('name="question_text"', false);
    }

    public function test_question_management_page_renders_summary_question_as_html(): void
    {
        [$admin, $exam, $category] = $this->makeExamFixture();
        $group = $this->makePassageGroup($exam);
        $this->makeSummaryQuestion($exam, $group, $category, '<p>What is <strong>H2O</strong>?</p>');

        $response = $this->actingAs($admin)
            ->withSession(['admin_selected_academic_session_id' => $exam->session_id])
            ->get(route('admin.questions.create', $exam));

        $response->assertOk();
        $response->assertSee('<strong>H2O</strong>', false);
        $response->assertDontSee('&lt;strong&gt;', false);
    }

    public function test_result_review_page_renders_summary_question_as_html(): void
    {
        [$admin, $exam, $category] = $this->makeExamFixture();
        $group = $this->makePassageGroup($exam);
        $question = $this->makeSummaryQuestion($exam, $group, $category, '<p>What is <strong>the capital of France</strong>?</p>');
        $attempt = $this->makeSubmittedAttempt($exam, $question);

        $response = $this->actingAs($admin)->get(route('admin.results.show', $attempt));

        $response->assertOk();
        $response->assertSee('<strong>the capital of France</strong>', false);
        $response->assertDontSee('&lt;strong&gt;', false);
    }

    // --- The "Insert Math" equation tool is available on every CKEditor question-text field ---

    public function test_standalone_question_edit_form_includes_math_equation_tool(): void
    {
        [$admin, $exam, $category] = $this->makeExamFixture();
        $question = $this->makeStandaloneQuestion($exam, $category, 'Plain question text');

        $response = $this->actingAs($admin)->get(route('admin.questions.edit', $question));

        $response->assertOk();
        $response->assertSee('data-ckeditor-math-wrap', false);
        $response->assertSee('data-math-staging', false);
        $response->assertSee('Insert Equation', false);
    }

    public function test_summary_question_edit_form_includes_math_equation_tool(): void
    {
        [$admin, $exam, $category] = $this->makeExamFixture();
        $group = $this->makePassageGroup($exam);
        $question = $this->makeSummaryQuestion($exam, $group, $category, 'Plain question text');

        $response = $this->actingAs($admin)->get(route('admin.questions.edit', $question));

        $response->assertOk();
        $response->assertSee('data-ckeditor-math-wrap', false);
        $response->assertSee('data-math-staging', false);
        $response->assertSee('Insert Equation', false);
    }

    public function test_passage_summary_form_includes_math_equation_tool(): void
    {
        [$admin, $exam] = $this->makeExamFixture();
        $group = $this->makePassageGroup($exam);

        $response = $this->actingAs($admin)->get(route('admin.passage-groups.edit', $group));

        $response->assertOk();
        $response->assertSee('data-ckeditor-math-wrap', false);
        $response->assertSee('data-math-staging', false);
        $response->assertSee('Insert Equation', false);
    }

    private function makeExamFixture(): array
    {
        $admin = User::create([
            'name' => 'Super Admin',
            'email' => 'admin@example.com',
            'role' => 'Super Admin',
            'password' => Hash::make('123456'),
        ]);

        $class = SchoolClass::create(['branch_id' => null, 'name' => 'Class 10']);
        $subject = Subject::create(['name' => 'Science']);
        $category = QuestionCategory::create(['branch_id' => null, 'name' => 'General']);

        $session = AcademicSession::create([
            'name' => '2026-2027',
            'start_date' => '2026-06-01',
            'end_date' => '2027-05-31',
            'is_active' => true,
        ]);

        $exam = Exam::create([
            'branch_id' => null,
            'school_class_id' => $class->id,
            'subject_id' => $subject->id,
            'title' => 'CKEditor Question Test',
            'total_marks' => 0,
            'duration_minutes' => 30,
            'maximum_attempts' => 1,
            'session_id' => $session->id,
            'status' => Exam::STATUS_DRAFT,
        ]);

        return [$admin, $exam, $category];
    }

    private function makePassageGroup(Exam $exam): PassageGroup
    {
        return PassageGroup::create([
            'exam_id' => $exam->id,
            'title' => 'Summary 1',
            'content' => '<p>Some passage content.</p>',
            'position' => 1,
        ]);
    }

    private function makeStandaloneQuestion(Exam $exam, QuestionCategory $category, string $text): Question
    {
        $question = Question::create([
            'exam_id' => $exam->id,
            'question_text' => $text,
            'question_type' => 'mcq',
            'marks' => 5,
            'question_category_id' => $category->id,
        ]);
        $question->options()->createMany([
            ['option_text' => 'A', 'is_correct' => true, 'position' => 0],
            ['option_text' => 'B', 'is_correct' => false, 'position' => 1],
        ]);

        return $question;
    }

    private function makeSummaryQuestion(Exam $exam, PassageGroup $group, QuestionCategory $category, string $text): Question
    {
        $question = Question::create([
            'exam_id' => $exam->id,
            'passage_group_id' => $group->id,
            'question_text' => $text,
            'question_type' => 'mcq',
            'marks' => 5,
            'question_category_id' => $category->id,
        ]);
        $question->options()->createMany([
            ['option_text' => 'A', 'is_correct' => true, 'position' => 0],
            ['option_text' => 'B', 'is_correct' => false, 'position' => 1],
        ]);

        return $question;
    }

    private function makeSubmittedAttempt(Exam $exam, Question $question): ExamAttempt
    {
        $correct = $question->options()->where('is_correct', true)->first();

        $branch = Branch::create(['name' => 'Main Branch', 'email' => 'main-branch-'.$question->id.'@example.com']);

        $student = Student::create([
            'branch_id' => $branch->id,
            'class_id' => $exam->school_class_id,
            'session_id' => $exam->session_id,
            'student_name' => 'Test Student',
            'guardian_name' => 'Guardian',
            'class' => $exam->schoolClass->name,
            'phone_number' => '9876543210',
            'email' => 'ckeditor-student-'.$question->id.'@example.com',
            'is_active' => true,
        ]);

        $attempt = ExamAttempt::create([
            'exam_id' => $exam->id,
            'student_id' => $student->id,
            'branch_id' => $branch->id,
            'school_class_id' => $exam->school_class_id,
            'attempt_number' => 1,
            'started_at' => now(),
            'expires_at' => now()->addMinutes(30),
            'submitted_at' => now(),
            'obtained_marks' => 5,
            'percentage' => 100,
            'correct_count' => 1,
            'wrong_count' => 0,
            'unanswered_count' => 0,
            'is_passed' => true,
            'status' => 'submitted',
        ]);

        ExamAnswer::create([
            'exam_attempt_id' => $attempt->id,
            'question_id' => $question->id,
            'question_option_id' => $correct->id,
            'is_correct' => true,
            'marks_awarded' => 5,
        ]);

        return $attempt;
    }
}
