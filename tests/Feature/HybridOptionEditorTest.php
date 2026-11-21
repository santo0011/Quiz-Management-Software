<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Exam;
use App\Models\ExamAnswer;
use App\Models\ExamAttempt;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use App\Support\OptionContent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class HybridOptionEditorTest extends TestCase
{
    use RefreshDatabase;

    private const IMAGE = '<img src="data:image/png;base64,aGVsbG8=">';

    public function test_option_content_tells_rich_html_from_legacy_plain_text(): void
    {
        $this->assertTrue(OptionContent::isHtml('<p>x</p>'));
        $this->assertTrue(OptionContent::isHtml('<figure class="image">'.self::IMAGE.'</figure>'));
        $this->assertFalse(OptionContent::isHtml('x < 5'));
        $this->assertFalse(OptionContent::isHtml('<b>literal</b>'));

        $this->assertSame('x &lt; 5', OptionContent::toHtml('x < 5'));
        $this->assertSame('x < 5', OptionContent::normalize('  x < 5 '));
        $this->assertSame('', OptionContent::normalize('<p>&nbsp;</p>'));
        $this->assertFalse(OptionContent::isBlank('<p>'.self::IMAGE.'</p>'));
    }

    public function test_rich_options_are_sanitized_and_legacy_plain_options_stay_plain(): void
    {
        [$admin, $exam] = $this->makeFixture();

        $this->actingAs($admin)->post(route('admin.questions.store', $exam), [
            'questions' => [[
                'question_text' => '<p>Pick one</p>',
                'marks' => 1,
                'options' => [
                    '<p><strong>Bold</strong> <a href="https://example.com">link</a></p><script>alert(1)</script>',
                    '<figure class="image">'.self::IMAGE.'</figure>',
                    '<p>\(\frac{1}{2}\)</p>',
                    'x < 5',
                ],
                'correct_option' => 1,
            ]],
        ])->assertRedirect()->assertSessionHasNoErrors();

        $options = Question::where('exam_id', $exam->id)->firstOrFail()->options;

        $this->assertSame('<p><strong>Bold</strong> link</p>', $options[0]->option_text);
        $this->assertStringContainsString(self::IMAGE, $options[1]->option_text);
        $this->assertTrue($options[1]->is_correct);
        $this->assertSame('<p>\(\frac{1}{2}\)</p>', $options[2]->option_text);
        $this->assertSame('x < 5', $options[3]->option_text);
    }

    public function test_option_with_no_visible_content_is_rejected(): void
    {
        [$admin, $exam] = $this->makeFixture();

        $this->actingAs($admin)->post(route('admin.questions.store', $exam), [
            '_form_key' => 'main',
            'questions' => [[
                'question_text' => '<p>Pick one</p>',
                'marks' => 1,
                'options' => ['<p>A</p>', '<p>&nbsp;</p>'],
                'correct_option' => 0,
            ]],
        ])->assertSessionHasErrors('questions.0.options.1');

        $this->assertSame(0, Question::count());
    }

    public function test_updating_options_keeps_student_answers_linked(): void
    {
        [$admin, $exam] = $this->makeFixture();
        $question = $this->makeQuestion($exam, ['A', 'B', 'C']);
        [$a, $b, $c] = $question->options->all();
        $answer = $this->answer($exam, $question, $c);

        // Remove B, add an image to C, append a new option.
        $this->actingAs($admin)->put(route('admin.questions.update', $question), [
            'question_text' => '<p>Question</p>',
            'marks' => 1,
            'options' => ['A', '<p>C '.self::IMAGE.'</p>', '<p>New</p>'],
            'option_ids' => [$a->id, $c->id, ''],
            'correct_option' => 1,
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertSame($c->id, $answer->fresh()->question_option_id);
        $this->assertNull(QuestionOption::find($b->id));

        $options = $question->fresh()->options;
        $this->assertSame([$a->id, $c->id], [$options[0]->id, $options[1]->id]);
        $this->assertSame([0, 1, 2], $options->pluck('position')->all());
        $this->assertTrue($options[1]->is_correct);
        $this->assertStringContainsString(self::IMAGE, $options[1]->option_text);
        $this->assertSame('<p>New</p>', $options[2]->option_text);
    }

    public function test_branch_update_also_keeps_answers_linked(): void
    {
        [, $exam, $branch] = $this->makeFixture();
        $branchUser = User::create([
            'name' => 'Branch User', 'email' => 'branch@example.com', 'username' => 'BR001',
            'role' => 'Branch', 'branch_id' => $branch->id, 'password' => Hash::make('123456'),
        ]);
        $question = $this->makeQuestion($exam, ['A', 'B']);
        [$a, $b] = $question->options->all();
        $answer = $this->answer($exam, $question, $b);

        $this->actingAs($branchUser)->put(route('branch.questions.update', $question), [
            'question_text' => '<p>Question</p>',
            'marks' => 1,
            'options' => ['<p>A edited</p>', 'B'],
            'option_ids' => [$a->id, $b->id],
            'correct_option' => 0,
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertSame($b->id, $answer->fresh()->question_option_id);
        $this->assertSame('<p>A edited</p>', $a->fresh()->option_text);
    }

    public function test_edit_form_renders_hybrid_option_fields_with_ids(): void
    {
        [$admin, $exam] = $this->makeFixture();
        $question = $this->makeQuestion($exam, ['x < 5', '<p><strong>rich</strong></p>']);

        $response = $this->actingAs($admin)->get(route('admin.questions.edit', $question));

        $response->assertOk();
        $response->assertSee('data-option-editor', false);
        $response->assertSee('name="option_ids[]" value="'.$question->options[0]->id.'"', false);
        $response->assertSee('x &lt; 5', false);
        $response->assertSee('<strong>rich</strong>', false);
        $response->assertSee('data-option-editor-template', false);
    }

    public function test_exam_preview_and_student_api_render_both_formats_safely(): void
    {
        [$admin, $exam, $branch] = $this->makeFixture();
        $question = $this->makeQuestion($exam, ['x < 5', '<p>Rich '.self::IMAGE.'</p>']);

        $this->actingAs($admin)->get(route('admin.questions.create', $exam))
            ->assertOk()
            ->assertSee('x &lt; 5', false)
            ->assertSee('<p>Rich '.self::IMAGE.'</p>', false);

        $student = $this->makeStudent($exam, $branch);
        $attempt = $this->startAttempt($exam, $student);

        $response = $this->actingAs($student, 'student')->getJson(route('student.attempts.state', $attempt));
        $response->assertOk();
        $response->assertJsonPath('items.0.question.options.0.html', 'x &lt; 5');
        $response->assertJsonPath('items.0.question.options.1.html', '<p>Rich '.self::IMAGE.'</p>');
    }

    public function test_result_review_renders_rich_selected_and_correct_options(): void
    {
        [$admin, $exam] = $this->makeFixture();
        $question = $this->makeQuestion($exam, ['<p>Right '.self::IMAGE.'</p>', 'a & b']);
        $answer = $this->answer($exam, $question, $question->options[1]);

        $this->actingAs($admin)->get(route('admin.results.show', $answer->attempt))
            ->assertOk()
            ->assertSee('a &amp; b', false)
            ->assertSee('<p>Right '.self::IMAGE.'</p>', false);
    }

    private function makeFixture(): array
    {
        $admin = User::create([
            'name' => 'Super Admin', 'email' => 'admin@example.com',
            'role' => 'Super Admin', 'password' => Hash::make('123456'),
        ]);
        $branch = Branch::create(['name' => 'Main Branch', 'email' => 'main@example.com']);
        $class = SchoolClass::create(['branch_id' => $branch->id, 'name' => 'Class 10']);

        $exam = Exam::create([
            'branch_id' => $branch->id,
            'school_class_id' => $class->id,
            'title' => 'Hybrid Option Exam',
            'total_marks' => 0,
            'duration_minutes' => 30,
            'starts_at' => now()->subMinute(),
            'ends_at' => now()->addDay(),
            'maximum_attempts' => 1,
            'status' => Exam::STATUS_PUBLISHED,
        ]);

        return [$admin, $exam, $branch];
    }

    private function makeQuestion(Exam $exam, array $optionTexts): Question
    {
        $question = Question::create([
            'exam_id' => $exam->id, 'question_text' => '<p>Question</p>',
            'question_type' => 'mcq', 'marks' => 1,
        ]);

        foreach ($optionTexts as $index => $text) {
            $question->options()->create(['option_text' => $text, 'is_correct' => $index === 0, 'position' => $index]);
        }

        return $question->load('options');
    }

    private function makeStudent(Exam $exam, Branch $branch): Student
    {
        return Student::create([
            'branch_id' => $branch->id, 'class_id' => $exam->school_class_id,
            'student_name' => 'Test Student', 'guardian_name' => 'Guardian',
            'class' => 'Class 10', 'phone_number' => '9876543210',
            'email' => 'hybrid-student-'.uniqid().'@example.com', 'is_active' => true,
        ]);
    }

    private function startAttempt(Exam $exam, Student $student, string $status = 'in_progress'): ExamAttempt
    {
        return ExamAttempt::create([
            'exam_id' => $exam->id, 'student_id' => $student->id,
            'branch_id' => $student->branch_id, 'school_class_id' => $exam->school_class_id,
            'attempt_number' => 1, 'started_at' => now(), 'expires_at' => now()->addMinutes(30),
            'submitted_at' => $status === 'submitted' ? now() : null,
            'obtained_marks' => 0, 'percentage' => 0, 'correct_count' => 0,
            'wrong_count' => 0, 'unanswered_count' => 0, 'status' => $status,
        ]);
    }

    private function answer(Exam $exam, Question $question, QuestionOption $option): ExamAnswer
    {
        $attempt = $this->startAttempt($exam, $this->makeStudent($exam, Branch::firstOrFail()), 'submitted');

        return ExamAnswer::create([
            'exam_attempt_id' => $attempt->id, 'question_id' => $question->id,
            'question_option_id' => $option->id, 'is_correct' => $option->is_correct,
            'marks_awarded' => $option->is_correct ? 1 : 0,
        ]);
    }
}
