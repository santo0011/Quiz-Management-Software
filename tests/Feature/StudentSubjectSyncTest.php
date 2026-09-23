<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use App\Services\ZohoStudentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Manage Subjects (manually assigning/unassigning a Student's Subjects from
 * the Student List) has been removed entirely. A Student's Subjects now come
 * exclusively from Zoho's `Enrolment.classes[].Subject` on every login,
 * resolved against existing Subject records case/whitespace-insensitively,
 * auto-creating one when no match exists — the same pattern already used
 * for Grade in StudentGradeSyncTest.
 */
class StudentSubjectSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_manage_subjects_routes_no_longer_exist(): void
    {
        $this->assertFalse(Route::has('admin.students.subjects.update'));
        $this->assertFalse(Route::has('branch.students.subjects.update'));
    }

    public function test_admin_students_index_shows_no_manage_subjects_action(): void
    {
        $admin = $this->makeAdmin();
        $student = $this->makeStudent();

        $response = $this->actingAs($admin)
            ->withSession(['admin_selected_branch_id' => $student->branch_id])
            ->get(route('admin.students.index'));

        $response->assertOk();
        $response->assertDontSee('Manage Subjects');
    }

    public function test_admin_students_show_page_has_no_manage_subjects_action(): void
    {
        $admin = $this->makeAdmin();
        $student = $this->makeStudent();

        $response = $this->actingAs($admin)
            ->withSession(['admin_selected_branch_id' => $student->branch_id])
            ->get(route('admin.students.show', $student));

        $response->assertOk();
        $response->assertDontSee('Manage Subjects');
    }

    public function test_admin_students_show_page_lists_the_students_synced_subjects_read_only(): void
    {
        $admin = $this->makeAdmin();
        $student = $this->makeStudent();
        $subject = Subject::create(['name' => 'Science']);
        $student->subjects()->attach($subject->id);

        $response = $this->actingAs($admin)
            ->withSession(['admin_selected_branch_id' => $student->branch_id])
            ->get(route('admin.students.show', $student));

        $response->assertOk();
        $response->assertSee('Science');
        $response->assertDontSee('Manage Subjects');
    }

    public function test_sync_attaches_the_existing_subject_matching_the_zoho_class_subject_name(): void
    {
        $branch = Branch::create(['name' => 'Branch A', 'email' => 'branch-a@example.com']);
        $science = Subject::create(['name' => 'Science']);
        $student = $this->studentFor($branch, 'NL-SUB-1');

        app(ZohoStudentService::class)->syncStudentFromZoho($student, [
            'Enrolment' => [
                'classes' => [
                    ['Class' => ['name' => 'Class 10', 'id' => 1], 'Subject' => ['name' => 'Science', 'id' => 96867000000516050]],
                ],
            ],
        ]);

        $this->assertSame(['Science'], $student->fresh()->subjects->pluck('name')->all());
        $this->assertSame(1, Subject::count());
        $this->assertSame($science->id, $student->fresh()->subjects->first()->id);
    }

    /**
     * A Student may have multiple classes/subjects inside
     * `Enrolment.classes[]` — every reported Subject must be attached, not
     * just the first/active one.
     */
    public function test_sync_attaches_every_subject_across_multiple_classes(): void
    {
        $branch = Branch::create(['name' => 'Branch A', 'email' => 'branch-a@example.com']);
        Subject::create(['name' => 'Science']);
        Subject::create(['name' => 'Mathematics']);
        $student = $this->studentFor($branch, 'NL-SUB-2');

        app(ZohoStudentService::class)->syncStudentFromZoho($student, [
            'Enrolment' => [
                'classes' => [
                    ['Class' => ['name' => 'Class 10', 'id' => 1], 'Subject' => ['name' => 'Science', 'id' => 111]],
                    ['Class' => ['name' => 'Class 10', 'id' => 1], 'Subject' => ['name' => 'Mathematics', 'id' => 222]],
                ],
            ],
        ]);

        $this->assertEqualsCanonicalizing(
            ['Science', 'Mathematics'],
            $student->fresh()->subjects->pluck('name')->all()
        );
    }

    /**
     * Matches the exact documented Zoho response shape: each entry inside
     * `Enrolment.classes[]` pairs exactly ONE `Class` object with ONE
     * `Subject` object (never an array of Subjects under one class) — a
     * Student can have several such Class+Subject pairs, and every one of
     * them must be picked up, handled dynamically rather than hardcoded to
     * any particular Subject names.
     */
    public function test_sync_attaches_one_subject_per_class_for_every_class_subject_pair(): void
    {
        $branch = Branch::create(['name' => 'Branch A', 'email' => 'branch-a@example.com']);
        Subject::create(['name' => 'Science']);
        Subject::create(['name' => 'English']);
        Subject::create(['name' => 'Mathematics']);
        $student = $this->studentFor($branch, 'NL-SUB-7');

        app(ZohoStudentService::class)->syncStudentFromZoho($student, [
            'Enrolment' => [
                'classes' => [
                    [
                        'Class' => ['name' => 'Science | Grade 2 (Group) | Ringwood (Head Office)', 'id' => '96867000000927309'],
                        'Subject' => ['name' => 'Science', 'id' => '96867000000516050'],
                    ],
                    [
                        'Class' => ['name' => 'English | Grade 2 (Group) | Ringwood (Head Office)', 'id' => '96867000000927307'],
                        'Subject' => ['name' => 'English', 'id' => '96867000000516052'],
                    ],
                    [
                        'Class' => ['name' => 'Mathematics | Grade 2 (1 on 1) | Ringwood (Head Office)', 'id' => '96867000000929421'],
                        'Subject' => ['name' => 'Mathematics', 'id' => '96867000000516051'],
                    ],
                ],
            ],
        ]);

        $this->assertEqualsCanonicalizing(
            ['Science', 'English', 'Mathematics'],
            $student->fresh()->subjects->pluck('name')->all()
        );
    }

    public function test_sync_matches_subject_name_case_and_whitespace_insensitively(): void
    {
        $branch = Branch::create(['name' => 'Branch A', 'email' => 'branch-a@example.com']);
        $science = Subject::create(['name' => 'Science']);
        $student = $this->studentFor($branch, 'NL-SUB-3');

        app(ZohoStudentService::class)->syncStudentFromZoho($student, [
            'Enrolment' => [
                'classes' => [
                    ['Class' => ['name' => 'Class 10', 'id' => 1], 'Subject' => ['name' => '  science  ', 'id' => 111]],
                ],
            ],
        ]);

        $this->assertSame([$science->id], $student->fresh()->subjects->pluck('id')->all());
    }

    /**
     * A Zoho Subject name with no matching local record must be
     * auto-created (never left unassigned) and attached to the Student.
     */
    public function test_sync_auto_creates_a_new_subject_for_an_unmatched_zoho_subject_name(): void
    {
        $branch = Branch::create(['name' => 'Branch A', 'email' => 'branch-a@example.com']);
        $student = $this->studentFor($branch, 'NL-SUB-4');

        app(ZohoStudentService::class)->syncStudentFromZoho($student, [
            'Enrolment' => [
                'classes' => [
                    ['Class' => ['name' => 'Class 10', 'id' => 1], 'Subject' => ['name' => 'Astrophysics', 'id' => 999]],
                ],
            ],
        ]);

        $this->assertSame(1, Subject::count());
        $this->assertSame('Astrophysics', Subject::first()->name);
        $this->assertSame(['Astrophysics'], $student->fresh()->subjects->pluck('name')->all());
    }

    /**
     * All THREE Subjects across multiple classes must be auto-created when
     * none of them exist yet — not just the first one.
     */
    public function test_sync_auto_creates_every_missing_subject_across_multiple_classes(): void
    {
        $branch = Branch::create(['name' => 'Branch A', 'email' => 'branch-a@example.com']);
        $student = $this->studentFor($branch, 'NL-SUB-8');

        app(ZohoStudentService::class)->syncStudentFromZoho($student, [
            'Enrolment' => [
                'classes' => [
                    ['Class' => ['name' => 'Class 10', 'id' => 1], 'Subject' => ['name' => 'English', 'id' => 1]],
                    ['Class' => ['name' => 'Class 10', 'id' => 1], 'Subject' => ['name' => 'Mathematics', 'id' => 2]],
                    ['Class' => ['name' => 'Class 10', 'id' => 1], 'Subject' => ['name' => 'Science', 'id' => 3]],
                ],
            ],
        ]);

        $this->assertSame(3, Subject::count());
        $this->assertEqualsCanonicalizing(
            ['English', 'Mathematics', 'Science'],
            $student->fresh()->subjects->pluck('name')->all()
        );
    }

    /**
     * Uppercase/lowercase variants of the same Subject name (whether
     * already existing or reported twice by Zoho) must never create a
     * duplicate Subject record.
     */
    public function test_sync_does_not_create_duplicate_subjects_for_case_insensitive_matches(): void
    {
        $branch = Branch::create(['name' => 'Branch A', 'email' => 'branch-a@example.com']);
        Subject::create(['name' => 'Mathematics']);
        $student = $this->studentFor($branch, 'NL-SUB-9');

        app(ZohoStudentService::class)->syncStudentFromZoho($student, [
            'Enrolment' => [
                'classes' => [
                    ['Class' => ['name' => 'Class 10', 'id' => 1], 'Subject' => ['name' => 'MATHEMATICS', 'id' => 1]],
                    ['Class' => ['name' => 'Class 10', 'id' => 1], 'Subject' => ['name' => 'mathematics', 'id' => 2]],
                ],
            ],
        ]);

        $this->assertSame(1, Subject::count());
        $this->assertSame(['Mathematics'], $student->fresh()->subjects->pluck('name')->all());
    }

    /**
     * Subjects are no longer manually editable — each login's sync() must
     * REPLACE the previous set with exactly what Zoho now reports, detaching
     * anything Zoho no longer lists.
     */
    public function test_sync_replaces_previously_synced_subjects_not_reported_by_zoho_anymore(): void
    {
        $branch = Branch::create(['name' => 'Branch A', 'email' => 'branch-a@example.com']);
        $science = Subject::create(['name' => 'Science']);
        $maths = Subject::create(['name' => 'Mathematics']);
        $student = $this->studentFor($branch, 'NL-SUB-5');
        $student->subjects()->attach([$science->id, $maths->id]);

        app(ZohoStudentService::class)->syncStudentFromZoho($student, [
            'Enrolment' => [
                'classes' => [
                    ['Class' => ['name' => 'Class 10', 'id' => 1], 'Subject' => ['name' => 'Mathematics', 'id' => 222]],
                ],
            ],
        ]);

        $this->assertSame(['Mathematics'], $student->fresh()->subjects->pluck('name')->all());
    }

    /**
     * The core Zoho/local fields the Grade sync already relies on must be
     * unaffected by this Subject-sync step running alongside it.
     */
    public function test_sync_does_not_affect_core_student_fields(): void
    {
        $branch = Branch::create(['name' => 'Branch A', 'email' => 'branch-a@example.com']);
        Subject::create(['name' => 'Science']);
        $student = $this->studentFor($branch, 'NL-SUB-6');

        app(ZohoStudentService::class)->syncStudentFromZoho($student, [
            'Enrolment' => [
                'Grade' => 'Grade 5',
                'classes' => [
                    ['Class' => ['name' => 'Class 10', 'id' => 1], 'Subject' => ['name' => 'Science', 'id' => 111]],
                ],
            ],
        ]);

        $fresh = $student->fresh();
        $this->assertSame('Test Student', $fresh->student_name);
        $this->assertSame($branch->id, $fresh->branch_id);
        $this->assertSame('NL-SUB-6', $fresh->zoho_student_id);
        $this->assertSame('Grade 5', $fresh->zoho_grade);
    }

    private function makeAdmin(): User
    {
        return User::create([
            'name' => 'Super Admin',
            'email' => 'admin-'.uniqid().'@example.com',
            'role' => 'Super Admin',
            'password' => Hash::make('123456'),
        ]);
    }

    private function makeStudent(): Student
    {
        $branch = Branch::create(['name' => 'Branch '.uniqid(), 'email' => 'branch-'.uniqid().'@example.com', 'is_active' => true]);
        $class = SchoolClass::create(['branch_id' => $branch->id, 'name' => 'Class 10']);

        return Student::create([
            'branch_id' => $branch->id,
            'class_id' => $class->id,
            'student_name' => 'Test Student',
            'guardian_name' => 'Guardian',
            'class' => $class->name,
            'phone_number' => '9876543210',
            'zoho_student_id' => 'NL-'.uniqid(),
            'email' => 'student-'.uniqid().'@example.com',
            'is_active' => true,
        ]);
    }

    private function studentFor(Branch $branch, string $zohoStudentId): Student
    {
        return Student::create([
            'branch_id' => $branch->id,
            'student_name' => 'Test Student',
            'guardian_name' => 'Guardian',
            'class' => 'Unassigned',
            'phone_number' => '123',
            'email' => 'student-'.uniqid().'@example.com',
            'zoho_student_id' => $zohoStudentId,
            'is_active' => true,
        ]);
    }
}
