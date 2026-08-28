<?php

namespace Tests\Feature;

use App\Mail\ResultRemarkMail;
use App\Mail\TeacherLoginOtpMail;
use App\Models\AcademicSession;
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

class TeacherSessionAndRemarkTest extends TestCase
{
    use RefreshDatabase;

    // --- Teacher session selection scopes exam/results data ---

    public function test_teacher_can_switch_academic_session_and_results_are_scoped_to_it(): void
    {
        $branch = Branch::create(['name' => 'Main', 'email' => 'branch-'.uniqid().'@example.com', 'is_active' => true]);
        $class = SchoolClass::create(['branch_id' => $branch->id, 'name' => 'Class 10']);
        $teacher = Teacher::create([
            'branch_id' => $branch->id,
            'name' => 'Jane',
            'email' => 'jane-'.uniqid().'@example.com',
            'phone_number' => '123',
            'password' => Hash::make('secret123'),
        ]);

        $sessionOne = $this->makeSession('2025-2026', now()->subYear());
        $sessionTwo = $this->makeSession('2026-2027', now());

        $studentOne = $this->makeStudent($branch, $class, $sessionOne, 'student-one');
        $studentTwo = $this->makeStudent($branch, $class, $sessionTwo, 'student-two');
        $exam = Exam::create([
            'branch_id' => $branch->id,
            'school_class_id' => $class->id,
            'title' => 'Midterm',
            'total_marks' => 10,
            'duration_minutes' => 30,
            'maximum_attempts' => 1,
            'status' => Exam::STATUS_PUBLISHED,
        ]);

        $this->makeSubmittedAttempt($exam, $studentOne, $branch, $class, $sessionOne);
        $this->makeSubmittedAttempt($exam, $studentTwo, $branch, $class, $sessionTwo);

        $this->actingAs($teacher, 'teacher')
            ->post(route('teacher.academic-session-selection.store'), ['academic_session_id' => $sessionOne->id])
            ->assertSessionHas('teacher_selected_academic_session_id', $sessionOne->id);

        $this->actingAs($teacher, 'teacher')
            ->get(route('teacher.results.index'))
            ->assertOk()
            ->assertSee('student-one')
            ->assertDontSee('student-two');

        $this->actingAs($teacher, 'teacher')
            ->post(route('teacher.academic-session-selection.store'), ['academic_session_id' => $sessionTwo->id])
            ->assertSessionHas('teacher_selected_academic_session_id', $sessionTwo->id);

        $this->actingAs($teacher, 'teacher')
            ->get(route('teacher.results.index'))
            ->assertOk()
            ->assertSee('student-two')
            ->assertDontSee('student-one');
    }

    public function test_teacher_dashboard_and_results_prompt_for_session_when_none_selected(): void
    {
        $branch = Branch::create(['name' => 'Main', 'email' => 'branch-'.uniqid().'@example.com', 'is_active' => true]);
        $teacher = Teacher::create([
            'branch_id' => $branch->id,
            'name' => 'Jane',
            'email' => 'jane-'.uniqid().'@example.com',
            'phone_number' => '123',
            'password' => Hash::make('secret123'),
        ]);

        $this->actingAs($teacher, 'teacher')
            ->get(route('teacher.dashboard'))
            ->assertOk()
            ->assertSee('Select an academic session to continue');

        $this->actingAs($teacher, 'teacher')
            ->get(route('teacher.results.index'))
            ->assertOk()
            ->assertSee('Select an academic session to continue');
    }

    public function test_teacher_cannot_view_a_result_outside_the_selected_session(): void
    {
        $branch = Branch::create(['name' => 'Main', 'email' => 'branch-'.uniqid().'@example.com', 'is_active' => true]);
        $class = SchoolClass::create(['branch_id' => $branch->id, 'name' => 'Class 10']);
        $teacher = Teacher::create([
            'branch_id' => $branch->id,
            'name' => 'Jane',
            'email' => 'jane-'.uniqid().'@example.com',
            'phone_number' => '123',
            'password' => Hash::make('secret123'),
        ]);

        $sessionOne = $this->makeSession('2025-2026', now()->subYear());
        $sessionTwo = $this->makeSession('2026-2027', now());
        $student = $this->makeStudent($branch, $class, $sessionTwo, 'other-session-student');
        $exam = Exam::create([
            'branch_id' => $branch->id,
            'school_class_id' => $class->id,
            'title' => 'Midterm',
            'total_marks' => 10,
            'duration_minutes' => 30,
            'maximum_attempts' => 1,
            'status' => Exam::STATUS_PUBLISHED,
        ]);
        $attempt = $this->makeSubmittedAttempt($exam, $student, $branch, $class, $sessionTwo);

        $this->actingAs($teacher, 'teacher')
            ->post(route('teacher.academic-session-selection.store'), ['academic_session_id' => $sessionOne->id]);

        $this->actingAs($teacher, 'teacher')
            ->get(route('teacher.results.show', $attempt))
            ->assertForbidden();
    }

