<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\Question;
use App\Models\QuestionCategory;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use App\Services\ExamAttemptService;
use App\Services\SingleSessionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Result answer reviews show each question's category everywhere, but the
 * question explanation only on the Branch and Teacher panels.
 */
class ResultCategoryAndExplanationTest extends TestCase
{
    use RefreshDatabase;

    private const EXPLANATION = 'Seven plus five makes twelve.';

    private Branch $branch;

    private Student $student;

    private ExamAttempt $attempt;

    protected function setUp(): void
    {
        parent::setUp();

        Http::fake();
        Storage::fake('public');

        $this->branch = Branch::create(['name' => 'Ringwood', 'email' => 'ringwood@example.com', 'is_active' => true]);
        $grade = SchoolClass::create(['branch_id' => null, 'name' => 'Grade 3']);
        $subject = Subject::create(['name' => 'Mathematics']);
        $category = QuestionCategory::create(['branch_id' => null, 'name' => 'Addition']);

        $this->student = Student::create([
            'branch_id' => $this->branch->id,
            'class_id' => $grade->id,
            'student_name' => 'avsbera',
            'guardian_name' => 'Parent',
            'class' => 'Grade 3',
            'phone_number' => '',
            'email' => 'avsbera@example.com',
            'is_active' => true,
        ]);
        $this->student->subjects()->attach($subject->id);

        $exam = Exam::create([
            'branch_id' => null,
            'school_class_id' => $grade->id,
            'subject_id' => $subject->id,
            'title' => 'Grade 3 Maths',
            'total_marks' => 1,
            'duration_minutes' => 20,
            'maximum_attempts' => 1,
            'status' => Exam::STATUS_PUBLISHED,
            'starts_at' => now()->subMinute(),
            'ends_at' => now()->addDay(),
        ]);

        $question = Question::create([
            'exam_id' => $exam->id,
            'question_category_id' => $category->id,
            'question_text' => '<p>What is 7 + 5?</p>',
            'marks' => 1,
            'position' => 1,
            'explanation' => self::EXPLANATION,
        ]);
        $question->options()->createMany([
            ['option_text' => '12', 'is_correct' => true, 'position' => 1],
            ['option_text' => '13', 'is_correct' => false, 'position' => 2],
        ]);

        $service = app(ExamAttemptService::class);
        $attempt = $service->start($exam->fresh(), $this->student);
        $service->saveAnswer($attempt, $this->student, $question->id, $question->options()->where('is_correct', true)->value('id'));
        $this->attempt = $service->submit($attempt, $this->student);
    }

    public function test_student_sees_category_but_not_explanation(): void
    {
        $this->actingAs($this->student, 'student');
        SingleSessionService::establish($this->student, 'student');

        $this->get(route('student.results.show', $this->attempt))
            ->assertOk()
            ->assertSee('Addition')
            ->assertDontSee(self::EXPLANATION);
    }

    public function test_teacher_sees_category_and_explanation(): void
    {
        $teacher = Teacher::create([
            'branch_id' => $this->branch->id,
            'name' => 'Jane',
            'email' => 'jane@example.com',
            'phone_number' => '123',
            'password' => Hash::make('secret123'),
        ]);

        $this->actingAs($teacher, 'teacher');
        SingleSessionService::establish($teacher, 'teacher');

        $this->get(route('teacher.results.show', $this->attempt))
            ->assertOk()
            ->assertSee('Addition')
            ->assertSee(self::EXPLANATION);
    }

    public function test_branch_sees_category_and_explanation(): void
    {
        $branchUser = User::create([
            'name' => 'Ringwood',
            'email' => 'ringwood-user@example.com',
            'role' => 'Branch',
            'branch_id' => $this->branch->id,
            'password' => Hash::make('secret123'),
        ]);

        $this->actingAs($branchUser);
        SingleSessionService::establish($branchUser, 'web');

        $this->get(route('branch.results.show', $this->attempt))
            ->assertOk()
            ->assertSee('Addition')
            ->assertSee(self::EXPLANATION);
    }

    public function test_super_admin_does_not_see_explanation(): void
    {
        $admin = User::create([
            'name' => 'Super Admin',
            'email' => 'admin@example.com',
            'role' => 'Super Admin',
            'password' => Hash::make('secret123'),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.results.show', $this->attempt))
            ->assertOk()
            ->assertSee('Addition')
            ->assertDontSee(self::EXPLANATION);
    }
}
