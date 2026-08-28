<?php

namespace Tests\Feature;

use App\Models\AcademicSession;
use App\Models\Branch;
use App\Models\Exam;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use App\Services\ExamAttemptService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class GlobalExamTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_create_an_exam_without_selecting_a_branch(): void
    {
        [$admin, $class, $subject, $session] = $this->makeAdminFixture();

        $response = $this->actingAs($admin)
            ->withSession(['admin_selected_academic_session_id' => $session->id])
            ->post(route('admin.exams.store'), [
                'title' => 'Global Science Test',
                'school_class_id' => $class->id,
                'subject_id' => $subject->id,
                'starts_at' => now()->addDay()->format('Y-m-d H:i:s'),
                'ends_at' => now()->addDays(2)->format('Y-m-d H:i:s'),
                'duration_minutes' => 30,
                'maximum_attempts' => 1,
            ]);

        $response->assertRedirect(route('admin.exams.index'));
        $response->assertSessionDoesntHaveErrors();

        $this->assertDatabaseHas('exams', [
            'title' => 'Global Science Test',
            'branch_id' => null,
        ]);

        $exam = Exam::where('title', 'Global Science Test')->firstOrFail();
        $this->assertTrue($exam->isGlobal());
    }

    public function test_exam_create_form_does_not_show_branch_field_for_super_admin(): void
    {
        [$admin, , , $session] = $this->makeAdminFixture();

        $this->actingAs($admin)
            ->withSession(['admin_selected_academic_session_id' => $session->id])
            ->get(route('admin.exams.index'))
            ->assertOk()
            ->assertDontSee('Select branch');
    }

    public function test_global_exam_is_visible_in_every_branch_exam_list(): void
    {
        [$admin, $class, $subject, $session] = $this->makeAdminFixture();
        $branch = Branch::create(['name' => 'Branch A', 'email' => 'branch-a@example.com']);
        $branchUser = User::create([
            'name' => 'Branch A',
            'email' => 'branch-a-user@example.com',
            'role' => 'Branch',
            'branch_id' => $branch->id,
            'password' => Hash::make('123456'),
        ]);

        $exam = Exam::create([
            'branch_id' => null,
            'school_class_id' => $class->id,
            'subject_id' => $subject->id,
            'title' => 'Global Exam',
            'total_marks' => 0,
            'duration_minutes' => 30,
            'maximum_attempts' => 1,
            'session_id' => $session->id,
            'status' => Exam::STATUS_DRAFT,
        ]);

        $response = $this->actingAs($branchUser)
            ->withSession(['branch_selected_academic_session_id' => $session->id])
            ->get(route('branch.exams.index'));

        $response->assertOk();
        $response->assertSee($exam->title);
    }

    public function test_branch_user_can_view_but_not_manage_a_global_exam(): void
    {
        [, $class, $subject, $session] = $this->makeAdminFixture();
        $branch = Branch::create(['name' => 'Branch B', 'email' => 'branch-b@example.com']);
        $branchUser = User::create([
            'name' => 'Branch B',
            'email' => 'branch-b-user@example.com',
            'role' => 'Branch',
            'branch_id' => $branch->id,
            'password' => Hash::make('123456'),
        ]);

        $exam = Exam::create([
            'branch_id' => null,
            'school_class_id' => $class->id,
            'subject_id' => $subject->id,
            'title' => 'Global Exam',
            'total_marks' => 0,
            'duration_minutes' => 30,
            'maximum_attempts' => 1,
            'session_id' => $session->id,
            'status' => Exam::STATUS_DRAFT,
        ]);

        $this->actingAs($branchUser)
            ->withSession(['branch_selected_academic_session_id' => $session->id])
            ->get(route('branch.exams.show', $exam))
            ->assertOk();

        $this->actingAs($branchUser)
            ->withSession(['branch_selected_academic_session_id' => $session->id])
            ->get(route('branch.exams.edit', $exam))
            ->assertForbidden();

        $this->actingAs($branchUser)
            ->put(route('branch.exams.update', $exam), ['title' => 'Hijacked'])
            ->assertForbidden();

        $this->actingAs($branchUser)
            ->delete(route('branch.exams.destroy', $exam))
            ->assertForbidden();
    }

    public function test_student_can_attempt_a_global_exam_regardless_of_branch(): void
    {
        [, $class, $subject, $session] = $this->makeAdminFixture();
        $branch = Branch::create(['name' => 'Branch C', 'email' => 'branch-c@example.com']);

        $student = Student::create([
            'branch_id' => $branch->id,
            'class_id' => $class->id,
            'session_id' => $session->id,
            'student_name' => 'Global Student',
            'guardian_name' => 'Guardian',
            'class' => $class->name,
            'phone_number' => '9876543210',
            'email' => 'global-student@example.com',
            'is_active' => true,
        ]);
        $student->subjects()->attach($subject->id);

        $exam = Exam::create([
            'branch_id' => null,
            'school_class_id' => $class->id,
            'subject_id' => $subject->id,
            'title' => 'Global Exam',
            'total_marks' => 10,
            'duration_minutes' => 30,
            'maximum_attempts' => 1,
            'session_id' => $session->id,
            'status' => Exam::STATUS_PUBLISHED,
            'starts_at' => now()->subMinute(),
            'ends_at' => now()->addHour(),
        ]);

        $exam->questions()->create([
            'question_text' => '2 + 2 = ?',
            'question_type' => 'mcq',
            'marks' => 10,
        ]);

        $attempt = app(ExamAttemptService::class)->start($exam, $student);

        $this->assertSame($exam->id, $attempt->exam_id);
        $this->assertSame($student->id, $attempt->student_id);
        $this->assertSame($branch->id, $attempt->branch_id);
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
        $subject = \App\Models\Subject::create(['name' => 'Science']);

        $session = AcademicSession::create([
            'name' => '2026-2027',
            'start_date' => '2026-06-01',
            'end_date' => '2027-05-31',
            'is_active' => true,
        ]);

        return [$admin, $class, $subject, $session];
    }
}
