<?php

namespace Tests\Feature;

use App\Models\AcademicSession;
use App\Models\Branch;
use App\Models\Exam;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ExamCreationValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_missing_grade_and_subject_show_friendly_messages_and_reopen_the_drawer(): void
    {
        [$admin, , , $session] = $this->makeFixtures();

        $response = $this->actingAs($admin)
            ->withSession(['admin_selected_academic_session_id' => $session->id])
            ->post(route('admin.exams.store'), [
                '_drawer' => 'addExamDrawer',
                'title' => 'Incomplete Exam',
                'starts_at' => now()->addDay()->format('Y-m-d H:i:s'),
                'ends_at' => now()->addDays(2)->format('Y-m-d H:i:s'),
                'duration_minutes' => 30,
                'maximum_attempts' => 1,
            ]);

        $response->assertSessionHasErrors([
            'school_class_id' => 'Please select a grade for this exam.',
            'subject_id' => 'Please select a subject for this exam.',
        ]);

        // The drawer must reopen on failure — proven by the "_drawer"
        // marker flashed back for the JS auto-reopen logic to pick up.
        $response->assertSessionHas('_old_input._drawer', 'addExamDrawer');

        $this->assertDatabaseMissing('exams', ['title' => 'Incomplete Exam']);
    }

    public function test_no_generic_technical_field_names_leak_into_validation_messages(): void
    {
        [$admin, , , $session] = $this->makeFixtures();

        $response = $this->actingAs($admin)
            ->withSession(['admin_selected_academic_session_id' => $session->id])
            ->post(route('admin.exams.store'), []);

        $errors = session('errors')->getBag('default')->getMessages();

        foreach ($errors as $field => $fieldMessages) {
            foreach ($fieldMessages as $message) {
                $this->assertStringNotContainsString('school class id', strtolower($message));
                $this->assertStringNotContainsString('subject id', strtolower($message));
                $this->assertStringNotContainsString('starts at', strtolower($message));
                $this->assertStringNotContainsString('ends at', strtolower($message));
                $this->assertStringNotContainsString('sqlstate', strtolower($message));
                $this->assertStringNotContainsString('exception', strtolower($message));
            }
        }
    }

    public function test_end_date_before_start_date_shows_a_clear_message(): void
    {
        [$admin, $class, $subject, $session] = $this->makeFixtures();

        $response = $this->actingAs($admin)
            ->withSession(['admin_selected_academic_session_id' => $session->id])
            ->post(route('admin.exams.store'), [
                'title' => 'Backwards Dates',
                'school_class_id' => $class->id,
                'subject_id' => $subject->id,
                'starts_at' => now()->addDays(2)->format('Y-m-d H:i:s'),
                'ends_at' => now()->addDay()->format('Y-m-d H:i:s'),
                'duration_minutes' => 30,
                'maximum_attempts' => 1,
            ]);

        $response->assertSessionHasErrors([
            'ends_at' => 'The end date and time must be on or after the start date and time.',
        ]);
    }

    public function test_valid_submission_creates_the_exam_and_does_not_reopen_the_drawer(): void
    {
        [$admin, $class, $subject, $session] = $this->makeFixtures();

        $response = $this->actingAs($admin)
            ->withSession(['admin_selected_academic_session_id' => $session->id])
            ->post(route('admin.exams.store'), [
                'title' => 'Valid Exam',
                'school_class_id' => $class->id,
                'subject_id' => $subject->id,
                'starts_at' => now()->addDay()->format('Y-m-d H:i:s'),
                'ends_at' => now()->addDays(2)->format('Y-m-d H:i:s'),
                'duration_minutes' => 30,
                'maximum_attempts' => 1,
            ]);

        $response->assertRedirect(route('admin.exams.index'));
        $response->assertSessionDoesntHaveErrors();
        $response->assertSessionHas('success', 'Exam created successfully.');
        $response->assertSessionMissing('_old_input');

        $this->assertDatabaseHas('exams', ['title' => 'Valid Exam']);
    }

    public function test_branch_exam_creation_also_shows_friendly_messages_and_reopens_the_drawer(): void
    {
        $branch = Branch::create(['name' => 'Branch '.uniqid(), 'email' => 'branch-'.uniqid().'@example.com', 'is_active' => true]);
        $branchUser = User::create([
            'name' => $branch->name,
            'email' => $branch->email,
            'role' => 'Branch',
            'branch_id' => $branch->id,
            'password' => Hash::make('secret123'),
        ]);
        $session = AcademicSession::create([
            'name' => 'Session '.uniqid(),
            'start_date' => now()->subMonth(),
            'end_date' => now()->addMonths(6),
            'is_active' => true,
        ]);

        $response = $this->actingAs($branchUser)
            ->withSession(['branch_selected_academic_session_id' => $session->id])
            ->post(route('branch.exams.store'), [
                '_drawer' => 'addExamDrawer',
                'title' => 'Incomplete Branch Exam',
                'starts_at' => now()->addDay()->format('Y-m-d H:i:s'),
                'ends_at' => now()->addDays(2)->format('Y-m-d H:i:s'),
                'duration_minutes' => 30,
                'maximum_attempts' => 1,
            ]);

        $response->assertSessionHasErrors([
            'school_class_id' => 'Please select a grade for this exam.',
            'subject_id' => 'Please select a subject for this exam.',
        ]);
        $response->assertSessionHas('_old_input._drawer', 'addExamDrawer');

        $this->assertDatabaseMissing('exams', ['title' => 'Incomplete Branch Exam']);
    }

    private function makeFixtures(): array
    {
        $admin = User::create([
            'name' => 'Super Admin',
            'email' => 'admin-'.uniqid().'@example.com',
            'role' => 'Super Admin',
            'password' => Hash::make('123456'),
        ]);

        $class = SchoolClass::create(['branch_id' => null, 'name' => 'Class 10']);
        $subject = Subject::create(['name' => 'Science']);

        $session = AcademicSession::create([
            'name' => 'Session '.uniqid(),
            'start_date' => now()->subMonth(),
            'end_date' => now()->addMonths(6),
            'is_active' => true,
        ]);

        return [$admin, $class, $subject, $session];
    }
}
