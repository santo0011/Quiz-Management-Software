<?php

namespace Tests\Feature;

use App\Models\Exam;
use App\Models\Question;
use App\Models\QuestionCategory;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Question categories are optional: an exam with no categories created yet
 * must still let the Super Admin (and Branch) add questions.
 */
class QuestionWithoutCategoryTest extends TestCase
{
    use RefreshDatabase;

    private function fixture(): array
    {
        $admin = User::create([
            'name' => 'Super Admin',
            'email' => 'admin@example.com',
            'role' => 'Super Admin',
            'password' => Hash::make('123456'),
        ]);

        $class = SchoolClass::create(['branch_id' => null, 'name' => 'Grade 1']);
        $subject = Subject::create(['name' => 'Maths']);

        $exam = Exam::create([
            'branch_id' => null,
            'school_class_id' => $class->id,
            'subject_id' => $subject->id,
            'title' => 'TEST',
            'total_marks' => 0,
            'duration_minutes' => 30,
            'maximum_attempts' => 1,
            'status' => Exam::STATUS_DRAFT,
        ]);

        return [$admin, $exam];
    }

    public function test_add_question_form_is_shown_when_no_categories_exist(): void
    {
        [$admin, $exam] = $this->fixture();

        $this->assertSame(0, QuestionCategory::count());

        $this->actingAs($admin)->get(route('admin.questions.create', $exam))
            ->assertOk()
            ->assertSee('Add Another Question')
            ->assertSee('Add Summary');
    }

    public function test_questions_can_be_saved_without_a_category(): void
    {
        [$admin, $exam] = $this->fixture();

        $this->actingAs($admin)->post(route('admin.questions.store', $exam), [
            'questions' => [
                [
                    'question_text' => '2 + 2 = ?',
                    'marks' => 1,
                    'question_category_id' => '',
                    'options' => ['4', '5', '6', '7'],
                    'correct_option' => 0,
                ],
            ],
        ])->assertRedirect()->assertSessionHasNoErrors();

        $question = Question::where('exam_id', $exam->id)->first();

        $this->assertNotNull($question);
        $this->assertNull($question->question_category_id);
    }
}
