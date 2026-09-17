<?php

namespace Tests\Feature;

use App\Mail\StudentResultMail;
use App\Models\Branch;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * The Branch Panel's manual "Review & Send Result" flow: takes the result
 * exactly as already calculated/stored by ExamAttemptService::submit(),
 * generates a PDF, emails it to the Student's Zoho/OTP email, and forwards
 * it to Zoho via the existing ZohoResultService. Marks/percentage/pass-fail
 * are never touched by any of this.
 */
class BranchResultSendTest extends TestCase
{
    use RefreshDatabase;

    public function test_branch_can_review_and_send_a_result_which_emails_the_student_and_notifies_zoho(): void
    {
        Storage::fake('public');
        Mail::fake();
        Http::fake([
            '*accounts.zoho.com.au*' => Http::response(['access_token' => 'fake-token'], 200),
            '*zohoapis.com.au*' => Http::response(['success' => true], 200),
        ]);

        [$branch, $branchUser, $attempt] = $this->makeSubmittedAttemptFixture([
            'guardian_email' => 'parent-otp-email@example.com',
        ]);

        $response = $this->actingAs($branchUser)->post(route('branch.results.send', $attempt), [
            'review' => 'Great improvement in problem solving this term. Keep practicing time management.',
        ]);

        $response->assertRedirect(route('branch.results.show', $attempt));
        $response->assertSessionHas('success');

        $attempt->refresh();
        $this->assertSame('Great improvement in problem solving this term. Keep practicing time management.', $attempt->branch_review);
        $this->assertSame($branchUser->id, $attempt->branch_review_by);
        $this->assertNotNull($attempt->branch_review_at);

        Mail::assertSent(StudentResultMail::class, function (StudentResultMail $mail) use ($attempt) {
            return $mail->hasTo('parent-otp-email@example.com') && $mail->attempt->is($attempt);
        });

        Http::assertSent(fn ($request) => str_contains($request->url(), 'receive_results_data_from_portal'));

        $attempt->refresh();
        $this->assertNotNull($attempt->result_email_sent_at);
        $this->assertSame($branchUser->id, $attempt->result_email_sent_by);
        $this->assertNotNull($attempt->branch_result_pdf_path);
        $this->assertNotNull($attempt->zoho_result_synced_at);
        Storage::disk('public')->assertExists($attempt->branch_result_pdf_path);
    }

    public function test_sending_does_not_change_any_calculated_result_value(): void
    {
        Storage::fake('public');
        Mail::fake();
        Http::fake(['*' => Http::response(['access_token' => 'fake-token', 'success' => true], 200)]);

        [, $branchUser, $attempt] = $this->makeSubmittedAttemptFixture([
            'guardian_email' => 'parent-otp-email@example.com',
        ]);

        $before = $attempt->only(['obtained_marks', 'percentage', 'correct_count', 'wrong_count', 'unanswered_count', 'is_passed']);

        $this->actingAs($branchUser)->post(route('branch.results.send', $attempt), ['review' => 'Solid performance overall.']);

        $attempt->refresh();
        $this->assertSame($before, $attempt->only(['obtained_marks', 'percentage', 'correct_count', 'wrong_count', 'unanswered_count', 'is_passed']));
    }

    public function test_send_uses_the_students_zoho_otp_email_never_a_different_local_email(): void
    {
        Storage::fake('public');
        Mail::fake();
        Http::fake(['*' => Http::response(['access_token' => 'fake-token', 'success' => true], 200)]);

        [, $branchUser, $attempt] = $this->makeSubmittedAttemptFixture([
            'email' => 'student-local-login-email@example.com',
            'guardian_email' => 'zoho-otp-parent-email@example.com',
        ]);

        $this->actingAs($branchUser)->post(route('branch.results.send', $attempt), ['review' => 'Good grasp of core concepts.']);

        Mail::assertSent(StudentResultMail::class, function (StudentResultMail $mail) {
            return $mail->hasTo('zoho-otp-parent-email@example.com')
                && ! $mail->hasTo('student-local-login-email@example.com');
        });
    }

