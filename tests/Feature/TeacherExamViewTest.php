<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Exam;
use App\Models\PassageGroup;
use App\Models\Question;
use App\Models\SchoolClass;
use App\Models\Teacher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TeacherExamViewTest extends TestCase
{
    use RefreshDatabase;

    private Branch $branch;

    private Branch $otherBranch;

    private Teacher $teacher;

    protected function setUp(): void
    {
        parent::setUp();

        $this->branch = Branch::create(['name' => 'Ringwood', 'email' => 'ringwood@example.com', 'is_active' => true]);
        $this->otherBranch = Branch::create(['name' => 'Berwick', 'email' => 'berwick@example.com', 'is_active' => true]);
        $this->teacher = Teacher::create([
            'branch_id' => $this->branch->id,
            'name' => 'Ringwood Teacher',
            'email' => 'teacher@example.com',
            'phone_number' => '111',
            'password' => Hash::make('secret123'),
        ]);
    }

    public function test_exam_list_shows_own_branch_and_global_exams_only(): void
    {
        $this->exam('Global Maths Test', null);
        $this->exam('Ringwood Own Test', $this->branch->id);
        $this->exam('Berwick Secret Test', $this->otherBranch->id);

        $this->actingAs($this->teacher, 'teacher')->get(route('teacher.exams.index'))
            ->assertOk()
            ->assertSee('Global Maths Test')
            ->assertSee('Ringwood Own Test')
            ->assertDontSee('Berwick Secret Test')
            ->assertDontSee('Add Exam');

        $this->actingAs($this->teacher, 'teacher')->get(route('teacher.dashboard'))
            ->assertSee(route('teacher.exams.index'), false);
    }

    public function test_exam_page_lists_questions_read_only(): void
    {
        $exam = $this->exam('Ringwood Own Test', $this->branch->id);

        Question::create([
            'exam_id' => $exam->id, 'question_text' => '<p>What is 8 + 6?</p>', 'question_type' => 'mcq',
            'marks' => 1, 'explanation' => 'Count on 6 from 8.', 'position' => 1,
        ])->options()->createMany([
            ['option_text' => '13', 'is_correct' => false, 'position' => 0],
            ['option_text' => '14', 'is_correct' => true, 'position' => 1],
        ]);

        $group = PassageGroup::create(['exam_id' => $exam->id, 'title' => 'Summary 1', 'content' => '<p>Mia has 12 apples.</p>', 'position' => 2]);
        Question::create([
            'exam_id' => $exam->id, 'passage_group_id' => $group->id, 'question_text' => 'How many apples does Mia have?',
            'question_type' => 'mcq', 'marks' => 1, 'position' => 1,
        ])->options()->createMany([
            ['option_text' => '12', 'is_correct' => true, 'position' => 0],
            ['option_text' => '5', 'is_correct' => false, 'position' => 1],
        ]);

        $this->actingAs($this->teacher, 'teacher')->get(route('teacher.exams.show', $exam))
            ->assertOk()
            ->assertSee('What is 8 + 6?')
            ->assertSee('Count on 6 from 8.')
            ->assertSee('Mia has 12 apples.')
            ->assertSee('How many apples does Mia have?')
            ->assertSee('Correct')
            ->assertSee('View only')
            // No action of any kind: no delete/update forms, edit links, reorder or publish.
            ->assertDontSee('name="_method" value="DELETE"', false)
            ->assertDontSee('name="_method" value="PUT"', false)
            ->assertDontSee('/questions/', false)
            ->assertDontSee('/reorder', false)
            ->assertDontSee('/publish"', false)
            ->assertDontSee('/edit"', false)
            ->assertDontSee('Manage Questions')
            ->assertDontSee('Edit Exam');
    }

    public function test_teacher_cannot_open_another_branchs_exam(): void
    {
        $exam = $this->exam('Berwick Secret Test', $this->otherBranch->id);

        $this->actingAs($this->teacher, 'teacher')->get(route('teacher.exams.show', $exam))->assertForbidden();
    }

    public function test_guest_is_sent_to_login(): void
    {
        $this->get(route('teacher.exams.index'))->assertRedirect();
    }

    private function exam(string $title, ?int $branchId): Exam
    {
        $class = SchoolClass::firstOrCreate(['branch_id' => null, 'name' => 'Grade 3']);

        return Exam::create([
            'branch_id' => $branchId,
            'school_class_id' => $class->id,
            'title' => $title,
            'total_marks' => 2,
            'duration_minutes' => 20,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addDay(),
            'maximum_attempts' => 1,
            'status' => Exam::STATUS_PUBLISHED,
        ]);
    }
}
