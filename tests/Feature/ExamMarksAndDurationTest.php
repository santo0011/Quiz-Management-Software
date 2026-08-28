<?php

namespace Tests\Feature;

use App\Models\AcademicSession;
use App\Models\Branch;
use App\Models\Exam;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ExamMarksAndDurationTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_sets_total_marks_pass_marks_and_duration_at_creation(): void
    {
        [$admin, $class, $subject, $session] = $this->makeAdminFixture();

        $this->actingAs($admin)
            ->withSession(['admin_selected_academic_session_id' => $session->id])
            ->post(route('admin.exams.store'), [
                'title' => 'Math Test',
                'school_class_id' => $class->id,
                'subject_id' => $subject->id,
                'starts_at' => now()->addDay()->format('Y-m-d H:i:s'),
                'ends_at' => now()->addDays(2)->format('Y-m-d H:i:s'),
                'total_marks' => 50,
                'passing_marks' => 20,
                'duration_minutes' => 45,
                'maximum_attempts' => 1,
            ])
            ->assertRedirect(route('admin.exams.index'));

        $this->assertDatabaseHas('exams', [
            'title' => 'Math Test',
            'total_marks' => 50,
            'passing_marks' => 20,
            'duration_minutes' => 45,
        ]);
    }

    public function test_branch_sets_total_marks_pass_marks_and_duration_at_creation(): void
    {
        [$branch, $branchUser, $class, $subject, $session] = $this->makeBranchFixture();

        $this->actingAs($branchUser)
            ->withSession(['branch_selected_academic_session_id' => $session->id])
            ->post(route('branch.exams.store'), [
                'title' => 'Branch Science Test',
                'school_class_id' => $class->id,
                'subject_id' => $subject->id,
                'starts_at' => now()->addDay()->format('Y-m-d H:i:s'),
                'ends_at' => now()->addDays(2)->format('Y-m-d H:i:s'),
                'total_marks' => 30,
                'passing_marks' => 12,
                'duration_minutes' => 20,
                'maximum_attempts' => 1,
            ])
            ->assertRedirect(route('branch.exams.index'));

        $this->assertDatabaseHas('exams', [
            'title' => 'Branch Science Test',
            'branch_id' => $branch->id,
            'total_marks' => 30,
            'passing_marks' => 12,
            'duration_minutes' => 20,
        ]);
    }

    public function test_duration_is_required_when_creating_an_exam(): void
    {
        [$admin, $class, $subject, $session] = $this->makeAdminFixture();

        $this->actingAs($admin)
            ->withSession(['admin_selected_academic_session_id' => $session->id])
            ->post(route('admin.exams.store'), [
                'title' => 'No Duration Test',
                'school_class_id' => $class->id,
                'subject_id' => $subject->id,
                'starts_at' => now()->addDay()->format('Y-m-d H:i:s'),
                'ends_at' => now()->addDays(2)->format('Y-m-d H:i:s'),
                'maximum_attempts' => 1,
            ])
            ->assertSessionHasErrors('duration_minutes');
    }

    public function test_total_marks_defaults_to_zero_when_left_blank_on_creation(): void
    {
        [$admin, $class, $subject, $session] = $this->makeAdminFixture();

        $this->actingAs($admin)
            ->withSession(['admin_selected_academic_session_id' => $session->id])
            ->post(route('admin.exams.store'), [
                'title' => 'Blank Marks Test',
                'school_class_id' => $class->id,
                'subject_id' => $subject->id,
                'starts_at' => now()->addDay()->format('Y-m-d H:i:s'),
                'ends_at' => now()->addDays(2)->format('Y-m-d H:i:s'),
                'duration_minutes' => 30,
                'maximum_attempts' => 1,
            ])
            ->assertSessionDoesntHaveErrors();

        $this->assertDatabaseHas('exams', [
            'title' => 'Blank Marks Test',
            'total_marks' => 0,
        ]);
    }

    public function test_total_marks_recalculates_automatically_once_questions_are_added(): void
    {
        [$admin, $class, $subject, $session] = $this->makeAdminFixture();

        $exam = Exam::create([
            'branch_id' => null,
            'school_class_id' => $class->id,
            'subject_id' => $subject->id,
            'title' => 'Recalculated Exam',
            'total_marks' => 15,
            'passing_marks' => 5,
            'duration_minutes' => 30,
            'maximum_attempts' => 1,
            'session_id' => $session->id,
            'status' => Exam::STATUS_DRAFT,
        ]);

        $this->assertSame(15, $exam->total_marks);

        $category = \App\Models\QuestionCategory::create(['branch_id' => null, 'name' => 'General']);

        $this->actingAs($admin)->post(route('admin.questions.store', $exam), [
            'questions' => [
                [
                    'question_text' => '2 + 2 = ?',
                    'marks' => 5,
                    'question_category_id' => $category->id,
                    'options' => ['4', '5', '6', '7'],
                    'correct_option' => 0,
                ],
                [
                    'question_text' => '3 + 3 = ?',
                    'marks' => 7,
                    'question_category_id' => $category->id,
                    'options' => ['6', '7', '8', '9'],
                    'correct_option' => 0,
                ],
            ],
        ])->assertRedirect();

        $exam->refresh();
        $this->assertSame(12, $exam->total_marks);
    }

    public function test_total_marks_field_stays_editable_even_when_exam_has_questions(): void
    {
        [$admin, $class, $subject, $session] = $this->makeAdminFixture();

        $exam = Exam::create([
            'branch_id' => null,
            'school_class_id' => $class->id,
            'subject_id' => $subject->id,
            'title' => 'Editable Total Marks Exam',
            'total_marks' => 10,
            'duration_minutes' => 30,
            'maximum_attempts' => 1,
            'session_id' => $session->id,
            'status' => Exam::STATUS_DRAFT,
        ]);

        $exam->questions()->create([
            'question_text' => 'Q1',
            'question_type' => 'mcq',
            'marks' => 10,
        ]);
        $exam->recalculateTotalMarks();

        $response = $this->actingAs($admin)
            ->withSession(['admin_selected_academic_session_id' => $session->id])
            ->get(route('admin.exams.edit', $exam));

        $response->assertOk();
        $response->assertSee('name="total_marks"', false);
        $response->assertDontSee('Calculated automatically', false);
    }

    public function test_total_marks_can_be_changed_manually_even_when_exam_has_questions(): void
    {
        [$admin, $class, $subject, $session] = $this->makeAdminFixture();

        $exam = Exam::create([
            'branch_id' => null,
            'school_class_id' => $class->id,
            'subject_id' => $subject->id,
            'title' => 'Manual Total Marks Exam',
            'total_marks' => 10,
            'duration_minutes' => 30,
            'maximum_attempts' => 1,
            'starts_at' => now()->addDay(),
            'ends_at' => now()->addDays(2),
            'session_id' => $session->id,
            'status' => Exam::STATUS_DRAFT,
        ]);

        $exam->questions()->create([
            'question_text' => 'Q1',
            'question_type' => 'mcq',
            'marks' => 10,
        ]);
        $exam->recalculateTotalMarks();

        $this->actingAs($admin)
            ->withSession(['admin_selected_academic_session_id' => $session->id])
            ->put(route('admin.exams.update', $exam), [
                'school_class_id' => $class->id,
                'subject_id' => $subject->id,
                'title' => $exam->title,
                'starts_at' => $exam->starts_at->format('Y-m-d H:i:s'),
                'ends_at' => $exam->ends_at->format('Y-m-d H:i:s'),
                'total_marks' => 25,
                'duration_minutes' => 30,
                'maximum_attempts' => 1,
            ])
            ->assertRedirect(route('admin.exams.index'));

        $exam->refresh();
        $this->assertSame(25, $exam->total_marks);
    }

    public function test_question_management_page_no_longer_shows_settings_modal(): void
    {
        [$admin, $class, $subject, $session] = $this->makeAdminFixture();

        $exam = Exam::create([
            'branch_id' => null,
            'school_class_id' => $class->id,
            'subject_id' => $subject->id,
            'title' => 'No Modal Exam',
            'total_marks' => 0,
            'duration_minutes' => 30,
            'maximum_attempts' => 1,
            'session_id' => $session->id,
            'status' => Exam::STATUS_DRAFT,
        ]);

        \App\Models\QuestionCategory::create(['branch_id' => null, 'name' => 'General']);

        $response = $this->actingAs($admin)
            ->withSession(['admin_selected_academic_session_id' => $session->id])
            ->get(route('admin.questions.create', $exam));

        $response->assertOk();
        $response->assertDontSee('examSettingsModal');
        $response->assertDontSee('Finish Setting Up This Exam');
    }

    private function makeAdminFixture(): array
    {
        $admin = User::create([
            'name' => 'Super Admin',
            'email' => 'admin@example.com',
            'role' => 'Super Admin',
            'password' => Hash::make('123456'),
        ]);

        $class = SchoolClass::create(['branch_id' => null, 'name' => 'Class 10']);
        $subject = Subject::create(['name' => 'Science']);

        $session = AcademicSession::create([
            'name' => '2026-2027',
            'start_date' => '2026-06-01',
            'end_date' => '2027-05-31',
            'is_active' => true,
        ]);

        return [$admin, $class, $subject, $session];
    }

    private function makeBranchFixture(): array
    {
        $branch = Branch::create(['name' => 'Branch A', 'email' => 'branch-a@example.com']);
        $branchUser = User::create([
            'name' => 'Branch A',
            'email' => 'branch-a-user@example.com',
            'role' => 'Branch',
            'branch_id' => $branch->id,
            'password' => Hash::make('123456'),
        ]);

        $class = SchoolClass::create(['branch_id' => $branch->id, 'name' => 'Class 8']);
        $subject = Subject::create(['name' => 'English']);

        $session = AcademicSession::create([
            'name' => '2026-2027',
            'start_date' => '2026-06-01',
            'end_date' => '2027-05-31',
            'is_active' => true,
        ]);

        return [$branch, $branchUser, $class, $subject, $session];
    }
}