    public function test_send_fails_gracefully_when_student_has_no_otp_email_on_file(): void
    {
        Storage::fake('public');
        Mail::fake();
        Http::fake();

        [, $branchUser, $attempt] = $this->makeSubmittedAttemptFixture([
            'guardian_email' => null,
        ]);

        $response = $this->actingAs($branchUser)->post(route('branch.results.send', $attempt), ['review' => 'Good effort this term.']);

        $response->assertRedirect(route('branch.results.show', $attempt));
        $response->assertSessionHas('error');
        Mail::assertNothingSent();
        Http::assertNothingSent();

        $attempt->refresh();
        $this->assertNull($attempt->result_email_sent_at);
        $this->assertNull($attempt->zoho_result_synced_at);
        // The review itself is still saved even though sending failed, so
        // the Branch user doesn't lose their feedback and can retry later.
        $this->assertSame('Good effort this term.', $attempt->branch_review);
    }

    /**
     * "Check the API response error field as well as the HTTP response. Do
     * not show success if the API reports an error." — a 200 response whose
     * body reports failure must not be treated as a successful Zoho send,
     * even though the email to the student still goes out independently.
     */
    public function test_zoho_rejection_is_not_reported_as_success_even_though_email_still_sends(): void
    {
        Storage::fake('public');
        Mail::fake();
        Http::fake([
            '*accounts.zoho.com.au*' => Http::response(['access_token' => 'fake-token'], 200),
            '*zohoapis.com.au*' => Http::response(['success' => false, 'error' => ['message' => 'Invalid class id']], 200),
        ]);

        [, $branchUser, $attempt] = $this->makeSubmittedAttemptFixture([
            'guardian_email' => 'parent-otp-email@example.com',
        ]);

        $response = $this->actingAs($branchUser)->post(route('branch.results.send', $attempt), ['review' => 'Needs more revision on word problems.']);

        $response->assertSessionHas('warning');
        $response->assertSessionMissing('success');

        Mail::assertSent(StudentResultMail::class);

        $attempt->refresh();
        $this->assertNotNull($attempt->result_email_sent_at);
        $this->assertNull($attempt->zoho_result_synced_at);
    }

    public function test_send_requires_a_review_before_the_result_can_be_sent(): void
    {
        Storage::fake('public');
        Mail::fake();
        Http::fake();

        [, $branchUser, $attempt] = $this->makeSubmittedAttemptFixture([
            'guardian_email' => 'parent-otp-email@example.com',
        ]);

        $response = $this->actingAs($branchUser)->post(route('branch.results.send', $attempt), ['review' => '']);

        $response->assertSessionHasErrors('review');
        Mail::assertNothingSent();
        Http::assertNothingSent();

        $attempt->refresh();
        $this->assertNull($attempt->branch_review);
        $this->assertNull($attempt->result_email_sent_at);
    }

    public function test_review_is_displayed_on_the_result_page_after_being_saved(): void
    {
        Storage::fake('public');
        Mail::fake();
        Http::fake(['*' => Http::response(['access_token' => 'fake-token', 'success' => true], 200)]);

        [, $branchUser, $attempt] = $this->makeSubmittedAttemptFixture([
            'guardian_email' => 'parent-otp-email@example.com',
        ]);

        $this->actingAs($branchUser)->post(route('branch.results.send', $attempt), [
            'review' => 'Excellent grasp of fractions; needs work on long division.',
        ]);

        $response = $this->actingAs($branchUser)->get(route('branch.results.show', $attempt));

        $response->assertOk();
        $response->assertSee('Excellent grasp of fractions; needs work on long division.');
        $response->assertSee($branchUser->name);
    }

    public function test_review_is_included_in_the_generated_result_pdf(): void
    {
        [, , $attempt] = $this->makeSubmittedAttemptFixture(['guardian_email' => 'parent-otp-email@example.com']);

        $attempt->update(['branch_review' => 'Strong in algebra, should revise geometry proofs before the next test.']);

        $html = view('pdf.branch-result', ['attempt' => $attempt->fresh(['student', 'exam.subject', 'schoolClass', 'branch'])])->render();

        $this->assertStringContainsString('Strong in algebra, should revise geometry proofs before the next test.', $html);
        $this->assertStringContainsString('Branch Review', $html);
    }

