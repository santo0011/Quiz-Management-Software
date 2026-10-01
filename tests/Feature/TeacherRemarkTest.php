<?php

namespace Tests\Feature;

use App\Mail\ResultRemarkMail;
use App\Models\Branch;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Teacher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class TeacherRemarkTest extends TestCase
{
    use RefreshDatabase;

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

    private function makeSubmittedAttempt(Exam $exam, Student $student, Branch $branch, SchoolClass $class): ExamAttempt
    {
        return ExamAttempt::create([
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
            'status' => 'submitted',
        ]);
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

        $attempt = $this->makeSubmittedAttempt($exam, $student, $branch, $class);

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
