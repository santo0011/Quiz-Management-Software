<?php

namespace Tests\Feature;

use App\Models\AcademicSession;
use App\Models\Branch;
use App\Models\Exam;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use App\Services\ExamAttemptService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ExamEligibilityTest extends TestCase
{
    use RefreshDatabase;

    // --- Creation requires Session, Grade, and Subject ---

    public function test_exam_creation_requires_grade_and_subject(): void
    {
        [$admin, , , $session] = $this->makeBaseFixtures();

        $this->actingAs($admin)
            ->withSession(['admin_selected_academic_session_id' => $session->id])
            ->post(route('admin.exams.store'), [
                'title' => 'Missing Grade And Subject',
                'starts_at' => now()->addDay()->format('Y-m-d H:i:s'),
                'ends_at' => now()->addDays(2)->format('Y-m-d H:i:s'),
                'duration_minutes' => 30,
                'maximum_attempts' => 1,
            ])
            ->assertSessionHasErrors(['school_class_id', 'subject_id']);
    }

    public function test_exam_creation_requires_a_selected_academic_session(): void
    {
        $admin = User::create([
            'name' => 'Super Admin',
            'email' => 'admin-'.uniqid().'@example.com',
            'role' => 'Super Admin',
            'password' => Hash::make('123456'),
        ]);
        $class = SchoolClass::create(['branch_id' => null, 'name' => 'Class 10']);
        $subject = Subject::create(['name' => 'Science']);

        $this->actingAs($admin)
            ->post(route('admin.exams.store'), [
                'title' => 'No Session Selected',
                'school_class_id' => $class->id,
                'subject_id' => $subject->id,
                'starts_at' => now()->addDay()->format('Y-m-d H:i:s'),
                'ends_at' => now()->addDays(2)->format('Y-m-d H:i:s'),
                'duration_minutes' => 30,
                'maximum_attempts' => 1,
            ])
            ->assertRedirect(route('admin.exams.index'));

        $this->assertDatabaseMissing('exams', ['title' => 'No Session Selected']);
    }

    // --- Visibility: all three (Session, Grade, Subject) must match ---

    public function test_student_sees_exam_only_when_session_grade_and_subject_all_match(): void
    {
        [, $class, $subject, $session] = $this->makeBaseFixtures();
        $student = $this->makeStudent($class, $session, [$subject]);
        $exam = $this->makePublishedExam($class, $subject, $session, 'Matching Exam');

        $response = $this->actingAs($student, 'student')->get(route('student.exams.available'));

        $response->assertOk();
        $response->assertSee('Matching Exam');
    }

    public function test_exam_is_hidden_when_grade_does_not_match(): void
    {
        [, $class, $subject, $session] = $this->makeBaseFixtures();
        $otherClass = SchoolClass::create(['branch_id' => null, 'name' => 'Class 9']);
        $student = $this->makeStudent($class, $session, [$subject]);
        $this->makePublishedExam($otherClass, $subject, $session, 'Wrong Grade Exam');

        $this->actingAs($student, 'student')
            ->get(route('student.exams.available'))
            ->assertOk()
            ->assertDontSee('Wrong Grade Exam');
    }

    public function test_exam_is_hidden_when_session_does_not_match(): void
    {
        [, $class, $subject, $session] = $this->makeBaseFixtures();
        $otherSession = AcademicSession::create([
            'name' => 'Other Session',
            'start_date' => now()->subYears(2),
            'end_date' => now()->subYear(),
            'is_active' => false,
        ]);
        $student = $this->makeStudent($class, $session, [$subject]);
        $this->makePublishedExam($class, $subject, $otherSession, 'Wrong Session Exam');

        $this->actingAs($student, 'student')
            ->get(route('student.exams.available'))
            ->assertOk()
            ->assertDontSee('Wrong Session Exam');
    }

    public function test_exam_is_hidden_when_subject_is_not_assigned_to_the_student(): void
    {
        [, $class, $subject, $session] = $this->makeBaseFixtures();
        $otherSubject = Subject::create(['name' => 'Mathematics']);
        $student = $this->makeStudent($class, $session, [$subject]);
        $this->makePublishedExam($class, $otherSubject, $session, 'Unassigned Subject Exam');

        $this->actingAs($student, 'student')
            ->get(route('student.exams.available'))
            ->assertOk()
            ->assertDontSee('Unassigned Subject Exam');
    }

    public function test_student_with_multiple_subjects_sees_exams_for_either_assigned_subject_but_not_a_third(): void
    {
        [, $class, $subjectA, $session] = $this->makeBaseFixtures();
        $subjectB = Subject::create(['name' => 'Mathematics']);
        $subjectC = Subject::create(['name' => 'History']);

        $student = $this->makeStudent($class, $session, [$subjectA, $subjectB]);

        $this->makePublishedExam($class, $subjectA, $session, 'Subject A Exam');
        $this->makePublishedExam($class, $subjectB, $session, 'Subject B Exam');
        $this->makePublishedExam($class, $subjectC, $session, 'Subject C Exam');

        $response = $this->actingAs($student, 'student')->get(route('student.exams.available'));

        $response->assertOk();
        $response->assertSee('Subject A Exam');
        $response->assertSee('Subject B Exam');
        $response->assertDontSee('Subject C Exam');
    }

    public function test_ineligible_exam_also_hidden_from_dashboard_and_upcoming_lists(): void
    {
        [, $class, $subject, $session] = $this->makeBaseFixtures();
        $otherSubject = Subject::create(['name' => 'Mathematics']);
        $student = $this->makeStudent($class, $session, [$subject]);

        Exam::create([
            'branch_id' => null,
            'school_class_id' => $class->id,
            'subject_id' => $otherSubject->id,
            'session_id' => $session->id,
            'title' => 'Upcoming Mismatch Exam',
            'total_marks' => 10,
            'duration_minutes' => 30,
            'maximum_attempts' => 1,
            'status' => Exam::STATUS_PUBLISHED,
            'starts_at' => now()->addDay(),
            'ends_at' => now()->addDays(2),
        ]);

        $this->actingAs($student, 'student')
            ->get(route('student.dashboard'))
            ->assertOk()
            ->assertDontSee('Upcoming Mismatch Exam');

        $this->actingAs($student, 'student')
            ->get(route('student.exams.upcoming'))
            ->assertOk()
            ->assertDontSee('Upcoming Mismatch Exam');
    }

    // --- Backend authorization: cannot view or attempt an ineligible exam directly ---

    public function test_student_cannot_open_an_ineligible_exam_directly_by_url(): void
    {
        [, $class, $subject, $session] = $this->makeBaseFixtures();
        $otherClass = SchoolClass::create(['branch_id' => null, 'name' => 'Class 9']);
        $student = $this->makeStudent($class, $session, [$subject]);
        $exam = $this->makePublishedExam($otherClass, $subject, $session, 'Wrong Grade Exam');

        $this->actingAs($student, 'student')
            ->get(route('student.exams.show', $exam))
            ->assertForbidden();
    }

    public function test_student_cannot_start_an_attempt_for_an_ineligible_exam(): void
    {
        [, $class, $subject, $session] = $this->makeBaseFixtures();
        $otherSubject = Subject::create(['name' => 'Mathematics']);
        $student = $this->makeStudent($class, $session, [$subject]);
        $exam = $this->makePublishedExam($class, $otherSubject, $session, 'Wrong Subject Exam');
        $exam->questions()->create(['question_text' => 'Q1', 'question_type' => 'mcq', 'marks' => 10]);

        $this->expectException(ValidationException::class);

        app(ExamAttemptService::class)->start($exam, $student);
    }

    public function test_student_can_start_an_attempt_for_an_eligible_exam(): void
    {
        [, $class, $subject, $session] = $this->makeBaseFixtures();
        $student = $this->makeStudent($class, $session, [$subject]);
        $exam = $this->makePublishedExam($class, $subject, $session, 'Eligible Exam');
        $exam->questions()->create(['question_text' => 'Q1', 'question_type' => 'mcq', 'marks' => 10]);

        $attempt = app(ExamAttemptService::class)->start($exam, $student);

        $this->assertSame($exam->id, $attempt->exam_id);
        $this->assertSame($student->id, $attempt->student_id);
    }

    // --- The same rule applies to Branch-created exams, not just global ones ---

    public function test_branch_created_exam_follows_the_same_matching_rule(): void
    {
        $branch = Branch::create(['name' => 'Branch X', 'email' => 'branch-x-'.uniqid().'@example.com', 'is_active' => true]);
        $class = SchoolClass::create(['branch_id' => $branch->id, 'name' => 'Class 10']);
        $subject = Subject::create(['name' => 'Science']);
        $session = AcademicSession::create([
            'name' => 'Branch Session',
            'start_date' => now()->subMonth(),
            'end_date' => now()->addMonths(6),
            'is_active' => true,
        ]);

        $student = Student::create([
            'branch_id' => $branch->id,
            'class_id' => $class->id,
            'session_id' => $session->id,
            'student_name' => 'Branch Student',
            'guardian_name' => 'Guardian',
            'class' => $class->name,
            'phone_number' => '9876543210',
            'email' => 'branch-student-'.uniqid().'@example.com',
            'is_active' => true,
        ]);
        $student->subjects()->attach($subject->id);

        $matchingExam = Exam::create([
            'branch_id' => $branch->id,
            'school_class_id' => $class->id,
            'subject_id' => $subject->id,
            'session_id' => $session->id,
            'title' => 'Branch Matching Exam',
            'total_marks' => 10,
            'duration_minutes' => 30,
            'maximum_attempts' => 1,
            'status' => Exam::STATUS_PUBLISHED,
            'starts_at' => now()->subMinute(),
            'ends_at' => now()->addHour(),
        ]);

        $mismatchSubject = Subject::create(['name' => 'Mathematics']);
        Exam::create([
            'branch_id' => $branch->id,
            'school_class_id' => $class->id,
            'subject_id' => $mismatchSubject->id,
            'session_id' => $session->id,
            'title' => 'Branch Mismatch Exam',
            'total_marks' => 10,
            'duration_minutes' => 30,
            'maximum_attempts' => 1,
            'status' => Exam::STATUS_PUBLISHED,
            'starts_at' => now()->subMinute(),
            'ends_at' => now()->addHour(),
        ]);

        $response = $this->actingAs($student, 'student')->get(route('student.exams.available'));

        $response->assertOk();
        $response->assertSee('Branch Matching Exam');
        $response->assertDontSee('Branch Mismatch Exam');
    }

    private function makeBaseFixtures(): array
    {
        $admin = User::create([
            'name' => 'Super Admin',
            'email' => 'admin-'.uniqid().'@example.com',
            'role' => 'Super Admin',
            'password' => Hash::make('123456'),
        ]);

        $class = SchoolClass::create(['branch_id' => null, 'name' => 'Class 10']);
        $subject = Subject::create(['name' => 'Science']);

        $session = AcademicSession::create([
            'name' => 'Session '.uniqid(),
            'start_date' => now()->subMonth(),
            'end_date' => now()->addMonths(6),
            'is_active' => true,
        ]);

        return [$admin, $class, $subject, $session];
    }

    private function makeStudent(SchoolClass $class, AcademicSession $session, array $subjects): Student
    {
        $branch = Branch::create(['name' => 'Branch '.uniqid(), 'email' => 'branch-'.uniqid().'@example.com', 'is_active' => true]);

        $student = Student::create([
            'branch_id' => $branch->id,
            'class_id' => $class->id,
            'session_id' => $session->id,
            'student_name' => 'Eligibility Student',
            'guardian_name' => 'Guardian',
            'class' => $class->name,
            'phone_number' => '9876543210',
            'email' => 'student-'.uniqid().'@example.com',
            'is_active' => true,
        ]);

        $student->subjects()->attach(array_map(fn ($subject) => $subject->id, $subjects));

        return $student;
    }

    private function makePublishedExam(SchoolClass $class, Subject $subject, AcademicSession $session, string $title): Exam
    {
        return Exam::create([
            'branch_id' => null,
            'school_class_id' => $class->id,
            'subject_id' => $subject->id,
            'session_id' => $session->id,
            'title' => $title,
            'total_marks' => 10,
            'duration_minutes' => 30,
            'maximum_attempts' => 1,
            'status' => Exam::STATUS_PUBLISHED,
            'starts_at' => now()->subMinute(),
            'ends_at' => now()->addHour(),
        ]);
    }
}
