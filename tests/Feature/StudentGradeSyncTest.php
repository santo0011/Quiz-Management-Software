<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Exam;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Services\ZohoStudentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The Student's Grade used for Exam eligibility (`class_id`) must track
 * Zoho's `Enrolment.Grade` on every login — resolved against existing
 * Super-Admin-managed Grade (SchoolClass) records, never auto-created.
 */
class StudentGradeSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_sync_resolves_class_id_from_the_zoho_grade_string(): void
    {
        $branch = Branch::create(['name' => 'Branch A', 'email' => 'branch-a@example.com']);
        $grade2 = SchoolClass::create(['branch_id' => null, 'name' => 'Grade 2']);
        $student = Student::create([
            'branch_id' => $branch->id,
            'student_name' => 'Test Student',
            'guardian_name' => 'Guardian',
            'class' => 'Unassigned',
            'phone_number' => '123',
            'email' => 'student-'.uniqid().'@example.com',
            'zoho_student_id' => 'NL1',
            'is_active' => true,
        ]);

        app(ZohoStudentService::class)->syncStudentFromZoho($student, [
            'Enrolment' => ['Grade' => 'Grade 2'],
        ]);

        $student->refresh();
        $this->assertSame($grade2->id, $student->class_id);
        $this->assertSame('Grade 2', $student->class);
        $this->assertSame('Grade 2', $student->zoho_grade);
    }

    /**
     * Regression test for a real bug: `extractActiveClass()` used to read
     * `id`/`name` flat on each `Enrolment.classes[]` entry, but the real
     * Zoho payload nests those under a `Class` object instead
     * (`class['Class']['id']`/`['name']`) — the same shape confirmed for
     * `Subject`. That mismatch meant `zoho_class_id`/`zoho_class_name`
     * always resolved to empty strings for every real student, which in
     * turn made ZohoResultService silently skip sending the result to Zoho
     * ("no Zoho class on file for student") even though Zoho's response
     * genuinely contained one.
     */
    public function test_sync_extracts_class_id_and_name_from_the_nested_class_object(): void
    {
        $branch = Branch::create(['name' => 'Branch A', 'email' => 'branch-a@example.com']);
        $student = Student::create([
            'branch_id' => $branch->id,
            'student_name' => 'Test Student',
            'guardian_name' => 'Guardian',
            'class' => 'Unassigned',
            'phone_number' => '123',
            'email' => 'student-'.uniqid().'@example.com',
            'zoho_student_id' => 'NL1184',
            'is_active' => true,
        ]);

        app(ZohoStudentService::class)->syncStudentFromZoho($student, [
            'Enrolment' => [
                'Grade' => 'Grade 1',
                'classes' => [
                    [
                        'Class' => ['name' => 'English | Grade 1 ( 1 on 1 ) | Clyde North', 'id' => '96867000000904800'],
                        'Subject' => ['name' => 'English', 'id' => '96867000000516052'],
                    ],
                    [
                        'Class' => ['name' => 'Mathematics | Grade 1 (Group) | Clyde North', 'id' => '96867000000911312'],
                        'Subject' => ['name' => 'Mathematics', 'id' => '96867000000516051'],
                    ],
                ],
            ],
        ]);

        $student->refresh();
        $this->assertSame('96867000000904800', $student->zoho_class_id);
        $this->assertSame('English | Grade 1 ( 1 on 1 ) | Clyde North', $student->zoho_class_name);
    }

    public function test_sync_matches_grade_name_case_and_whitespace_insensitively(): void
    {
        $branch = Branch::create(['name' => 'Branch A', 'email' => 'branch-a@example.com']);
        $grade3 = SchoolClass::create(['branch_id' => null, 'name' => 'Grade 3']);
        $student = Student::create([
            'branch_id' => $branch->id,
            'student_name' => 'Test Student',
            'guardian_name' => 'Guardian',
            'class' => 'Unassigned',
            'phone_number' => '123',
            'email' => 'student-'.uniqid().'@example.com',
            'zoho_student_id' => 'NL2',
            'is_active' => true,
        ]);

        app(ZohoStudentService::class)->syncStudentFromZoho($student, [
            'Enrolment' => ['Grade' => '  grade 3  '],
        ]);

        $this->assertSame($grade3->id, $student->fresh()->class_id);
    }

    public function test_sync_prefers_the_branch_specific_grade_over_a_same_named_global_one(): void
    {
        $branch = Branch::create(['name' => 'Branch A', 'email' => 'branch-a@example.com']);
        SchoolClass::create(['branch_id' => null, 'name' => 'Grade 4']);
        $branchGrade4 = SchoolClass::create(['branch_id' => $branch->id, 'name' => 'Grade 4']);
        $student = Student::create([
            'branch_id' => $branch->id,
            'student_name' => 'Test Student',
            'guardian_name' => 'Guardian',
            'class' => 'Unassigned',
            'phone_number' => '123',
            'email' => 'student-'.uniqid().'@example.com',
            'zoho_student_id' => 'NL3',
            'is_active' => true,
        ]);

        app(ZohoStudentService::class)->syncStudentFromZoho($student, [
            'Enrolment' => ['Grade' => 'Grade 4'],
        ]);

        $this->assertSame($branchGrade4->id, $student->fresh()->class_id);
    }

    /**
     * No Grade record exists yet matching "Grade 9" — this must NOT create
     * one automatically ("avoid creating duplicate Grade values from
     * Zoho"), and must leave the Student's existing class_id untouched.
     */
    public function test_sync_never_creates_a_new_grade_and_leaves_class_id_untouched_when_unmatched(): void
    {
        $branch = Branch::create(['name' => 'Branch A', 'email' => 'branch-a@example.com']);
        $existingGrade = SchoolClass::create(['branch_id' => null, 'name' => 'Grade 1']);
        $student = Student::create([
            'branch_id' => $branch->id,
            'class_id' => $existingGrade->id,
            'student_name' => 'Test Student',
            'guardian_name' => 'Guardian',
            'class' => $existingGrade->name,
            'phone_number' => '123',
            'email' => 'student-'.uniqid().'@example.com',
            'zoho_student_id' => 'NL4',
            'is_active' => true,
        ]);

        app(ZohoStudentService::class)->syncStudentFromZoho($student, [
            'Enrolment' => ['Grade' => 'Grade 9'],
        ]);

        $student->refresh();
        $this->assertSame($existingGrade->id, $student->class_id);
        $this->assertSame('Grade 9', $student->zoho_grade);
        $this->assertSame(1, SchoolClass::count());
    }

    /**
     * End-to-end: a Student's Grade (synced from Zoho) gates Exam
     * visibility exactly like the existing Grade/Subject eligibility rule,
     * with no Academic Session involved at all.
     */
    public function test_student_only_sees_exams_matching_their_zoho_synced_grade(): void
    {
        $branch = Branch::create(['name' => 'Branch A', 'email' => 'branch-a@example.com']);
        $grade2 = SchoolClass::create(['branch_id' => null, 'name' => 'Grade 2']);
        $grade3 = SchoolClass::create(['branch_id' => null, 'name' => 'Grade 3']);
        $subject = \App\Models\Subject::create(['name' => 'Science']);

        $student = Student::create([
            'branch_id' => $branch->id,
            'student_name' => 'Test Student',
            'guardian_name' => 'Guardian',
            'class' => 'Unassigned',
            'phone_number' => '123',
            'email' => 'student-'.uniqid().'@example.com',
            'zoho_student_id' => 'NL5',
            'is_active' => true,
        ]);
        // Subjects are now Zoho-driven too (StudentSubjectSyncTest), so the
        // payload must report the same Subject via Enrolment.classes[] for
        // it to remain attached after this sync — a bare manual attach()
        // would otherwise be detached by the Subject sync's replace-on-login
        // behavior.
        app(ZohoStudentService::class)->syncStudentFromZoho($student, [
            'Enrolment' => [
                'Grade' => 'Grade 2',
                'classes' => [
                    ['Class' => ['name' => 'Class 10', 'id' => 1], 'Subject' => ['name' => $subject->name, 'id' => 111]],
                ],
            ],
        ]);

        $matchingExam = Exam::create([
            'branch_id' => null,
            'school_class_id' => $grade2->id,
            'subject_id' => $subject->id,
            'title' => 'Grade 2 Exam',
            'total_marks' => 10,
            'duration_minutes' => 30,
            'maximum_attempts' => 1,
            'status' => Exam::STATUS_PUBLISHED,
            'starts_at' => now()->subMinute(),
            'ends_at' => now()->addHour(),
        ]);

        Exam::create([
            'branch_id' => null,
            'school_class_id' => $grade3->id,
            'subject_id' => $subject->id,
            'title' => 'Grade 3 Exam',
            'total_marks' => 10,
            'duration_minutes' => 30,
            'maximum_attempts' => 1,
            'status' => Exam::STATUS_PUBLISHED,
            'starts_at' => now()->subMinute(),
            'ends_at' => now()->addHour(),
        ]);

        $response = $this->actingAs($student->fresh(), 'student')->get(route('student.exams.available'));

        $response->assertOk();
        $response->assertSee('Grade 2 Exam');
        $response->assertDontSee('Grade 3 Exam');

        // Backend must reject a direct attempt at the mismatched-grade exam too.
        $mismatchExam = Exam::where('title', 'Grade 3 Exam')->firstOrFail();
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        app(\App\Services\ExamAttemptService::class)->start($mismatchExam, $student->fresh());
    }
}
