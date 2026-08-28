<?php

namespace Tests\Feature;

use App\Mail\GuardianLoginOtpMail;
use App\Models\Branch;
use App\Models\Exam;
use App\Models\ExamAnswer;
use App\Models\ExamAttempt;
use App\Models\Guardian;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\SchoolClass;
use App\Models\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class GuardianLoginTest extends TestCase
{
    use RefreshDatabase;

    // --- First-time login: email -> OTP -> set password ---

    public function test_check_email_requires_a_student_linked_to_that_guardian_email(): void
    {
        $this->postJson(route('guardian-login.check-email'), [
            'email' => 'nobody@example.com',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['email'])
            ->assertJsonPath('errors.email.0', 'No student is linked to this guardian email address.');
    }

    public function test_check_email_reports_password_setup_required_for_a_new_guardian(): void
    {
        $this->makeStudentWithGuardianEmail('guardian@example.com');

        $this->postJson(route('guardian-login.check-email'), [
            'email' => 'guardian@example.com',
        ])->assertOk()
            ->assertJsonPath('status', 'password_setup_required');
    }

    public function test_guardian_without_password_can_create_one_with_a_single_use_otp(): void
    {
        Mail::fake();

        $student = $this->makeStudentWithGuardianEmail('guardian@example.com');

        $this->postJson(route('guardian-login.send-otp'), [
            'email' => 'guardian@example.com',
        ])->assertOk();

        $otp = null;
        Mail::assertSent(GuardianLoginOtpMail::class, function (GuardianLoginOtpMail $mail) use (&$otp): bool {
            $otp = $mail->otp;

            return strlen($mail->otp) === 6;
        });

        $this->postJson(route('guardian-login.verify-otp'), [
            'email' => 'guardian@example.com',
            'otp' => $otp,
        ])->assertOk();

        $this->postJson(route('guardian-login.create-password'), [
            'password' => 'secure-password',
            'password_confirmation' => 'secure-password',
        ])->assertOk()
            ->assertJsonPath('redirect', route('guardian.dashboard'));

        $guardian = Guardian::where('email', 'guardian@example.com')->firstOrFail();
        $this->assertTrue(Hash::check('secure-password', $guardian->password));
        $this->assertDatabaseMissing('password_reset_otps', [
            'email' => 'guardian@example.com',
            'used_at' => null,
        ]);
        $this->assertAuthenticatedAs($guardian, 'guardian');
        $this->assertNotNull($student->guardian_email);
    }

    public function test_otp_cannot_be_reused_or_guessed_after_expiry(): void
    {
        Mail::fake();
        $this->makeStudentWithGuardianEmail('guardian@example.com');

        $this->postJson(route('guardian-login.send-otp'), ['email' => 'guardian@example.com'])->assertOk();

        $this->postJson(route('guardian-login.verify-otp'), [
            'email' => 'guardian@example.com',
            'otp' => '000000',
        ])->assertUnprocessable()->assertJsonValidationErrors(['otp']);
    }

    // --- Subsequent login: email + password -> OTP -> complete login ---

    public function test_guardian_with_password_logs_in_with_password_then_otp(): void
    {
        Mail::fake();
        $this->makeStudentWithGuardianEmail('guardian@example.com');

        $guardian = Guardian::create([
            'email' => 'guardian@example.com',
            'password' => Hash::make('existing-password'),
        ]);

        $this->post(route('login.store'), [
            'login_type' => 'guardian',
            'email' => 'guardian@example.com',
            'password' => 'existing-password',
        ])->assertRedirect(route('login.otp'));

        $this->assertGuest('guardian');

        $otp = null;
        Mail::assertSent(GuardianLoginOtpMail::class, function (GuardianLoginOtpMail $mail) use (&$otp): bool {
            $otp = $mail->otp;

            return true;
        });

        $this->post(route('login.otp.verify'), ['otp' => $otp])
            ->assertRedirect(route('guardian.dashboard'));

        $this->assertAuthenticatedAs($guardian, 'guardian');
    }

    public function test_guardian_login_rejects_wrong_password(): void
    {
        $this->makeStudentWithGuardianEmail('guardian@example.com');

        Guardian::create([
            'email' => 'guardian@example.com',
            'password' => Hash::make('existing-password'),
        ]);

        $this->post(route('login.store'), [
            'login_type' => 'guardian',
            'email' => 'guardian@example.com',
            'password' => 'wrong-password',
        ])->assertSessionHas('login_error');

        $this->assertGuest('guardian');
    }

    // --- Dashboard: all linked Students, multiple sharing one email ---

    public function test_guardian_dashboard_lists_every_student_sharing_the_guardian_email(): void
    {
        $studentOne = $this->makeStudentWithGuardianEmail('guardian@example.com', 'Alice');
        $studentTwo = $this->makeStudentWithGuardianEmail('guardian@example.com', 'Bob');
        $unrelated = $this->makeStudentWithGuardianEmail('someone-else@example.com', 'Charlie');

        $guardian = Guardian::create([
            'email' => 'guardian@example.com',
            'password' => Hash::make('secret123'),
        ]);

        $response = $this->actingAs($guardian, 'guardian')->get(route('guardian.dashboard'));

        $response->assertOk();
        $response->assertSee('Alice');
        $response->assertSee('Bob');
        $response->assertDontSee('Charlie');
    }

    // --- Security: only linked Students are reachable ---

    public function test_guardian_cannot_view_a_student_not_linked_to_their_email(): void
    {
        $unrelated = $this->makeStudentWithGuardianEmail('someone-else@example.com');

        $guardian = Guardian::create([
            'email' => 'guardian@example.com',
            'password' => Hash::make('secret123'),
        ]);

        $this->actingAs($guardian, 'guardian')
            ->get(route('guardian.students.show', $unrelated))
            ->assertForbidden();
    }

    public function test_guardian_can_view_their_own_linked_student_profile_and_results(): void
    {
        $student = $this->makeStudentWithGuardianEmail('guardian@example.com', 'Alice');

        $exam = Exam::create([
            'branch_id' => $student->branch_id,
            'school_class_id' => $student->class_id,
            'title' => 'Midterm Science',
            'total_marks' => 10,
            'duration_minutes' => 30,
            'maximum_attempts' => 1,
            'status' => Exam::STATUS_PUBLISHED,
        ]);

        $attempt = ExamAttempt::create([
            'exam_id' => $exam->id,
            'student_id' => $student->id,
            'branch_id' => $student->branch_id,
            'school_class_id' => $student->class_id,
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

        $guardian = Guardian::create([
            'email' => 'guardian@example.com',
            'password' => Hash::make('secret123'),
        ]);

        $this->actingAs($guardian, 'guardian')
            ->get(route('guardian.students.show', $student))
            ->assertOk()
            ->assertSee('Alice')
            ->assertSee('Midterm Science');

        $this->actingAs($guardian, 'guardian')
            ->get(route('guardian.students.results.show', [$student, $attempt]))
            ->assertOk()
            ->assertSee('Midterm Science')
            ->assertSee('80');
    }

    public function test_guardian_cannot_view_a_result_belonging_to_an_unrelated_student(): void
    {
        $student = $this->makeStudentWithGuardianEmail('someone-else@example.com');

        $exam = Exam::create([
            'branch_id' => $student->branch_id,
            'school_class_id' => $student->class_id,
            'title' => 'Midterm Science',
            'total_marks' => 10,
            'duration_minutes' => 30,
            'maximum_attempts' => 1,
            'status' => Exam::STATUS_PUBLISHED,
        ]);

        $attempt = ExamAttempt::create([
            'exam_id' => $exam->id,
            'student_id' => $student->id,
            'branch_id' => $student->branch_id,
            'school_class_id' => $student->class_id,
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

        $guardian = Guardian::create([
            'email' => 'guardian@example.com',
            'password' => Hash::make('secret123'),
        ]);

        $this->actingAs($guardian, 'guardian')
            ->get(route('guardian.students.results.show', [$student, $attempt]))
            ->assertForbidden();
    }

    // --- Profile ---

    public function test_guardian_profile_page_shows_registered_email_and_linked_students(): void
    {
        $this->makeStudentWithGuardianEmail('guardian@example.com', 'Alice');
        $this->makeStudentWithGuardianEmail('guardian@example.com', 'Bob');

        $guardian = Guardian::create([
            'email' => 'guardian@example.com',
            'password' => Hash::make('secret123'),
        ]);

        $this->actingAs($guardian, 'guardian')
            ->get(route('guardian.profile'))
            ->assertOk()
            ->assertSee('guardian@example.com')
            ->assertSee('Alice')
            ->assertSee('Bob');
    }

    // --- Result Details (per-question breakdown) ---

    public function test_guardian_can_view_question_level_result_details_for_their_own_student(): void
    {
        $student = $this->makeStudentWithGuardianEmail('guardian@example.com', 'Alice');

        $exam = Exam::create([
            'branch_id' => $student->branch_id,
            'school_class_id' => $student->class_id,
            'title' => 'Midterm Science',
            'total_marks' => 10,
            'duration_minutes' => 30,
            'maximum_attempts' => 1,
            'status' => Exam::STATUS_PUBLISHED,
        ]);

        $question = Question::create([
            'exam_id' => $exam->id,
            'question_text' => 'What is the boiling point of water?',
            'marks' => 5,
            'position' => 1,
        ]);

        $correctOption = QuestionOption::create([
            'question_id' => $question->id,
            'option_text' => '100 C',
            'is_correct' => true,
            'position' => 1,
        ]);

        QuestionOption::create([
            'question_id' => $question->id,
            'option_text' => '50 C',
            'is_correct' => false,
            'position' => 2,
        ]);

        $attempt = ExamAttempt::create([
            'exam_id' => $exam->id,
            'student_id' => $student->id,
            'branch_id' => $student->branch_id,
            'school_class_id' => $student->class_id,
            'attempt_number' => 1,
            'started_at' => now()->subMinutes(20),
            'expires_at' => now(),
            'submitted_at' => now(),
            'obtained_marks' => 5,
            'percentage' => 100,
            'correct_count' => 1,
            'wrong_count' => 0,
            'unanswered_count' => 0,
            'is_passed' => true,
            'status' => 'submitted',
        ]);

        ExamAnswer::create([
            'exam_attempt_id' => $attempt->id,
            'question_id' => $question->id,
            'question_option_id' => $correctOption->id,
            'is_correct' => true,
            'marks_awarded' => 5,
        ]);

        $guardian = Guardian::create([
            'email' => 'guardian@example.com',
            'password' => Hash::make('secret123'),
        ]);

        $this->actingAs($guardian, 'guardian')
            ->get(route('guardian.students.results.details', [$student, $attempt]))
            ->assertOk()
            ->assertSee('What is the boiling point of water?', false)
            ->assertSee('100 C', false)
            ->assertSee('5.00 marks', false);
    }

    public function test_guardian_cannot_view_result_details_for_an_unrelated_student(): void
    {
        $student = $this->makeStudentWithGuardianEmail('someone-else@example.com');

        $exam = Exam::create([
            'branch_id' => $student->branch_id,
            'school_class_id' => $student->class_id,
            'title' => 'Midterm Science',
            'total_marks' => 10,
            'duration_minutes' => 30,
            'maximum_attempts' => 1,
            'status' => Exam::STATUS_PUBLISHED,
        ]);

        $attempt = ExamAttempt::create([
            'exam_id' => $exam->id,
            'student_id' => $student->id,
            'branch_id' => $student->branch_id,
            'school_class_id' => $student->class_id,
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

        $guardian = Guardian::create([
            'email' => 'guardian@example.com',
            'password' => Hash::make('secret123'),
        ]);

        $this->actingAs($guardian, 'guardian')
            ->get(route('guardian.students.results.details', [$student, $attempt]))
            ->assertForbidden();
    }

    // --- Change Password (self-service, from the sidebar) ---

    public function test_guardian_can_change_their_password(): void
    {
        $this->makeStudentWithGuardianEmail('guardian@example.com');

        $guardian = Guardian::create([
            'email' => 'guardian@example.com',
            'password' => Hash::make('old-password'),
        ]);

        $this->actingAs($guardian, 'guardian')
            ->get(route('guardian.password.edit'))
            ->assertOk();

        $this->actingAs($guardian, 'guardian')
            ->put(route('guardian.password.update'), [
                'current_password' => 'old-password',
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ])
            ->assertSessionHas('success');

        $this->assertTrue(Hash::check('new-password', $guardian->fresh()->password));
    }

    public function test_guardian_password_change_rejects_wrong_current_password(): void
    {
        $this->makeStudentWithGuardianEmail('guardian@example.com');

        $guardian = Guardian::create([
            'email' => 'guardian@example.com',
            'password' => Hash::make('old-password'),
        ]);

        $this->actingAs($guardian, 'guardian')
            ->put(route('guardian.password.update'), [
                'current_password' => 'wrong-password',
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ])
            ->assertSessionHasErrors('current_password');

        $this->assertTrue(Hash::check('old-password', $guardian->fresh()->password));
    }

    public function test_guardian_password_change_requires_confirmation_match(): void
    {
        $this->makeStudentWithGuardianEmail('guardian@example.com');

        $guardian = Guardian::create([
            'email' => 'guardian@example.com',
            'password' => Hash::make('old-password'),
        ]);

        $this->actingAs($guardian, 'guardian')
            ->put(route('guardian.password.update'), [
                'current_password' => 'old-password',
                'password' => 'new-password',
                'password_confirmation' => 'different-password',
            ])
            ->assertSessionHasErrors('password');

        $this->assertTrue(Hash::check('old-password', $guardian->fresh()->password));
    }

    private function makeStudentWithGuardianEmail(string $guardianEmail, string $studentName = 'Test Student'): Student
    {
        $branch = Branch::create(['name' => 'Main Branch '.uniqid(), 'email' => 'branch-'.uniqid().'@example.com']);
        $class = SchoolClass::create(['branch_id' => $branch->id, 'name' => 'Class 10']);

        return Student::create([
            'branch_id' => $branch->id,
            'class_id' => $class->id,
            'student_name' => $studentName,
            'guardian_name' => 'Guardian Name',
            'guardian_email' => $guardianEmail,
            'class' => $class->name,
            'phone_number' => '9876543210',
            'email' => strtolower(str_replace(' ', '.', $studentName)).'-'.uniqid().'@example.com',
            'is_active' => true,
        ]);
    }
}
