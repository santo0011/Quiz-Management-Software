<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Exam;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Subject;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The Available / Upcoming / Completed stat cards on the Student Dashboard
 * are tabs, and each tab lists its own exams right on the dashboard.
 */
class StudentDashboardStatLinksTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_tabs_list_available_and_upcoming_exams(): void
    {
        $branch = Branch::create(['name' => 'Ringwood', 'email' => 'ringwood@example.com', 'is_active' => true]);
        $class = SchoolClass::create(['branch_id' => null, 'name' => 'Grade 3']);
        $subject = Subject::create(['name' => 'Mathematics']);

        $student = Student::create([
            'branch_id' => $branch->id,
            'class_id' => $class->id,
            'student_name' => 'avsbera',
            'guardian_name' => 'Guardian',
            'class' => $class->name,
            'phone_number' => '',
            'email' => 'student@example.com',
            'is_active' => true,
        ]);
        $student->subjects()->attach($subject->id);

        $available = $this->exam($class, $subject, 'Open Maths Test', now()->subMinute(), now()->addHour());
        $this->exam($class, $subject, 'Next Week Maths Test', now()->addDays(7), now()->addDays(8));

        $content = $this->actingAs($student, 'student')
            ->get(route('student.dashboard'))
            ->assertOk()
            ->assertSee('data-exam-tab="available"', false)
            ->assertSee('data-exam-tab="upcoming"', false)
            ->assertSee('data-exam-tab="completed"', false)
            ->assertDontSee('View exams')
            ->getContent();

        $availablePanel = $this->panel($content, 'available');
        $upcomingPanel = $this->panel($content, 'upcoming');

        $this->assertStringContainsString('Open Maths Test', $availablePanel);
        $this->assertStringContainsString(route('student.exams.show', $available), $availablePanel);
        $this->assertStringNotContainsString('Next Week Maths Test', $availablePanel);

        $this->assertStringContainsString('Next Week Maths Test', $upcomingPanel);
        $this->assertStringNotContainsString('Open Maths Test', $upcomingPanel);

        $this->assertStringContainsString('No completed exams yet', $this->panel($content, 'completed'));
    }

    private function exam(SchoolClass $class, Subject $subject, string $title, $starts, $ends): Exam
    {
        return Exam::create([
            'branch_id' => null,
            'school_class_id' => $class->id,
            'subject_id' => $subject->id,
            'title' => $title,
            'total_marks' => 10,
            'duration_minutes' => 30,
            'maximum_attempts' => 1,
            'status' => Exam::STATUS_PUBLISHED,
            'starts_at' => $starts,
            'ends_at' => $ends,
        ]);
    }

    private function panel(string $html, string $name): string
    {
        $start = strpos($html, 'data-exam-panel="'.$name.'"');
        $this->assertNotFalse($start, "Panel {$name} missing");
        $next = strpos($html, 'data-exam-panel="', $start + 20);
        $end = $next !== false ? $next : strpos($html, '</section>', $start);

        return substr($html, $start, $end - $start);
    }
}
