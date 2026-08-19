<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ErrorHandlingTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;
    private User $branchUser;
    private Student $student;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superAdmin = User::create([
            'name' => 'Super Admin',
            'email' => 'admin@example.com',
            'role' => 'Super Admin',
            'password' => Hash::make('123456'),
        ]);

        $branch = Branch::create([
            'name' => 'Kolkata Branch',
            'email' => 'branch@example.com',
        ]);

        $this->branchUser = User::create([
            'name' => $branch->name,
            'email' => $branch->email,
            'role' => 'Branch',
            'branch_id' => $branch->id,
            'password' => Hash::make('123456'),
        ]);

        $this->student = Student::create([
            'branch_id' => $branch->id,
            'student_name' => 'Rahul Kumar',
            'guardian_name' => 'Guardian',
            'class' => 'Class 10',
            'phone_number' => '9876543210',
            'email' => 'student@example.com',
            'is_active' => true,
        ]);
    }

    public function test_non_existing_url_returns_custom_404_page(): void
    {
        $this->get('/this-page-does-not-exist')
            ->assertStatus(404)
            ->assertSee('404')
            ->assertSee('Page Not Found')
            ->assertSee('Go to Login');
    }

    public function test_non_existing_url_for_authenticated_user_returns_custom_404_with_dashboard_button(): void
    {
        $this->actingAs($this->superAdmin)
            ->get('/this-page-does-not-exist')
            ->assertStatus(404)
            ->assertSee('404')
            ->assertSee('Page Not Found')
            ->assertSee('Go to Dashboard');
    }

    public function test_unauthenticated_user_visiting_admin_route_redirects_to_login(): void
    {
        $this->get('/admin/dashboard')
            ->assertRedirect(route('login'))
            ->assertSessionHas('login_error', 'Please login to continue.');
    }

    public function test_unauthenticated_user_visiting_branch_route_redirects_to_login(): void
    {
        $this->get('/branch/dashboard')
            ->assertRedirect(route('login'))
            ->assertSessionHas('login_error', 'Please login to continue.');
    }

    public function test_unauthenticated_user_visiting_student_route_redirects_to_login(): void
    {
        $this->get('/student/dashboard')
            ->assertRedirect(route('login'))
            ->assertSessionHas('login_error', 'Please login to continue.');
    }

    public function test_branch_user_accessing_admin_route_redirects_to_branch_dashboard(): void
    {
        $this->actingAs($this->branchUser)
            ->get('/admin/dashboard')
            ->assertRedirect(route('branch.dashboard'))
            ->assertSessionHas('error', 'You do not have permission to access that area.');
    }

    public function test_super_admin_accessing_branch_route_redirects_to_admin_dashboard(): void
    {
        $this->actingAs($this->superAdmin)
            ->get('/branch/dashboard')
            ->assertRedirect(route('admin.dashboard'))
            ->assertSessionHas('error', 'You do not have permission to access that area.');
    }

    public function test_student_accessing_admin_route_redirects_to_student_dashboard(): void
    {
        $this->actingAs($this->student, 'student')
            ->get('/admin/dashboard')
            ->assertRedirect(route('student.dashboard'))
            ->assertSessionHas('error', 'You do not have permission to access that area.');
    }

    public function test_student_accessing_branch_route_redirects_to_student_dashboard(): void
    {
        $this->actingAs($this->student, 'student')
            ->get('/branch/dashboard')
            ->assertRedirect(route('student.dashboard'))
            ->assertSessionHas('error', 'You do not have permission to access that area.');
    }
}
