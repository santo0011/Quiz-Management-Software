<?php

namespace Tests\Feature;

use App\Models\Exam;
use App\Models\PassageGroup;
use App\Models\SchoolClass;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SummaryToPassageTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_passage_is_titled_passage_and_page_uses_passage_wording(): void
    {
        [$admin, $exam] = $this->fixture();

        $this->actingAs($admin)->post(route('admin.passage-groups.store', $exam), ['content' => '<p>Read this.</p>'])
            ->assertSessionHas('success', 'Passage added. Now add its questions below.');

        $this->assertSame('Passage 1', PassageGroup::where('exam_id', $exam->id)->value('title'));

        $this->actingAs($admin)->get(route('admin.questions.create', $exam))
            ->assertOk()
            ->assertSee('Add Passage')
            ->assertSee('Passage Content')
            ->assertSee('Save Passage')
            ->assertDontSee('Add Summary')
            ->assertDontSee('Passage/Summary');
    }

    public function test_migration_renames_only_auto_titles_and_keeps_content(): void
    {
        [, $exam] = $this->fixture();
        $auto = PassageGroup::create(['exam_id' => $exam->id, 'title' => 'Summary 3', 'content' => '<p>Summary of the story.</p>', 'position' => 1]);
        $custom = PassageGroup::create(['exam_id' => $exam->id, 'title' => 'Summary of Chapter 2', 'content' => '<p>Keep me.</p>', 'position' => 2]);

        $migration = require database_path('migrations/2026_11_22_000001_rename_summary_titles_to_passage.php');
        $migration->up();

        $this->assertSame('Passage 3', $auto->fresh()->title);
        $this->assertSame('<p>Summary of the story.</p>', $auto->fresh()->content);
        $this->assertSame('Summary of Chapter 2', $custom->fresh()->title);

        $migration->down();
        $this->assertSame('Summary 3', $auto->fresh()->title);
    }

    private function fixture(): array
    {
        $admin = User::create(['name' => 'Super Admin', 'email' => 'admin@example.com', 'role' => 'Super Admin', 'password' => Hash::make('123456')]);
        $class = SchoolClass::create(['branch_id' => null, 'name' => 'Class 10']);
        $exam = Exam::create([
            'branch_id' => null, 'school_class_id' => $class->id, 'title' => 'Passage Exam',
            'total_marks' => 0, 'duration_minutes' => 30, 'maximum_attempts' => 1, 'status' => Exam::STATUS_DRAFT,
        ]);

        return [$admin, $exam];
    }
}
