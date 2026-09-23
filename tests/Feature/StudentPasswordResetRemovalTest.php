<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\SchoolClass;
use App\Models\Setting;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Students have no individual password at all — they log in either via
 * Zoho OTP, or via Teacher Override's single global Common Password. This
 * guards against the per-Student "Reset Password" feature (route,
 * controller action, and UI) coming back.
 */
class StudentPasswordResetRemovalTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_password_update_route_no_longer_exists(): void
    {
        $this->assertFalse(Route::has('admin.students.password.update'));
    }

    public function test_admin_students_index_shows_no_reset_password_action(): void
    {
        $admin = $this->makeAdmin();
        $student = $this->makeStudent();

        $response = $this->actingAs($admin)
            ->withSession(['admin_selected_branch_id' => $student->branch_id])
            ->get(route('admin.students.index'));

        $response->assertOk();
        $response->assertDontSee('Reset Password');
        $response->assertDontSee('Edit Student');
        $response->assertDontSee('>Delete<', false);
    }

    public function test_admin_students_show_page_has_no_reset_password_action(): void
    {
        $admin = $this->makeAdmin();
        $student = $this->makeStudent();

        $response = $this->actingAs($admin)
            ->withSession(['admin_selected_branch_id' => $student->branch_id])
            ->get(route('admin.students.show', $student));

        $response->assertOk();
        $response->assertDontSee('Reset Password');
    }

    /**
     * The "Forgot Password" self-service flow no longer accepts a Student
     * account at all — it only ever applied to Super Admin/Branch.
     */
    public function test_forgot_password_flow_rejects_the_student_type(): void
    {
        $this->post(route('password.email'), [
            'type' => 'student',
            'email' => 'anyone@example.com',
        ])->assertSessionHasErrors(['type']);
    }

    /**
     * Untouched by this change: the Teacher Override Common Password
     * remains the one global setting shared by every branch/student, and
     * still works exactly as before.
     */
    public function test_teacher_override_global_password_setting_is_unaffected(): void
    {
        Setting::current()->update(['common_student_password' => 'ABC123']);

        $this->assertTrue(Setting::current()->hasCommonStudentPassword());
        $this->assertTrue(Hash::check('ABC123', Setting::current()->common_student_password));
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
}
