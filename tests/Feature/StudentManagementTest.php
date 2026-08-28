<?php

namespace Tests\Feature;

use App\Models\AcademicSession;
use App\Models\Branch;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class StudentManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_branch_user_only_sees_own_students(): void
    {
        [$branch, $otherBranch, $branchUser] = $this->makeBranchUser();
        $session = $this->makeAcademicSession();

        $ownStudent = Student::create($this->studentPayload([
            'branch_id' => $branch->id,
            'student_name' => 'Own Student',
            'email' => 'own@example.com',
            'session_id' => $session->id,
        ]));

        Student::create($this->studentPayload([
            'branch_id' => $otherBranch->id,
            'student_name' => 'Other Student',
            'email' => 'other@example.com',
            'session_id' => $session->id,
        ]));

        $response = $this->actingAs($branchUser)
            ->withSession(['branch_selected_academic_session_id' => $session->id])
            ->get(route('branch.students.index'));

        $response->assertOk();
        $response->assertSee($ownStudent->student_name);
        $response->assertDontSee('Other Student');
    }

    public function test_branch_user_cannot_access_another_branch_student_by_id(): void
    {
        [, $otherBranch, $branchUser] = $this->makeBranchUser();

        $otherStudent = Student::create($this->studentPayload([
            'branch_id' => $otherBranch->id,
            'email' => 'locked@example.com',
        ]));

        $this->actingAs($branchUser)
            ->get(route('branch.students.show', $otherStudent))
            ->assertForbidden();

        $this->actingAs($branchUser)
            ->put(route('branch.students.update', $otherStudent), $this->studentPayload([
                'email' => 'updated@example.com',
            ]))
            ->assertForbidden();
    }

    public function test_branch_created_student_is_forced_to_authenticated_branch(): void
    {
        [$branch, , $branchUser] = $this->makeBranchUser();
        $session = $this->makeAcademicSession();

        $this->actingAs($branchUser)
            ->withSession(['branch_selected_academic_session_id' => $session->id])
            ->post(route('branch.students.store'), $this->studentPayload([
                'branch_id' => 999,
                'email' => 'new@example.com',
            ]))
            ->assertRedirect(route('branch.students.index'));

        $this->assertDatabaseHas('students', [
            'email' => 'new@example.com',
            'branch_id' => $branch->id,
        ]);
    }

    public function test_guardian_email_is_saved_when_provided(): void
    {
        [$branch, , $branchUser] = $this->makeBranchUser();
        $session = $this->makeAcademicSession();

        $this->actingAs($branchUser)
            ->withSession(['branch_selected_academic_session_id' => $session->id])
            ->post(route('branch.students.store'), $this->studentPayload([
                'email' => 'guardian-email-student@example.com',
                'guardian_email' => 'guardian@example.com',
            ]))
            ->assertRedirect(route('branch.students.index'));

        $this->assertDatabaseHas('students', [
            'email' => 'guardian-email-student@example.com',
            'guardian_email' => 'guardian@example.com',
        ]);
    }

    public function test_guardian_email_is_optional(): void
    {
        [, , $branchUser] = $this->makeBranchUser();
        $session = $this->makeAcademicSession();

        $this->actingAs($branchUser)
            ->withSession(['branch_selected_academic_session_id' => $session->id])
            ->post(route('branch.students.store'), $this->studentPayload([
                'email' => 'no-guardian-email-student@example.com',
            ]))
            ->assertRedirect(route('branch.students.index'));

        $this->assertDatabaseHas('students', [
            'email' => 'no-guardian-email-student@example.com',
            'guardian_email' => null,
        ]);
    }

    public function test_guardian_email_must_be_a_valid_address(): void
    {
        [, , $branchUser] = $this->makeBranchUser();
        $session = $this->makeAcademicSession();

        $this->actingAs($branchUser)
            ->withSession(['branch_selected_academic_session_id' => $session->id])
            ->post(route('branch.students.store'), $this->studentPayload([
                'email' => 'invalid-guardian-email-student@example.com',
                'guardian_email' => 'not-an-email',
            ]))
            ->assertSessionHasErrors(['guardian_email' => 'Please enter a valid guardian email address.']);

        $this->assertDatabaseMissing('students', [
            'email' => 'invalid-guardian-email-student@example.com',
        ]);
    }

    public function test_super_admin_can_only_open_students_for_selected_branch(): void
    {
        [$branch, $otherBranch] = $this->makeBranches();

        $admin = User::create([
            'name' => 'Super Admin',
            'email' => 'admin@example.com',
            'role' => 'Super Admin',
            'password' => Hash::make('123456'),
        ]);

        $otherStudent = Student::create($this->studentPayload([
            'branch_id' => $otherBranch->id,
            'email' => 'other-admin@example.com',
        ]));

        $this->actingAs($admin)
            ->withSession(['admin_selected_branch_id' => $branch->id])
            ->get(route('admin.students.show', $otherStudent))
            ->assertForbidden();
    }

    private function makeBranchUser(): array
    {
        [$branch, $otherBranch] = $this->makeBranches();

        $branchUser = User::create([
            'name' => $branch->name,
            'email' => $branch->email,
            'role' => 'Branch',
            'branch_id' => $branch->id,
            'password' => Hash::make('123456'),
        ]);

        return [$branch, $otherBranch, $branchUser];
    }

    private function makeBranches(): array
    {
        return [
            Branch::create(['name' => 'Kolkata Branch', 'email' => 'kolkata@example.com']),
            Branch::create(['name' => 'Delhi Branch', 'email' => 'delhi@example.com']),
        ];
    }

    private function makeAcademicSession(): AcademicSession
    {
        return AcademicSession::create([
            'name' => '2026-2027',
            'start_date' => '2026-06-01',
            'end_date' => '2027-05-31',
            'is_active' => true,
        ]);
    }

    private function studentPayload(array $overrides = []): array
    {
        return array_merge([
            'branch_id' => 1,
            'student_name' => 'Test Student',
            'guardian_name' => 'Test Guardian',
            'class' => 'Class 10',
            'phone_number' => '9876543210',
            'email' => 'student@example.com',
        ], $overrides);
    }
}
