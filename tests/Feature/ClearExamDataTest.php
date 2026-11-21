<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\Question;
use App\Models\QuestionCategory;
use App\Models\QuestionOption;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ClearExamDataTest extends TestCase
{
    use RefreshDatabase;

    private function superAdmin(int $id): User
    {
        return User::forceCreate([
            'id' => $id,
            'name' => 'Super Admin '.$id,
            'email' => "admin{$id}@example.com",
            'role' => 'Super Admin',
            'password' => Hash::make('secret123'),
        ]);
    }

    private function seedExamData(): array
    {
        $branch = Branch::create(['name' => 'Main Branch', 'email' => 'main@example.com']);
        $class = SchoolClass::create(['branch_id' => $branch->id, 'name' => 'Class 10']);
        $subject = Subject::firstOrCreate(['name' => 'Science']);
        $category = QuestionCategory::create(['branch_id' => null, 'name' => 'General']);

        $student = Student::create([
            'branch_id' => $branch->id,
            'class_id' => $class->id,
            'student_name' => 'Test Student',
            'guardian_name' => 'Test Guardian',
            'class' => $class->name,
            'phone_number' => '9876543210',
            'zoho_student_id' => 'NL-1',
            'email' => 'student@example.com',
            'is_active' => true,
        ]);

        $exam = Exam::create([
            'branch_id' => $branch->id,
            'school_class_id' => $class->id,
            'subject_id' => $subject->id,
            'title' => 'Algebra Basics',
            'total_marks' => 10,
            'duration_minutes' => 30,
            'starts_at' => now()->subMinute(),
            'ends_at' => now()->addHour(),
            'maximum_attempts' => 1,
            'status' => Exam::STATUS_PUBLISHED,
        ]);

        $question = Question::create([
            'exam_id' => $exam->id,
            'question_text' => '2 + 2 = ?',
            'question_type' => 'mcq',
            'marks' => 10,
        ]);
        QuestionOption::create(['question_id' => $question->id, 'option_text' => '4', 'is_correct' => true, 'position' => 0]);

        ExamAttempt::create([
            'exam_id' => $exam->id,
            'student_id' => $student->id,
            'branch_id' => $branch->id,
            'school_class_id' => $class->id,
            'attempt_number' => 1,
            'started_at' => now()->subMinutes(10),
            'expires_at' => now()->addMinutes(20),
            'submitted_at' => now(),
            'obtained_marks' => 10,
            'percentage' => 100,
            'correct_count' => 1,
            'wrong_count' => 0,
            'unanswered_count' => 0,
            'status' => 'submitted',
        ]);

        return [$branch, $student, $class, $subject, $category];
    }

    public function test_super_admin_with_id_9_sees_the_option_and_can_clear_exam_data_only(): void
    {
        [$branch, $student, $class, $subject] = $this->seedExamData();
        $admin = $this->superAdmin(9);

        $this->actingAs($admin)->get(route('admin.settings.edit'))
            ->assertOk()
            ->assertSee('Clear Exam Data');

        $this->actingAs($admin)->delete(route('admin.settings.exam-data.clear'), [
            'clear_password' => 'secret123',
            'clear_confirmation' => 'CLEAR',
        ])->assertRedirect(route('admin.settings.edit'))
            ->assertSessionHas('success');

        foreach (['exams', 'questions', 'question_options', 'exam_attempts', 'exam_answers', 'passage_groups', 'question_categories'] as $table) {
            $this->assertDatabaseCount($table, 0);
        }

        $this->assertDatabaseHas('branches', ['id' => $branch->id]);
        $this->assertDatabaseHas('students', ['id' => $student->id]);
        $this->assertDatabaseHas('school_classes', ['id' => $class->id]);
        $this->assertDatabaseHas('subjects', ['id' => $subject->id]);
        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    public function test_other_super_admins_cannot_see_or_use_the_option(): void
    {
        $this->seedExamData();
        $admin = $this->superAdmin(5);

        $this->actingAs($admin)->get(route('admin.settings.edit'))
            ->assertOk()
            ->assertDontSee('Clear Exam Data');

        $this->actingAs($admin)->delete(route('admin.settings.exam-data.clear'), [
            'clear_password' => 'secret123',
            'clear_confirmation' => 'CLEAR',
        ])->assertForbidden();

        $this->assertDatabaseCount('exams', 1);
        $this->assertDatabaseCount('question_categories', 1);
    }

    public function test_wrong_password_or_missing_confirmation_does_not_clear_anything(): void
    {
        $this->seedExamData();
        $admin = $this->superAdmin(9);

        $this->actingAs($admin)->delete(route('admin.settings.exam-data.clear'), [
            'clear_password' => 'wrong-password',
            'clear_confirmation' => 'CLEAR',
        ])->assertSessionHasErrorsIn('clearExamData', 'clear_password');

        $this->actingAs($admin)->delete(route('admin.settings.exam-data.clear'), [
            'clear_password' => 'secret123',
            'clear_confirmation' => 'clear',
        ])->assertSessionHasErrorsIn('clearExamData', 'clear_confirmation');

        $this->assertDatabaseCount('exams', 1);
        $this->assertDatabaseCount('exam_attempts', 1);
    }
}