    /**
     * Cross-branch visibility (temporary, explicit requirement): a Branch
     * user can review and send a result belonging to a DIFFERENT branch.
     */
    public function test_branch_user_can_send_a_result_belonging_to_a_different_branch(): void
    {
        Storage::fake('public');
        Mail::fake();
        Http::fake(['*' => Http::response(['access_token' => 'fake-token', 'success' => true], 200)]);

        [$ownBranch] = $this->makeSubmittedAttemptFixture(['guardian_email' => 'a@example.com']);
        [, , $otherAttempt] = $this->makeSubmittedAttemptFixture(
            ['guardian_email' => 'other-parent@example.com'],
            branchName: 'Other Branch',
            branchEmail: 'other-branch@example.com',
        );

        $branchUser = User::create([
            'name' => 'Branch User',
            'email' => 'branch-user-cross@example.com',
            'role' => 'Branch',
            'branch_id' => $ownBranch->id,
            'password' => Hash::make('123456'),
        ]);

        $this->actingAs($branchUser)->get(route('branch.results.show', $otherAttempt))->assertOk();

        $response = $this->actingAs($branchUser)->post(route('branch.results.send', $otherAttempt), ['review' => 'Consistent effort across the term.']);

        $response->assertSessionHas('success');
        Mail::assertSent(StudentResultMail::class, fn (StudentResultMail $mail) => $mail->hasTo('other-parent@example.com'));
    }

    /**
     * @return array{0: Branch, 1: User, 2: ExamAttempt}
     */
    private function makeSubmittedAttemptFixture(array $studentOverrides = [], string $branchName = 'Main Branch', string $branchEmail = 'main-branch@example.com'): array
    {
        $branch = Branch::create(['name' => $branchName, 'email' => $branchEmail]);
        $class = SchoolClass::create(['branch_id' => $branch->id, 'name' => 'Class 10']);
        $subject = Subject::firstOrCreate(['name' => 'Science']);

        $student = Student::create(array_merge([
            'branch_id' => $branch->id,
            'class_id' => $class->id,
            'student_name' => 'Test Student',
            'guardian_name' => 'Test Guardian',
            'class' => $class->name,
            'phone_number' => '9876543210',
            'zoho_student_id' => 'NL-'.uniqid(),
            'zoho_class_id' => 'zoho-class-1',
            'zoho_class_name' => 'Class 10',
            'email' => 'student-'.uniqid().'@example.com',
            'is_active' => true,
        ], $studentOverrides));

        $exam = Exam::create([
            'branch_id' => $branch->id,
            'school_class_id' => $class->id,
            'subject_id' => $subject->id,
            'title' => 'Algebra Basics',
            'total_marks' => 10,
            'duration_minutes' => 30,
            'starts_at' => now()->subMinute(),
            'ends_at' => now()->addHour(),
            'passing_marks' => 5,
            'maximum_attempts' => 1,
            'status' => Exam::STATUS_PUBLISHED,
        ]);

        $question = Question::create([
            'exam_id' => $exam->id,
            'question_text' => '2 + 2 = ?',
            'question_type' => 'mcq',
            'marks' => 10,
        ]);
        QuestionOption::create(['question_id' => $question->id, 'option_text' => '4', 'is_correct' => true, 'position' => 0]);
        QuestionOption::create(['question_id' => $question->id, 'option_text' => '5', 'is_correct' => false, 'position' => 1]);

        $attempt = ExamAttempt::create([
            'exam_id' => $exam->id,
            'student_id' => $student->id,
            'branch_id' => $branch->id,
            'school_class_id' => $class->id,
            'attempt_number' => 1,
            'started_at' => now()->subMinutes(10),
            'expires_at' => now()->addMinutes(20),
            'submitted_at' => now(),
            'obtained_marks' => 10,
            'percentage' => 100,
            'correct_count' => 1,
            'wrong_count' => 0,
            'unanswered_count' => 0,
            'is_passed' => true,
            'status' => 'submitted',
        ]);

        $branchUser = User::create([
            'name' => 'Branch User',
            'email' => 'branch-user-'.uniqid().'@example.com',
            'role' => 'Branch',
            'branch_id' => $branch->id,
            'password' => Hash::make('123456'),
        ]);

        return [$branch, $branchUser, $attempt];
    }
}
