<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Student;
use App\Services\SingleSessionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentDashboardStatLinksTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_stat_cards_link_to_their_pages(): void
    {
        $branch = Branch::create(['name' => 'Ringwood', 'email' => 'ringwood@example.com']);
        $student = Student::create([
            'branch_id' => $branch->id,
            'student_name' => 'avsbera',
            'guardian_name' => 'Guardian',
            'class' => 'Grade 3',
            'phone_number' => '',
            'email' => 'student@example.com',
            'zoho_student_id' => 'NL1405',
            'is_active' => true,
        ]);

        $this->actingAs($student, 'student');
        SingleSessionService::establish($student, 'student');

        $this->get(route('student.dashboard'))
            ->assertOk()
            ->assertSee('href="'.route('student.exams.available').'" class="student-stat student-stat-link"', false)
            ->assertSee('href="'.route('student.exams.upcoming').'" class="student-stat student-stat-link"', false)
            ->assertSee('href="'.route('student.results.index').'" class="student-stat student-stat-link"', false);
    }
}
