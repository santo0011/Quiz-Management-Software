<?php

namespace Tests\Feature;

use App\Mail\ResultRemarkMail;
use App\Mail\TeacherCredentialsMail;
use App\Mail\TeacherLoginOtpMail;
use App\Models\Branch;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class TeacherManagementTest extends TestCase
{
    use RefreshDatabase;

    // --- Branch creates & manages Teachers ---

    public function test_branch_can_create_a_teacher_and_credentials_are_emailed(): void
    {
        Mail::fake();

        [$branchUser, $branch] = $this->makeBranchUser();

        $response = $this->actingAs($branchUser)->post(route('branch.teachers.store'), [
            'name' => 'Jane Teacher',
            'email' => 'jane.teacher@example.com',
            'phone_number' => '9876543210',
        ]);

        $response->assertRedirect(route('branch.teachers.index'));

        $teacher = Teacher::where('email', 'jane.teacher@example.com')->firstOrFail();
        $this->assertSame($branch->id, $teacher->branch_id);
        $this->assertNotNull($teacher->password);

        Mail::assertSent(TeacherCredentialsMail::class, function (TeacherCredentialsMail $mail) use ($teacher) {
            return $mail->hasTo($teacher->email) && Hash::check($mail->temporaryPassword, $teacher->password);
        });
    }

    public function test_branch_cannot_create_teacher_with_duplicate_email(): void
    {
        Mail::fake();
        [$branchUser, $branch] = $this->makeBranchUser();
        Teacher::create([
            'branch_id' => $branch->id,
            'name' => 'Existing',
            'email' => 'dup@example.com',
            'phone_number' => '111',
            'password' => Hash::make('secret123'),
        ]);

        $this->actingAs($branchUser)->post(route('branch.teachers.store'), [
            'name' => 'New Teacher',
            'email' => 'dup@example.com',
            'phone_number' => '222',
        ])->assertSessionHasErrors('email');
    }

    public function test_branch_can_reset_a_teachers_password(): void
    {
        [$branchUser, $branch] = $this->makeBranchUser();
        $teacher = Teacher::create([
            'branch_id' => $branch->id,
            'name' => 'Jane',
            'email' => 'jane@example.com',
            'phone_number' => '123',
            'password' => Hash::make('old-password'),
        ]);

        $this->actingAs($branchUser)->put(route('branch.teachers.password.update', $teacher), [
            'password' => 'brand-new-password',
            'password_confirmation' => 'brand-new-password',
        ])->assertRedirect(route('branch.teachers.index'));

        $this->assertTrue(Hash::check('brand-new-password', $teacher->fresh()->password));
    }

    public function test_branch_cannot_manage_a_teacher_from_another_branch(): void
    {
        [$branchUser] = $this->makeBranchUser();
        [, $otherBranch] = $this->makeBranchUser();

        $foreignTeacher = Teacher::create([
            'branch_id' => $otherBranch->id,
            'name' => 'Foreign',
            'email' => 'foreign@example.com',
            'phone_number' => '999',
            'password' => Hash::make('secret123'),
        ]);

        $this->actingAs($branchUser)
            ->delete(route('branch.teachers.destroy', $foreignTeacher))
            ->assertForbidden();

        $this->actingAs($branchUser)
            ->put(route('branch.teachers.password.update', $foreignTeacher), [
                'password' => 'whatever123',
                'password_confirmation' => 'whatever123',
            ])->assertForbidden();
    }

    public function test_branch_with_teachers_cannot_be_deleted(): void
    {
        $superAdmin = User::create([
            'name' => 'Admin',
            'email' => 'admin-'.uniqid().'@example.com',
            'role' => 'Super Admin',
            'password' => Hash::make('secret123'),
        ]);

        $branch = Branch::create(['name' => 'Protected Branch', 'email' => 'protected-'.uniqid().'@example.com', 'is_active' => true]);
        Teacher::create([
            'branch_id' => $branch->id,
            'name' => 'Jane',
            'email' => 'jane-'.uniqid().'@example.com',
            'phone_number' => '123',
            'password' => Hash::make('secret123'),
        ]);

        $this->actingAs($superAdmin)
            ->delete(route('admin.branches.destroy', $branch))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('branches', ['id' => $branch->id]);
    }

    // --- Teacher login (password + OTP) ---

    public function test_teacher_logs_in_with_password_then_otp(): void
    {
        Mail::fake();
        $branch = Branch::create(['name' => 'Main', 'email' => 'branch@example.com', 'is_active' => true]);
        $teacher = Teacher::create([
            'branch_id' => $branch->id,
            'name' => 'Jane',
            'email' => 'jane@example.com',
            'phone_number' => '123',
            'password' => Hash::make('teacher-password'),
        ]);

        $this->post(route('login.store'), [
            'login_type' => 'teacher',
            'email' => 'jane@example.com',
            'password' => 'teacher-password',
        ])->assertRedirect(route('login.otp'));

        $this->assertGuest('teacher');

        $otp = null;
        Mail::assertSent(TeacherLoginOtpMail::class, function (TeacherLoginOtpMail $mail) use (&$otp) {
            $otp = $mail->otp;

            return true;
        });

        $this->post(route('login.otp.verify'), ['otp' => $otp])
            ->assertRedirect(route('teacher.dashboard'));

        $this->assertAuthenticatedAs($teacher, 'teacher');
    }

    public function test_teacher_login_rejects_wrong_password(): void
    {
        $branch = Branch::create(['name' => 'Main', 'email' => 'branch@example.com', 'is_active' => true]);
        Teacher::create([
            'branch_id' => $branch->id,
            'name' => 'Jane',
            'email' => 'jane@example.com',
            'phone_number' => '123',
            'password' => Hash::make('teacher-password'),
        ]);

        $this->post(route('login.store'), [
            'login_type' => 'teacher',
            'email' => 'jane@example.com',
            'password' => 'wrong-password',
        ])->assertSessionHas('login_error');

        $this->assertGuest('teacher');
    }

    public function test_login_page_offers_teacher_option(): void
    {
        $this->get(route('login'))->assertOk()->assertSee('Teacher');
    }

    // --- Teacher profile & change password ---

    public function test_teacher_can_view_profile_and_change_password(): void
    {
        $branch = Branch::create(['name' => 'Main', 'email' => 'branch@example.com', 'is_active' => true]);
        $teacher = Teacher::create([
            'branch_id' => $branch->id,
            'name' => 'Jane',
            'email' => 'jane@example.com',
            'phone_number' => '123',
            'password' => Hash::make('old-password'),
        ]);

        $this->actingAs($teacher, 'teacher')
            ->get(route('teacher.profile'))
            ->assertOk()
            ->assertSee('Jane')
            ->assertSee('jane@example.com');

        $this->actingAs($teacher, 'teacher')
            ->put(route('teacher.password.update'), [
                'current_password' => 'old-password',
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ])->assertSessionHas('success');

        $this->assertTrue(Hash::check('new-password', $teacher->fresh()->password));
    }

    // --- Results scoped to branch, remark + conditional email ---

    public function test_teacher_can_view_and_remark_a_result_in_their_branch(): void
    {
        Mail::fake();

        [$attempt, $teacher] = $this->makeAttemptWithTeacher(
            studentEmail: 'student@example.com',
            guardianEmail: 'guardian@example.com',
        );

        $this->actingAs($teacher, 'teacher')
            ->get(route('teacher.results.show', $attempt))
            ->assertOk();

        $this->actingAs($teacher, 'teacher')
            ->post(route('teacher.results.remark.store', $attempt), [
                'remark' => 'Great improvement this term.',
            ])->assertRedirect(route('teacher.results.show', $attempt));

        $attempt->refresh();
        $this->assertSame('Great improvement this term.', $attempt->teacher_remark);
        $this->assertSame($teacher->id, $attempt->teacher_remark_by);
        $this->assertNotNull($attempt->teacher_remark_at);

        Mail::assertSent(ResultRemarkMail::class, 2);
        Mail::assertSent(ResultRemarkMail::class, fn ($mail) => $mail->hasTo('student@example.com'));
        Mail::assertSent(ResultRemarkMail::class, fn ($mail) => $mail->hasTo('guardian@example.com'));
    }

    public function test_remark_email_only_goes_to_student_when_guardian_email_is_missing(): void
    {
        Mail::fake();

        [$attempt, $teacher] = $this->makeAttemptWithTeacher(
            studentEmail: 'solo-student@example.com',
            guardianEmail: null,
        );

        $this->actingAs($teacher, 'teacher')
            ->post(route('teacher.results.remark.store', $attempt), [
                'remark' => 'Keep up the good work.',
            ])->assertRedirect(route('teacher.results.show', $attempt));

        Mail::assertSent(ResultRemarkMail::class, 1);
        Mail::assertSent(ResultRemarkMail::class, fn ($mail) => $mail->hasTo('solo-student@example.com'));
    }

    public function test_teacher_cannot_view_or_remark_a_result_outside_their_branch(): void
    {
        [$attempt] = $this->makeAttemptWithTeacher('student@example.com', 'guardian@example.com');

        $otherBranch = Branch::create(['name' => 'Other', 'email' => 'other-branch@example.com', 'is_active' => true]);
        $outsideTeacher = Teacher::create([
            'branch_id' => $otherBranch->id,
            'name' => 'Outsider',
            'email' => 'outsider@example.com',
            'phone_number' => '000',
            'password' => Hash::make('secret123'),
        ]);

        $this->actingAs($outsideTeacher, 'teacher')
            ->get(route('teacher.results.show', $attempt))
            ->assertForbidden();

        $this->actingAs($outsideTeacher, 'teacher')
            ->post(route('teacher.results.remark.store', $attempt), ['remark' => 'Nope'])
            ->assertForbidden();
    }

    /**
     * @return array{0: array{0: \App\Http\Controllers\Controller|\Illuminate\Foundation\Auth\User, 1: Branch}}
     */
    private function makeBranchUser(): array
    {
        $branch = Branch::create(['name' => 'Branch '.uniqid(), 'email' => 'branch-'.uniqid().'@example.com', 'is_active' => true]);
        $user = User::create([
            'name' => $branch->name,
            'email' => $branch->email,
            'role' => 'Branch',
            'branch_id' => $branch->id,
            'password' => Hash::make('secret123'),
        ]);

        return [$user, $branch];
    }

    private function makeAttemptWithTeacher(string $studentEmail, ?string $guardianEmail): array
    {
        $branch = Branch::create(['name' => 'Main '.uniqid(), 'email' => 'branch-'.uniqid().'@example.com', 'is_active' => true]);
        $class = SchoolClass::create(['branch_id' => $branch->id, 'name' => 'Class 10']);

        $student = Student::create([
            'branch_id' => $branch->id,
            'class_id' => $class->id,
            'student_name' => 'Alice',
            'guardian_name' => 'Guardian Name',
            'guardian_email' => $guardianEmail,
            'class' => $class->name,
            'phone_number' => '123456',
            'email' => $studentEmail,
            'is_active' => true,
        ]);

        $exam = Exam::create([
            'branch_id' => $branch->id,
            'school_class_id' => $class->id,
            'title' => 'Midterm',
            'total_marks' => 10,
            'duration_minutes' => 30,
            'maximum_attempts' => 1,
            'status' => Exam::STATUS_PUBLISHED,
        ]);

        $attempt = ExamAttempt::create([
            'exam_id' => $exam->id,
            'student_id' => $student->id,
            'branch_id' => $branch->id,
            'school_class_id' => $class->id,
            'attempt_number' => 1,
            'started_at' => now()->subMinutes(20),
            'expires_at' => now(),
            'submitted_at' => now(),
            'obtained_marks' => 8,
            'percentage' => 80,
            'correct_count' => 4,
            'wrong_count' => 1,
            'unanswered_count' => 0,
            'is_passed' => true,
            'status' => 'submitted',
        ]);

        $teacher = Teacher::create([
            'branch_id' => $branch->id,
            'name' => 'Jane Teacher',
            'email' => 'teacher-'.uniqid().'@example.com',
            'phone_number' => '123',
            'password' => Hash::make('secret123'),
        ]);

        return [$attempt, $teacher];
    }
}
