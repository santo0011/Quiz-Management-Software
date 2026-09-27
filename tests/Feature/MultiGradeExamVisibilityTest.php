<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Exam;
use App\Models\Question;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use App\Services\ExamAttemptService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * One Exam → many Grades, and who can see an Exam:
 *  - Super Admin exams: Super Admin + every branch.
 *  - Branch exams: Super Admin + the creating branch only.
 */
class MultiGradeExamVisibilityTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Subject $subject;

    private SchoolClass $grade5;

    private SchoolClass $grade6;

    private SchoolClass $grade7;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'name' => 'Super Admin',
            'email' => 'super-admin@example.com',
            'role' => 'Super Admin',
            'password' => Hash::make('123456'),
        ]);

        $this->subject = Subject::create(['name' => 'Mathematics']);
        $this->grade5 = SchoolClass::create(['branch_id' => null, 'name' => 'Grade 5']);
        $this->grade6 = SchoolClass::create(['branch_id' => null, 'name' => 'Grade 6']);
        $this->grade7 = SchoolClass::create(['branch_id' => null, 'name' => 'Grade 7']);
    }

    // --- Multiple Grades: creation ---

    public function test_super_admin_creates_one_global_exam_for_multiple_grades(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.exams.store'), $this->examPayload('Global Multi Grade', [$this->grade5->id, $this->grade6->id]))
            ->assertRedirect(route('admin.exams.index'))
            ->assertSessionDoesntHaveErrors();

        $this->assertSame(1, Exam::where('title', 'Global Multi Grade')->count(), 'Exam must not be duplicated per grade.');

        $exam = Exam::where('title', 'Global Multi Grade')->firstOrFail();
        $this->assertNull($exam->branch_id);
        $this->assertEqualsCanonicalizing([$this->grade5->id, $this->grade6->id], $exam->grades->pluck('id')->all());
        $this->assertSame($this->grade5->id, $exam->school_class_id);
    }

    public function test_branch_creates_one_branch_only_exam_for_multiple_grades(): void
    {
        [$branch, $branchUser] = $this->makeBranch('North');

        $this->actingAs($branchUser)
            ->post(route('branch.exams.store'), $this->examPayload('Branch Multi Grade', [$this->grade6->id, $this->grade7->id]))
            ->assertRedirect(route('branch.exams.index'))
            ->assertSessionDoesntHaveErrors();

        $this->assertSame(1, Exam::where('title', 'Branch Multi Grade')->count());

        $exam = Exam::where('title', 'Branch Multi Grade')->firstOrFail();
        $this->assertSame($branch->id, $exam->branch_id);
        $this->assertEqualsCanonicalizing([$this->grade6->id, $this->grade7->id], $exam->grades->pluck('id')->all());
    }

    public function test_teacher_panel_no_longer_manages_exams(): void
    {
        [$branch] = $this->makeBranch('Teacher Branch');
        $teacher = Teacher::create([
            'branch_id' => $branch->id,
            'name' => 'Teacher',
            'email' => 'teacher@example.com',
            'phone_number' => '123',
            'password' => Hash::make('123456'),
        ]);

        $this->actingAs($teacher, 'teacher')->get('/teacher/exams')->assertNotFound();
        $this->actingAs($teacher, 'teacher')->post('/teacher/exams', $this->examPayload('Teacher Multi Grade', [$this->grade5->id]))->assertNotFound();
        $this->actingAs($teacher, 'teacher')->get('/teacher/questions')->assertNotFound();
        $this->actingAs($teacher, 'teacher')->get('/teacher/question-categories')->assertNotFound();

        $this->assertDatabaseMissing('exams', ['title' => 'Teacher Multi Grade']);
    }

    public function test_at_least_one_grade_is_required(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.exams.store'), $this->examPayload('No Grades', []))
            ->assertSessionHasErrors(['school_class_ids' => 'Please select at least one grade for this exam.']);

        $this->assertDatabaseMissing('exams', ['title' => 'No Grades']);
    }

    public function test_legacy_single_grade_field_is_still_accepted(): void
    {
        $payload = $this->examPayload('Legacy Single Grade', []);
        unset($payload['school_class_ids']);
        $payload['school_class_id'] = $this->grade6->id;

        $this->actingAs($this->admin)
            ->post(route('admin.exams.store'), $payload)
            ->assertSessionDoesntHaveErrors();

        $exam = Exam::where('title', 'Legacy Single Grade')->firstOrFail();
        $this->assertSame([$this->grade6->id], $exam->grades->pluck('id')->all());
    }

    public function test_branch_cannot_assign_its_exam_to_another_branchs_grade(): void
    {
        [, $northUser] = $this->makeBranch('North');
        [$south] = $this->makeBranch('South');
        $southOnlyGrade = SchoolClass::create(['branch_id' => $south->id, 'name' => 'South Grade 8']);

        $this->actingAs($northUser)
            ->post(route('branch.exams.store'), $this->examPayload('Sneaky Exam', [$this->grade5->id, $southOnlyGrade->id]))
            ->assertSessionHasErrors('school_class_ids');

        $this->assertDatabaseMissing('exams', ['title' => 'Sneaky Exam']);
    }

    public function test_updating_an_exam_replaces_its_grades_without_duplicating_it(): void
    {
        [, $branchUser] = $this->makeBranch('North');

        $this->actingAs($branchUser)->post(route('branch.exams.store'), $this->examPayload('Editable Exam', [$this->grade5->id, $this->grade6->id]));
        $exam = Exam::where('title', 'Editable Exam')->firstOrFail();

        $this->actingAs($branchUser)
            ->put(route('branch.exams.update', $exam), $this->examPayload('Editable Exam', [$this->grade6->id, $this->grade7->id]))
            ->assertRedirect(route('branch.exams.index'))
            ->assertSessionDoesntHaveErrors();

        $exam->refresh();
        $this->assertSame(1, Exam::where('title', 'Editable Exam')->count());
        $this->assertEqualsCanonicalizing([$this->grade6->id, $this->grade7->id], $exam->grades->pluck('id')->all());
        $this->assertSame($this->grade6->id, $exam->school_class_id);
    }

    public function test_edit_form_pre_checks_the_exams_grades(): void
    {
        $exam = $this->makeExam('Prechecked Exam', null, [$this->grade5, $this->grade7]);

        $html = $this->actingAs($this->admin)->get(route('admin.exams.edit', $exam))->assertOk()->getContent();

        $this->assertMatchesRegularExpression('/value="'.$this->grade5->id.'"[^>]*checked/', $html);
        $this->assertMatchesRegularExpression('/value="'.$this->grade7->id.'"[^>]*checked/', $html);
        $this->assertDoesNotMatchRegularExpression('/value="'.$this->grade6->id.'"[^>]*checked/', $html);
    }

    // --- Student eligibility across multiple Grades ---

    public function test_students_in_any_selected_grade_are_eligible_and_others_are_not(): void
    {
        [$branch] = $this->makeBranch('North');
        $exam = $this->makeExam('Grades 5 and 6 Exam', null, [$this->grade5, $this->grade6], published: true);

        $grade5Student = $this->makeStudent($branch, $this->grade5);
        $grade6Student = $this->makeStudent($branch, $this->grade6);
        $grade7Student = $this->makeStudent($branch, $this->grade7);

        $this->actingAs($grade5Student, 'student')->get(route('student.exams.available'))->assertSee('Grades 5 and 6 Exam');
        $this->actingAs($grade6Student, 'student')->get(route('student.exams.available'))->assertSee('Grades 5 and 6 Exam');
        $this->actingAs($grade7Student, 'student')->get(route('student.exams.available'))->assertDontSee('Grades 5 and 6 Exam');

        $this->actingAs($grade6Student, 'student')->get(route('student.exams.show', $exam))->assertOk();
        $this->actingAs($grade7Student, 'student')->get(route('student.exams.show', $exam))->assertForbidden();
    }

    public function test_student_in_a_secondary_grade_can_start_the_attempt_and_it_records_their_grade(): void
    {
        [$branch] = $this->makeBranch('North');
        $exam = $this->makeExam('Attemptable Exam', null, [$this->grade5, $this->grade6], published: true);
        $grade6Student = $this->makeStudent($branch, $this->grade6);

        $attempt = app(ExamAttemptService::class)->start($exam->load('questions'), $grade6Student);

        $this->assertSame($grade6Student->class_id, $attempt->school_class_id);
    }

    public function test_student_outside_the_selected_grades_cannot_start_the_attempt(): void
    {
        [$branch] = $this->makeBranch('North');
        $exam = $this->makeExam('Locked Exam', null, [$this->grade5, $this->grade6], published: true);
        $grade7Student = $this->makeStudent($branch, $this->grade7);

        $this->expectException(ValidationException::class);
        app(ExamAttemptService::class)->start($exam->load('questions'), $grade7Student);
    }

    public function test_subject_matching_is_still_required_with_multiple_grades(): void
    {
        [$branch] = $this->makeBranch('North');
        $this->makeExam('Maths Multi Grade', null, [$this->grade5, $this->grade6], published: true);
        $otherSubject = Subject::create(['name' => 'Science']);
        $scienceOnlyStudent = $this->makeStudent($branch, $this->grade5, $otherSubject);

        $this->actingAs($scienceOnlyStudent, 'student')
            ->get(route('student.exams.available'))
            ->assertDontSee('Maths Multi Grade');
    }

    public function test_removing_a_grade_removes_eligibility_for_that_grade(): void
    {
        [$branch] = $this->makeBranch('North');
        $exam = $this->makeExam('Shrinking Exam', null, [$this->grade5, $this->grade6], published: true);
        $grade6Student = $this->makeStudent($branch, $this->grade6);

        $exam->syncGrades([$this->grade5->id]);

        $this->assertFalse(Exam::eligibleForStudent($grade6Student)->whereKey($exam->id)->exists());
    }

    // --- Visibility ---

    public function test_super_admin_exam_is_visible_to_every_branch(): void
    {
        [, $northUser] = $this->makeBranch('North');
        [, $southUser] = $this->makeBranch('South');
        $exam = $this->makeExam('Global Visible Exam', null, [$this->grade5]);

        foreach ([$northUser, $southUser] as $branchUser) {
            $this->actingAs($branchUser)->get(route('branch.exams.index'))->assertSee('Global Visible Exam');
            $this->actingAs($branchUser)->get(route('branch.exams.show', $exam))->assertOk();
        }
    }

    public function test_branch_exam_is_visible_to_super_admin_and_its_own_branch_only(): void
    {
        [$north, $northUser] = $this->makeBranch('North');
        [, $southUser] = $this->makeBranch('South');
        $exam = $this->makeExam('North Private Exam', $north, [$this->grade5, $this->grade6]);

        $this->actingAs($this->admin)->get(route('admin.exams.index'))->assertSee('North Private Exam');
        $this->actingAs($this->admin)->get(route('admin.exams.show', $exam))->assertOk();

        $this->actingAs($northUser)->get(route('branch.exams.index'))->assertSee('North Private Exam');
        $this->actingAs($northUser)->get(route('branch.exams.show', $exam))->assertOk();

        $this->actingAs($southUser)->get(route('branch.exams.index'))->assertDontSee('North Private Exam');
    }

    public function test_other_branch_cannot_access_a_branch_exam_by_url(): void
    {
        [$north] = $this->makeBranch('North');
        [$south, $southUser] = $this->makeBranch('South');
        $exam = $this->makeExam('North Only Exam', $north, [$this->grade5]);

        $this->actingAs($southUser)->get(route('branch.exams.show', $exam))->assertForbidden();
        $this->actingAs($southUser)->get(route('branch.exams.edit', $exam))->assertForbidden();
        $this->actingAs($southUser)->put(route('branch.exams.update', $exam), $this->examPayload('Hijacked', [$this->grade5->id]))->assertForbidden();
        $this->actingAs($southUser)->post(route('branch.exams.publish', $exam))->assertForbidden();
        $this->actingAs($southUser)->delete(route('branch.exams.destroy', $exam))->assertForbidden();
        $this->actingAs($southUser)->get(route('branch.questions.create', $exam))->assertForbidden();

        $this->assertDatabaseHas('exams', ['id' => $exam->id, 'title' => 'North Only Exam']);
    }

    public function test_student_of_another_branch_cannot_see_or_start_a_branch_exam_even_with_matching_grade(): void
    {
        [$north] = $this->makeBranch('North');
        [$south] = $this->makeBranch('South');
        $exam = $this->makeExam('North Student Exam', $north, [$this->grade5], published: true);

        $southStudent = $this->makeStudent($south, $this->grade5);
        $northStudent = $this->makeStudent($north, $this->grade5);

        $this->actingAs($northStudent, 'student')->get(route('student.exams.available'))->assertSee('North Student Exam');
        $this->actingAs($southStudent, 'student')->get(route('student.exams.available'))->assertDontSee('North Student Exam');
        $this->actingAs($southStudent, 'student')->get(route('student.exams.show', $exam))->assertForbidden();

        $this->expectException(ValidationException::class);
        app(ExamAttemptService::class)->start($exam->load('questions'), $southStudent);
    }

    // --- Exam List ---

    public function test_exam_list_shows_grades_and_scope(): void
    {
        [$north, $northUser] = $this->makeBranch('North Campus');
        $this->makeExam('Global Listed Exam', null, [$this->grade5, $this->grade6]);
        $this->makeExam('Branch Listed Exam', $north, [$this->grade7]);

        $this->actingAs($this->admin)->get(route('admin.exams.index'))
            ->assertOk()
            ->assertSeeInOrder(['Exam Name', 'Subject', 'Grade(s)', 'Scope', 'Status', 'Actions'])
            ->assertDontSee('Created By')
            ->assertDontSee('(30 minutes)')
            ->assertSee('Grade 5, Grade 6')
            ->assertSee('All Branches')
            ->assertSee('Branch Only')
            ->assertSee('Mathematics');

        // The Branch exam list has no Scope column.
        $this->actingAs($northUser)->get(route('branch.exams.index'))
            ->assertOk()
            ->assertSee('Global Listed Exam')
            ->assertSee('Branch Listed Exam')
            ->assertSeeInOrder(['Exam Name', 'Subject', 'Grade(s)', 'Questions', 'Status', 'Actions'])
            ->assertDontSee('<th>Scope</th>', false)
            ->assertDontSee('scope-badge', false);
    }

    public function test_super_admin_can_filter_the_exam_list_by_scope(): void
    {
        [$north] = $this->makeBranch('North');
        $this->makeExam('Filter Global Exam', null, [$this->grade5]);
        $this->makeExam('Filter Branch Exam', $north, [$this->grade5]);

        $this->actingAs($this->admin)->get(route('admin.exams.index', ['scope' => 'global']))
            ->assertSee('Filter Global Exam')
            ->assertDontSee('Filter Branch Exam');

        $this->actingAs($this->admin)->get(route('admin.exams.index', ['scope' => 'branch']))
            ->assertSee('Filter Branch Exam')
            ->assertDontSee('Filter Global Exam');
    }

    // --- Data integrity ---

    public function test_exam_created_with_only_a_primary_grade_is_assigned_to_it(): void
    {
        $exam = Exam::create([
            'branch_id' => null,
            'school_class_id' => $this->grade5->id,
            'subject_id' => $this->subject->id,
            'title' => 'Primary Only',
            'total_marks' => 0,
            'duration_minutes' => 30,
            'maximum_attempts' => 1,
            'status' => Exam::STATUS_DRAFT,
        ]);

        $this->assertSame([$this->grade5->id], $exam->grades()->pluck('school_classes.id')->all());
    }

    public function test_a_grade_used_by_any_exam_cannot_be_deleted(): void
    {
        $this->makeExam('Secondary Grade Exam', null, [$this->grade5, $this->grade6]);

        $this->actingAs($this->admin)
            ->delete(route('admin.classes.destroy', $this->grade6))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('school_classes', ['id' => $this->grade6->id]);
    }

    public function test_deleting_an_exam_removes_its_grade_links(): void
    {
        $exam = $this->makeExam('Deletable Exam', null, [$this->grade5, $this->grade6]);

        $this->actingAs($this->admin)->delete(route('admin.exams.destroy', $exam))->assertSessionHas('success');

        $this->assertSame(0, DB::table('exam_school_class')->where('exam_id', $exam->id)->count());
    }

    // --- Helpers ---

    private function examPayload(string $title, array $gradeIds): array
    {
        return [
            'title' => $title,
            'school_class_ids' => $gradeIds,
            'subject_id' => $this->subject->id,
            'starts_at' => now()->addDay()->format('Y-m-d H:i:s'),
            'ends_at' => now()->addDays(2)->format('Y-m-d H:i:s'),
            'duration_minutes' => 30,
            'maximum_attempts' => 1,
        ];
    }

    /**
     * @return array{0: Branch, 1: User}
     */
    private function makeBranch(string $name): array
    {
        $slug = strtolower(str_replace(' ', '-', $name)).'-'.uniqid();
        $branch = Branch::create(['name' => $name, 'email' => $slug.'@example.com', 'is_active' => true]);
        $user = User::create([
            'name' => $name,
            'email' => 'user-'.$slug.'@example.com',
            'role' => 'Branch',
            'branch_id' => $branch->id,
            'password' => Hash::make('123456'),
        ]);

        return [$branch, $user];
    }

    /**
     * @param  array<int, SchoolClass>  $grades
     */
    private function makeExam(string $title, ?Branch $branch, array $grades, bool $published = false): Exam
    {
        $exam = Exam::create([
            'branch_id' => $branch?->id,
            'school_class_id' => $grades[0]->id,
            'subject_id' => $this->subject->id,
            'title' => $title,
            'total_marks' => 1,
            'duration_minutes' => 30,
            'maximum_attempts' => 1,
            'status' => $published ? Exam::STATUS_PUBLISHED : Exam::STATUS_DRAFT,
            'starts_at' => now()->subMinute(),
            'ends_at' => now()->addHour(),
        ]);

        $exam->syncGrades(array_map(fn (SchoolClass $grade) => $grade->id, $grades));

        $question = Question::create([
            'exam_id' => $exam->id,
            'question_text' => 'What is 2 + 2?',
            'marks' => 1,
            'position' => 1,
        ]);
        $question->options()->createMany([
            ['option_text' => '4', 'is_correct' => true, 'position' => 1],
            ['option_text' => '5', 'is_correct' => false, 'position' => 2],
        ]);

        return $exam;
    }

    private function makeStudent(Branch $branch, SchoolClass $grade, ?Subject $subject = null): Student
    {
        $student = Student::create([
            'branch_id' => $branch->id,
            'class_id' => $grade->id,
            'student_name' => 'Student '.uniqid(),
            'guardian_name' => 'Guardian',
            'class' => $grade->name,
            'phone_number' => '9876543210',
            'email' => 'student-'.uniqid().'@example.com',
            'is_active' => true,
        ]);

        $student->subjects()->attach(($subject ?? $this->subject)->id);

        return $student;
    }
}
