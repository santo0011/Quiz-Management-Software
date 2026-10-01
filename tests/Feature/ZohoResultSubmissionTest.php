<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Exam;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Services\ExamAttemptService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ZohoResultSubmissionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.zoho.result_pdf_base_url' => 'https://portal.example.com']);
    }

    public function test_submitting_an_exam_generates_a_result_pdf_and_sends_it_to_zoho(): void
    {
        Storage::fake('public');

        Http::fake([
            'https://portal.example.com/result-pdfs/*' => Http::response('%PDF fake result', 200, ['Content-Type' => 'application/pdf']),
            '*accounts.zoho.com.au*' => Http::response(['access_token' => 'fake-access-token'], 200),
            '*zohoapis.com.au*' => Http::response(['success' => true], 200),
        ]);

        [, , $student, $exam] = $this->makeExamFixture();
        $student->update([
            'zoho_student_id' => 'NL1184',
            'zoho_class_id' => '96867000000904800',
            'zoho_class_name' => 'English | Grade 1 ( 1 on 1 ) | Clyde North',
            'zoho_grade' => 'Grade 1',
            'guardian_email' => 'parent-result@example.com',
        ]);

        $attempt = app(ExamAttemptService::class)->start($exam, $student);
        $question = $exam->questions()->first();
        $option = $question->options()->where('is_correct', true)->first();

        app(ExamAttemptService::class)->saveAnswer($attempt, $student, $question->id, $option->id);
        $submitted = app(ExamAttemptService::class)->submit($attempt, $student);

        $submitted->refresh();
        $this->assertNotNull($submitted->result_pdf_path);
        Storage::disk('public')->assertExists($submitted->result_pdf_path);
        $this->assertNotNull($submitted->zoho_result_synced_at);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'receive_results_data_from_portal')
                && $request['Student_NRICH_ID'] === 'NL1184'
                && $request['Email'] === 'parent-result@example.com'
                && $request['Student_Class']['id'] === '96867000000904800'
                && $request['Student_Class']['name'] === 'English | Grade 1 ( 1 on 1 ) | Clyde North';
        });
    }

    public function test_zoho_failure_during_submission_does_not_block_the_student_from_seeing_their_result(): void
    {
        Storage::fake('public');

        Http::fake([
            'https://portal.example.com/result-pdfs/*' => Http::response('%PDF fake result', 200, ['Content-Type' => 'application/pdf']),
            '*accounts.zoho.com.au*' => Http::response([], 500),
        ]);

        [, , $student, $exam] = $this->makeExamFixture();
        $student->update([
            'zoho_student_id' => 'NL1184',
            'zoho_class_id' => 'some-class-id',
            'zoho_class_name' => 'Some Class',
        ]);

        $attempt = app(ExamAttemptService::class)->start($exam, $student);

        $submitted = app(ExamAttemptService::class)->submit($attempt, $student);

        $this->assertSame('submitted', $submitted->status);
        Storage::disk('public')->assertExists($submitted->result_pdf_path);
        $this->assertNull($submitted->zoho_result_synced_at);
    }

    public function test_result_is_not_sent_to_zoho_when_student_has_no_nrich_id(): void
    {
        Storage::fake('public');
        Http::fake();

        [, , $student, $exam] = $this->makeExamFixture();

        $attempt = app(ExamAttemptService::class)->start($exam, $student);
        $submitted = app(ExamAttemptService::class)->submit($attempt, $student);

        Storage::disk('public')->assertExists($submitted->result_pdf_path);
        $this->assertNull($submitted->zoho_result_synced_at);
        Http::assertNothingSent();
    }

    private function makeExamFixture(): array
    {
        $branch = Branch::create(['name' => 'Main Branch', 'email' => 'main@example.com']);
        $class = SchoolClass::create(['branch_id' => $branch->id, 'name' => 'Class 10']);

        $student = Student::create([
            'branch_id' => $branch->id,
            'class_id' => $class->id,
            'student_name' => 'Test Student',
            'guardian_name' => 'Test Guardian',
            'class' => $class->name,
            'phone_number' => '9876543210',
            'email' => 'student@example.com',
            'is_active' => true,
        ]);

        $exam = Exam::create([
            'branch_id' => $branch->id,
            'school_class_id' => $class->id,
            'title' => 'Algebra Basics',
            'total_marks' => 10,
            'duration_minutes' => 30,
            'starts_at' => now()->subMinute(),
            'ends_at' => now()->addHour(),
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

        return [$branch, $class, $student, $exam];
    }
}
