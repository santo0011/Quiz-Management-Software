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
            ->assertDontSee('View exams')
            ->assertDontSee('data-exam-tab=', false)
            ->getContent();

        // The three cards link to their own pages, showing the counts.
        $this->assertMatchesRegularExpression('#<a href="'.preg_quote(route('student.exams.available'), '#').'"[^>]*student-stat-link[^>]*>\s*<span>Available Exams</span><strong>1</strong>#', $content);
        $this->assertMatchesRegularExpression('#<a href="'.preg_quote(route('student.exams.upcoming'), '#').'"[^>]*student-stat-link[^>]*>\s*<span>Upcoming Exams</span><strong>1</strong>#', $content);
        $this->assertMatchesRegularExpression('#<a href="'.preg_quote(route('student.results.index'), '#').'"[^>]*student-stat-link[^>]*>\s*<span>Completed Exams</span><strong>0</strong>#', $content);

        // Below the cards, the dashboard lists the exams available right now.
        $availablePanel = $this->panel($content, 'available');
        $this->assertStringContainsString('Open Maths Test', $availablePanel);
        $this->assertStringContainsString(route('student.exams.show', $available), $availablePanel);
        $this->assertStringNotContainsString('Next Week Maths Test', $availablePanel);

        // Each linked page opens and shows the right exams.
        $this->actingAs($student, 'student')->get(route('student.exams.available'))
            ->assertOk()->assertSee('Open Maths Test')->assertDontSee('Next Week Maths Test');
        $this->actingAs($student, 'student')->get(route('student.exams.upcoming'))
            ->assertOk()->assertSee('Next Week Maths Test')->assertDontSee('Open Maths Test');
        $this->actingAs($student, 'student')->get(route('student.results.index'))->assertOk();
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