    // --- Remark notification & email status flash ---

    public function test_remark_flash_reports_both_recipients_when_guardian_email_present(): void
    {
        Mail::fake();

        [$attempt, $teacher] = $this->makeAttemptWithTeacher('student@example.com', 'guardian@example.com');

        $response = $this->actingAs($teacher, 'teacher')
            ->post(route('teacher.results.remark.store', $attempt), ['remark' => 'Great job.']);

        $response->assertRedirect(route('teacher.results.show', $attempt));
        $response->assertSessionHas('success', 'Remark saved successfully.');
        $response->assertSessionHas('email_status', 'Result email sent to student@example.com and guardian@example.com.');
        $response->assertSessionMissing('warning');

        Mail::assertSent(ResultRemarkMail::class, 2);
    }

    public function test_remark_flash_reports_only_student_when_guardian_email_missing(): void
    {
        Mail::fake();

        [$attempt, $teacher] = $this->makeAttemptWithTeacher('solo-student@example.com', null);

        $response = $this->actingAs($teacher, 'teacher')
            ->post(route('teacher.results.remark.store', $attempt), ['remark' => 'Keep it up.']);

        $response->assertSessionHas('success', 'Remark saved successfully.');
        $response->assertSessionHas('email_status', 'Result email sent to solo-student@example.com.');
    }

    // --- Automatic session selection at login (Super Admin, Branch, Teacher) ---

