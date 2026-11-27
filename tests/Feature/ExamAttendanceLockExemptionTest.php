<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Attempts by the testing Student ID NL1405 must never trigger the
 * attendance-based exam lock; every other student still locks the exam.
 */
class ExamAttendanceLockExemptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_exempt_student_attempt_does_not_lock_exam(): void
    {
        [$branch, $class, $exam] = $this->makeExam();
        $this->attempt($exam, $this->makeStudent($branch, $class, 'NL1405'));

        $this->assertFalse($exam->hasBeenAttempted());
    }

    public function test_other_student_attempt_still_locks_exam(): void
    {
        [$branch, $class, $exam] = $this->makeExam();
        $this->attempt($exam, $this->makeStudent($branch, $class, 'NL1184'));

        $this->assertTrue($exam->hasBeenAttempted());
    }

    public function test_student_without_id_still_locks_exam(): void
    {
        [$branch, $class, $exam] = $this->makeExam();
        $this->attempt($exam, $this->makeStudent($branch, $class, null));

        $this->assertTrue($exam->hasBeenAttempted());
    }

    public function test_exempt_plus_other_student_attempts_still_lock_exam(): void
    {
        [$branch, $class, $exam] = $this->makeExam();
        $this->attempt($exam, $this->makeStudent($branch, $class, 'NL1405'));
        $this->attempt($exam, $this->makeStudent($branch, $class, 'NL1184'));

        $this->assertTrue($exam->hasBeenAttempted());
    }

    public function test_admin_can_unpublish_and_delete_exam_attended_only_by_exempt_student(): void
    {
        [$branch, $class, $exam] = $this->makeExam();
        $this->attempt($exam, $this->makeStudent($branch, $class, 'NL1405'));
        $admin = $this->makeUser('Super Admin', null);

        $this->actingAs($admin)
            ->postJson(route('admin.exams.unpublish', $exam))
            ->assertOk();
        $this->assertSame(Exam::STATUS_DRAFT, $exam->fresh()->status);

        $this->actingAs($admin)->delete(route('admin.exams.destroy', $exam));
        $this->assertDatabaseMissing('exams', ['id' => $exam->id]);
    }

    public function test_admin_cannot_unpublish_or_delete_exam_attended_by_other_student(): void
    {
        [$branch, $class, $exam] = $this->makeExam();
        $this->attempt($exam, $this->makeStudent($branch, $class, 'NL1184'));
        $admin = $this->makeUser('Super Admin', null);

        $this->actingAs($admin)
            ->postJson(route('admin.exams.unpublish', $exam))
            ->assertStatus(422)
            ->assertJson(['message' => Exam::UNPUBLISH_LOCK_MESSAGE]);

        $this->actingAs($admin)->delete(route('admin.exams.destroy', $exam))
            ->assertSessionHas('error', Exam::DELETE_LOCK_MESSAGE);
        $this->assertDatabaseHas('exams', ['id' => $exam->id, 'status' => Exam::STATUS_PUBLISHED]);
    }

    public function test_branch_can_unpublish_and_delete_exam_attended_only_by_exempt_student(): void
    {
        [$branch, $class, $exam] = $this->makeExam();
        $this->attempt($exam, $this->makeStudent($branch, $class, 'NL1405'));
        $branchUser = $this->makeUser('Branch', $branch->id);

        $this->actingAs($branchUser)
            ->postJson(route('branch.exams.unpublish', $exam))
            ->assertOk();

        $this->actingAs($branchUser)->delete(route('branch.exams.destroy', $exam));
        $this->assertDatabaseMissing('exams', ['id' => $exam->id]);
    }

    public function test_branch_cannot_delete_exam_attended_by_other_student(): void
    {
        [$branch, $class, $exam] = $this->makeExam();
        $this->attempt($exam, $this->makeStudent($branch, $class, 'NL1184'));
        $branchUser = $this->makeUser('Branch', $branch->id);

        $this->actingAs($branchUser)->delete(route('branch.exams.destroy', $exam))
            ->assertSessionHas('error', Exam::DELETE_LOCK_MESSAGE);
        $this->assertDatabaseHas('exams', ['id' => $exam->id]);
    }

    private function makeExam(): array
    {
        $branch = Branch::create(['name' => 'Main Branch', 'email' => 'main@example.com']);
        $class = SchoolClass::create(['branch_id' => $branch->id, 'name' => 'Class 10']);

        $exam = Exam::create([
            'branch_id' => $branch->id,
            'school_class_id' => $class->id,
            'title' => 'Algebra Basics',
            'total_marks' => 10,
            'duration_minutes' => 30,
            'maximum_attempts' => 1,
            'status' => Exam::STATUS_PUBLISHED,
        ]);

        return [$branch, $class, $exam];
    }

    private function makeStudent(Branch $branch, SchoolClass $class, ?string $nrichId): Student
    {
        static $n = 0;
        $n++;

        return Student::create([
            'branch_id' => $branch->id,
            'class_id' => $class->id,
            'student_name' => "Student {$n}",
            'guardian_name' => 'Guardian',
            'class' => $class->name,
            'phone_number' => '98765432'.str_pad((string) $n, 2, '0', STR_PAD_LEFT),
            'email' => "student{$n}@example.com",
            'zoho_student_id' => $nrichId,
            'is_active' => true,
        ]);
    }

    private function attempt(Exam $exam, Student $student): void
    {
        ExamAttempt::create([
            'exam_id' => $exam->id,
            'student_id' => $student->id,
            'branch_id' => $student->branch_id,
            'school_class_id' => $student->class_id,
            'attempt_number' => 1,
            'started_at' => now()->subMinute(),
            'expires_at' => now()->addMinutes(20),
            'status' => 'in_progress',
        ]);
    }

    private function makeUser(string $role, ?int $branchId): User
    {
        return User::create([
            'name' => $role.' User',
            'email' => strtolower(str_replace(' ', '-', $role)).'@example.com',
            'role' => $role,
            'branch_id' => $branchId,
            'password' => Hash::make('123456'),
        ]);
    }
}
