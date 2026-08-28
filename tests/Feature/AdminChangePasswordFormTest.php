<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminChangePasswordFormTest extends TestCase
{
    use RefreshDatabase;

    public function test_mismatched_passwords_are_rejected_with_clear_message_and_branch_password_unchanged(): void
    {
        $admin = $this->makeAdmin();
        [$branch, $branchUser] = $this->makeBranch();

        $response = $this->actingAs($admin)->put(route('admin.branches.password.update', $branch), [
            '_drawer' => 'editBranchDrawer'.$branch->id,
            'password' => 'new-password-1',
            'password_confirmation' => 'different-password',
        ]);

        $response->assertSessionHasErrors(['password' => 'Passwords do not match.']);
        $response->assertSessionHas('_old_input._drawer', 'editBranchDrawer'.$branch->id);

        $this->assertTrue(Hash::check('secret123', $branchUser->fresh()->password));
    }

    public function test_matching_passwords_update_the_branch_password_and_flash_success(): void
    {
        $admin = $this->makeAdmin();
        [$branch, $branchUser] = $this->makeBranch();

        $response = $this->actingAs($admin)->put(route('admin.branches.password.update', $branch), [
            '_drawer' => 'editBranchDrawer'.$branch->id,
            'password' => 'new-password-1',
            'password_confirmation' => 'new-password-1',
        ]);

        $response->assertRedirect(route('admin.branches.index'));
        $response->assertSessionHas('success', 'Branch password updated successfully.');
        $response->assertSessionMissing('_old_input._drawer');

        $this->assertTrue(Hash::check('new-password-1', $branchUser->fresh()->password));
    }

    public function test_mismatched_passwords_are_rejected_for_student_and_password_unchanged(): void
    {
        $admin = $this->makeAdmin();
        $student = $this->makeStudent();

        $response = $this->actingAs($admin)->put(route('admin.students.password.update', $student), [
            '_drawer' => 'editStudentDrawer'.$student->id,
            'password' => 'new-password-1',
            'password_confirmation' => 'oops-mismatch',
        ]);

        $response->assertSessionHasErrors(['password' => 'Passwords do not match.']);
        $response->assertSessionHas('_old_input._drawer', 'editStudentDrawer'.$student->id);

        $this->assertTrue(Hash::check('secret123', $student->fresh()->password));
    }

    public function test_matching_passwords_update_the_student_password_and_flash_success(): void
    {
        $admin = $this->makeAdmin();
        $student = $this->makeStudent();

        $response = $this->actingAs($admin)->put(route('admin.students.password.update', $student), [
            '_drawer' => 'editStudentDrawer'.$student->id,
            'password' => 'new-password-1',
            'password_confirmation' => 'new-password-1',
        ]);

        $response->assertRedirect(route('admin.students.index'));
        $response->assertSessionHas('success', 'Student password updated successfully.');

        $this->assertTrue(Hash::check('new-password-1', $student->fresh()->password));
    }

    public function test_password_edit_form_shows_error_only_for_the_submitted_row(): void
    {
        $admin = $this->makeAdmin();
        $session = \App\Models\AcademicSession::create([
            'name' => 'Session '.uniqid(),
            'start_date' => now()->subMonth(),
            'end_date' => now()->addMonths(6),
            'is_active' => true,
        ]);
        $studentOne = $this->makeStudent('student-one', $session);
        $studentTwo = $this->makeStudent('student-two', $session);

        $this->withSession(['admin_selected_academic_session_id' => $session->id])
            ->actingAs($admin)
            ->put(route('admin.students.password.update', $studentOne), [
                '_drawer' => 'editStudentDrawer'.$studentOne->id,
                'password' => 'new-password-1',
                'password_confirmation' => 'mismatch',
            ])
            ->assertSessionHasErrors('password');

        $page = $this->actingAs($admin)->get(route('admin.students.index'));

        $page->assertOk();
        $page->assertSee('Passwords do not match.');

        // Only one "Passwords do not match." alert should render — proving
        // the error is scoped to student one's own drawer/form instance and
        // does not bleed into student two's identical-looking form.
        $page->assertSeeInOrder(['Passwords do not match.']);
        $this->assertSame(
            1,
            substr_count($page->getContent(), 'Passwords do not match.'),
            'Expected the mismatch error to render exactly once (scoped to the submitted row only).'
        );
    }

    private function makeAdmin(): User
    {
        return User::create([
            'name' => 'Super Admin',
            'email' => 'admin-'.uniqid().'@example.com',
            'role' => 'Super Admin',
            'password' => Hash::make('secret123'),
        ]);
    }

    private function makeBranch(): array
    {
        $branch = Branch::create(['name' => 'Branch '.uniqid(), 'email' => 'branch-'.uniqid().'@example.com', 'is_active' => true]);
        $branchUser = User::create([
            'name' => $branch->name,
            'email' => $branch->email,
            'role' => 'Branch',
            'branch_id' => $branch->id,
            'password' => Hash::make('secret123'),
        ]);

        return [$branch, $branchUser];
    }

    private function makeStudent(string $name = 'test-student', ?\App\Models\AcademicSession $session = null): Student
    {
        $branch = Branch::create(['name' => 'Branch '.uniqid(), 'email' => 'branch-'.uniqid().'@example.com', 'is_active' => true]);
        $class = SchoolClass::create(['branch_id' => $branch->id, 'name' => 'Class 10']);

        return Student::create([
            'branch_id' => $branch->id,
            'class_id' => $class->id,
            'session_id' => $session?->id,
            'student_name' => $name,
            'guardian_name' => 'Guardian',
            'class' => $class->name,
            'phone_number' => '9876543210',
            'email' => $name.'-'.uniqid().'@example.com',
            'password' => Hash::make('secret123'),
            'is_active' => true,
        ]);
    }
}