    public function test_super_admin_login_auto_selects_the_session_covering_today(): void
    {
        Mail::fake();
        $session = $this->makeSession('Current', now()->subDays(5), now()->addDays(5));

        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin-'.uniqid().'@example.com',
            'role' => 'Super Admin',
            'password' => Hash::make('secret123'),
        ]);

        $this->post(route('login.store'), [
            'login_type' => 'super_admin',
            'email' => $admin->email,
            'password' => 'secret123',
        ])->assertRedirect(route('login.otp'));

        $otp = $this->captureOtp();

        $this->post(route('login.otp.verify'), ['otp' => $otp])
            ->assertSessionHas('admin_selected_academic_session_id', $session->id);
    }

    public function test_branch_login_auto_selects_the_session_covering_today(): void
    {
        Mail::fake();
        $session = $this->makeSession('Current', now()->subDays(5), now()->addDays(5));

        $branch = Branch::create(['name' => 'Main', 'email' => 'branch-'.uniqid().'@example.com', 'is_active' => true]);
        $branchUser = User::create([
            'name' => $branch->name,
            'email' => $branch->email,
            'role' => 'Branch',
            'branch_id' => $branch->id,
            'password' => Hash::make('secret123'),
        ]);

        $this->post(route('login.store'), [
            'login_type' => 'branch',
            'email' => $branchUser->email,
            'password' => 'secret123',
        ])->assertRedirect(route('login.otp'));

        $otp = $this->captureOtp(\App\Mail\BranchLoginOtpMail::class);

        $this->post(route('login.otp.verify'), ['otp' => $otp])
            ->assertSessionHas('branch_selected_academic_session_id', $session->id);
    }

    public function test_teacher_login_auto_selects_the_session_covering_today(): void
    {
        Mail::fake();
        $session = $this->makeSession('Current', now()->subDays(5), now()->addDays(5));

        $branch = Branch::create(['name' => 'Main', 'email' => 'branch-'.uniqid().'@example.com', 'is_active' => true]);
        $teacher = Teacher::create([
            'branch_id' => $branch->id,
            'name' => 'Jane',
            'email' => 'jane-'.uniqid().'@example.com',
            'phone_number' => '123',
            'password' => Hash::make('secret123'),
        ]);

        $this->post(route('login.store'), [
            'login_type' => 'teacher',
            'email' => $teacher->email,
            'password' => 'secret123',
        ])->assertRedirect(route('login.otp'));

        $otp = $this->captureOtp(TeacherLoginOtpMail::class);

        $this->post(route('login.otp.verify'), ['otp' => $otp])
            ->assertSessionHas('teacher_selected_academic_session_id', $session->id);
    }

    public function test_login_does_not_auto_select_when_no_session_covers_today(): void
    {
        Mail::fake();
        $this->makeSession('Past', now()->subYears(2), now()->subYear());

        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin-'.uniqid().'@example.com',
            'role' => 'Super Admin',
            'password' => Hash::make('secret123'),
        ]);

        $this->post(route('login.store'), [
            'login_type' => 'super_admin',
            'email' => $admin->email,
            'password' => 'secret123',
        ]);

        $otp = $this->captureOtp();

        $response = $this->post(route('login.otp.verify'), ['otp' => $otp]);
        $response->assertSessionMissing('admin_selected_academic_session_id');

        $this->actingAs($admin)
            ->get(route('admin.results.index'))
            ->assertSee('Select an academic session to continue');
    }

    public function test_login_prefers_the_active_session_when_multiple_cover_today(): void
    {
        Mail::fake();
        $closed = AcademicSession::create([
            'name' => 'Closed but current',
            'start_date' => now()->subDays(5),
            'end_date' => now()->addDays(5),
            'is_active' => false,
        ]);
        $active = AcademicSession::create([
            'name' => 'Active and current',
            'start_date' => now()->subDays(3),
            'end_date' => now()->addDays(3),
            'is_active' => true,
        ]);

        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin-'.uniqid().'@example.com',
            'role' => 'Super Admin',
            'password' => Hash::make('secret123'),
        ]);

        $this->post(route('login.store'), [
            'login_type' => 'super_admin',
            'email' => $admin->email,
            'password' => 'secret123',
        ]);

        $otp = $this->captureOtp();

        $this->post(route('login.otp.verify'), ['otp' => $otp])
            ->assertSessionHas('admin_selected_academic_session_id', $active->id);
    }

    private function captureOtp(string $mailClass = \App\Mail\SuperAdminLoginOtpMail::class): string
    {
        $otp = null;

        Mail::assertSent($mailClass, function ($mail) use (&$otp) {
            $otp = $mail->otp;

            return true;
        });

        return $otp;
    }

    private function makeSession(string $name, $start, $end = null): AcademicSession
    {
        return AcademicSession::create([
            'name' => $name.'-'.uniqid(),
            'start_date' => $start,
            'end_date' => $end ?? now()->addYear(),
            'is_active' => true,
        ]);
    }

    private function makeStudent(Branch $branch, SchoolClass $class, AcademicSession $session, string $name): Student
    {
        return Student::create([
            'branch_id' => $branch->id,
            'class_id' => $class->id,
            'session_id' => $session->id,
            'student_name' => $name,
            'guardian_name' => 'Guardian',
            'class' => $class->name,
            'phone_number' => '123456',
            'email' => $name.'-'.uniqid().'@example.com',
            'is_active' => true,
        ]);
    }

    private function makeSubmittedAttempt(Exam $exam, Student $student, Branch $branch, SchoolClass $class, AcademicSession $session): ExamAttempt
    {
        return ExamAttempt::create([
            'exam_id' => $exam->id,
            'student_id' => $student->id,
            'branch_id' => $branch->id,
            'school_class_id' => $class->id,
            'session_id' => $session->id,
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
    }

    private function makeAttemptWithTeacher(string $studentEmail, ?string $guardianEmail): array
    {
        $branch = Branch::create(['name' => 'Main '.uniqid(), 'email' => 'branch-'.uniqid().'@example.com', 'is_active' => true]);
        $class = SchoolClass::create(['branch_id' => $branch->id, 'name' => 'Class 10']);
        $session = $this->makeSession('Current', now()->subDays(5), now()->addDays(5));

        $student = Student::create([
            'branch_id' => $branch->id,
            'class_id' => $class->id,
            'session_id' => $session->id,
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

        $attempt = $this->makeSubmittedAttempt($exam, $student, $branch, $class, $session);

        $teacher = Teacher::create([
            'branch_id' => $branch->id,
            'name' => 'Jane Teacher',
            'email' => 'teacher-'.uniqid().'@example.com',
            'phone_number' => '123',
            'password' => Hash::make('secret123'),
        ]);

        $this->actingAs($teacher, 'teacher')
            ->post(route('teacher.academic-session-selection.store'), ['academic_session_id' => $session->id]);

        return [$attempt, $teacher];
    }
}
