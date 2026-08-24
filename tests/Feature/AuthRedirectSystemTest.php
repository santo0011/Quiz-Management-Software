<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthRedirectSystemTest extends TestCase
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

        $branch = Branch::create(['name' => 'Kolkata Branch', 'email' => 'branch@example.com']);

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

    public function test_authenticated_super_admin_visiting_login_page_is_redirected_to_admin_dashboard(): void
    {
        $this->actingAs($this->superAdmin)
            ->get(route('login'))
            ->assertRedirect(route('admin.dashboard'));
    }

    public function test_authenticated_branch_visiting_login_page_is_redirected_to_branch_dashboard(): void
    {
        $this->actingAs($this->branchUser)
            ->get(route('login'))
            ->assertRedirect(route('branch.dashboard'));
    }

    public function test_authenticated_student_visiting_login_page_is_redirected_to_student_dashboard(): void
    {
        // Previously the "guest" route group only checked the default "web"
        // guard, so a logged-in student hitting /login saw the login form
        // again instead of being redirected.
        $this->actingAs($this->student, 'student')
            ->get(route('login'))
            ->assertRedirect(route('student.dashboard'));
    }

    public function test_authenticated_super_admin_visiting_password_reset_pages_is_redirected_away(): void
    {
        $this->actingAs($this->superAdmin)
            ->get(route('password.request'))
            ->assertRedirect(route('admin.dashboard'));
    }

    public function test_guest_visiting_login_page_sees_the_login_form(): void
    {
        $this->get(route('login'))->assertOk()->assertViewIs('auth.login');
    }

    public function test_root_url_redirects_authenticated_user_straight_to_their_dashboard(): void
    {
        $this->actingAs($this->branchUser)
            ->get('/')
            ->assertRedirect(route('branch.dashboard'));
    }

    public function test_root_url_redirects_guest_to_login(): void
    {
        $this->get('/')->assertRedirect(route('login'));
    }

    public function test_unauthenticated_visit_to_a_protected_page_stores_it_as_the_intended_destination(): void
    {
        // redirect()->guest() (used by the AuthenticationException handler)
        // is what actually records "url.intended" for the following login
        // to honor; confirm it still does so after the middleware changes.
        $this->get(route('branch.password.edit'))
            ->assertRedirect(route('login'));

        $this->assertSame(route('branch.password.edit'), session('url.intended'));
    }

    public function test_login_redirects_to_the_intended_branch_page_instead_of_the_dashboard(): void
    {
        $this->withSession(['url.intended' => route('branch.password.edit')])
            ->post(route('login.store'), [
                'login_type' => 'branch',
                'email' => 'branch@example.com',
                'password' => '123456',
            ])->assertRedirect(route('branch.password.edit'));
    }

    public function test_login_redirects_to_the_intended_student_page_instead_of_the_dashboard(): void
    {
        $this->student->forceFill(['login_code_hash' => Hash::make('654321')])->save();

        $this->withSession(['url.intended' => route('student.profile')])
            ->post(route('login.store'), [
                'login_type' => 'student',
                'email' => 'student@example.com',
                'password' => '654321',
            ])->assertRedirect(route('student.profile'));
    }

    public function test_login_falls_back_to_the_dashboard_when_nothing_was_intended(): void
    {
        $this->post(route('login.store'), [
            'login_type' => 'branch',
            'email' => 'branch@example.com',
            'password' => '123456',
        ])->assertRedirect(route('branch.dashboard'));
    }
}
