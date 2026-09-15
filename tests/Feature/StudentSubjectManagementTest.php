<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Students are read-only local records (their core data comes from Zoho) —
 * Edit/Delete must be gone from the UI and the routes, and the only local
 * modification left is managing a Student's Subject assignments.
 */
class StudentSubjectManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_students_index_shows_manage_subjects_and_no_edit_or_delete(): void
    {
        $admin = $this->makeAdmin();
        $student = $this->makeStudent();

        $response = $this->actingAs($admin)->get(route('admin.students.index'));

        $response->assertOk();
        $response->assertSee('Manage Subjects');
        $response->assertDontSee('Edit Student');
        $response->assertDontSee('>Delete<', false);
    }

    public function test_admin_students_show_page_offers_manage_subjects_not_edit(): void
    {
        $admin = $this->makeAdmin();
        $student = $this->makeStudent();

        $response = $this->actingAs($admin)->get(route('admin.students.show', $student));

        $response->assertOk();
        $response->assertSee('Manage Subjects');
        $response->assertDontSee('Edit Student');
    }

    public function test_edit_update_and_destroy_routes_no_longer_exist_for_admin_students(): void
    {
        $this->assertFalse(\Illuminate\Support\Facades\Route::has('admin.students.edit'));
        $this->assertFalse(\Illuminate\Support\Facades\Route::has('admin.students.update'));
        $this->assertFalse(\Illuminate\Support\Facades\Route::has('admin.students.destroy'));
        $this->assertFalse(\Illuminate\Support\Facades\Route::has('branch.students.edit'));
        $this->assertFalse(\Illuminate\Support\Facades\Route::has('branch.students.update'));
        $this->assertFalse(\Illuminate\Support\Facades\Route::has('branch.students.destroy'));
    }

    public function test_admin_can_assign_and_unassign_student_subjects(): void
    {
        $admin = $this->makeAdmin();
        $student = $this->makeStudent();
        $math = Subject::create(['name' => 'Mathematics']);
        $science = Subject::create(['name' => 'Science']);
        $student->subjects()->attach($math->id);

        $this->actingAs($admin)
            ->put(route('admin.students.subjects.update', $student), ['subject_ids' => [$science->id]])
            ->assertRedirect(route('admin.students.index'));

        $student->refresh();
        $this->assertEqualsCanonicalizing([$science->id], $student->subjects->pluck('id')->all());
    }

    public function test_assigning_subjects_prevents_duplicates_and_ignores_repeated_ids(): void
    {
        $admin = $this->makeAdmin();
        $student = $this->makeStudent();
        $science = Subject::create(['name' => 'Science']);

        $this->actingAs($admin)
            ->put(route('admin.students.subjects.update', $student), ['subject_ids' => [$science->id, $science->id]])
            ->assertRedirect(route('admin.students.index'));

        $this->assertSame(1, $student->subjects()->count());
    }

    public function test_assigning_subjects_does_not_touch_the_students_core_zoho_data(): void
    {
        $admin = $this->makeAdmin();
        $student = $this->makeStudent();
        $science = Subject::create(['name' => 'Science']);
        $originalName = $student->student_name;
        $originalZohoId = $student->zoho_student_id;
        $originalClass = $student->class;
        $originalClassId = $student->class_id;

        $this->actingAs($admin)
            ->put(route('admin.students.subjects.update', $student), ['subject_ids' => [$science->id]]);

        $student->refresh();
        $this->assertSame($originalName, $student->student_name);
        $this->assertSame($originalZohoId, $student->zoho_student_id);
        $this->assertSame($originalClass, $student->class);
        $this->assertSame($originalClassId, $student->class_id);
    }

    public function test_branch_can_manage_subjects_for_its_own_student_only(): void
    {
        $branch = Branch::create(['name' => 'Branch A', 'email' => 'branch-a@example.com', 'is_active' => true]);
        $otherBranch = Branch::create(['name' => 'Branch B', 'email' => 'branch-b@example.com', 'is_active' => true]);
        $branchUser = User::create([
            'name' => $branch->name,
            'email' => $branch->email,
            'role' => 'Branch',
            'branch_id' => $branch->id,
            'password' => Hash::make('secret123'),
        ]);

        $ownStudent = $this->makeStudent('own', $branch);
        $otherStudent = $this->makeStudent('other', $otherBranch);
        $subject = Subject::create(['name' => 'Science']);

        $this->actingAs($branchUser)
            ->put(route('branch.students.subjects.update', $ownStudent), ['subject_ids' => [$subject->id]])
            ->assertRedirect(route('branch.students.index'));

        $this->assertSame([$subject->id], $ownStudent->fresh()->subjects->pluck('id')->all());

        $this->actingAs($branchUser)
            ->put(route('branch.students.subjects.update', $otherStudent), ['subject_ids' => [$subject->id]])
            ->assertForbidden();

        $this->assertEmpty($otherStudent->fresh()->subjects);
    }

    public function test_student_guard_cannot_manage_its_own_subjects(): void
    {
        $student = $this->makeStudent();
        $subject = Subject::create(['name' => 'Science']);

        // A Student is authenticated, just under the wrong guard/role for
        // this Super-Admin-only route — the app's existing role middleware
        // bounces them to their own dashboard rather than the admin panel.
        $this->actingAs($student, 'student')
            ->put(route('admin.students.subjects.update', $student), ['subject_ids' => [$subject->id]])
            ->assertRedirect(route('student.dashboard'));

        $this->assertEmpty($student->fresh()->subjects);
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

    private function makeStudent(string $name = 'test-student', ?Branch $branch = null): Student
    {
        $branch ??= Branch::create(['name' => 'Branch '.uniqid(), 'email' => 'branch-'.uniqid().'@example.com', 'is_active' => true]);
        $class = SchoolClass::create(['branch_id' => $branch->id, 'name' => 'Class 10']);

        return Student::create([
            'branch_id' => $branch->id,
            'class_id' => $class->id,
            'student_name' => $name,
            'guardian_name' => 'Guardian',
            'class' => $class->name,
            'phone_number' => '9876543210',
            'zoho_student_id' => 'NL-'.uniqid(),
            'email' => $name.'-'.uniqid().'@example.com',
            'is_active' => true,
        ]);
    }
}
