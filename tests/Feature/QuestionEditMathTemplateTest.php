<?php

namespace Tests\Feature;

use App\Models\Exam;
use App\Models\Question;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Regression: the hidden "Math Equation" template on the question edit page
 * inherited the question's saved HTML (via @include scope), and on Save the
 * JS appended it to the question as literal text, so edited questions
 * displayed "<p>What is 7 + 5?</p>" with visible tags.
 */
class QuestionEditMathTemplateTest extends TestCase
{
    use RefreshDatabase;

    public function test_math_mode_template_on_edit_page_is_not_prefilled_with_the_question_text(): void
    {
        $admin = User::create([
            'name' => 'Super Admin',
            'email' => 'admin@example.com',
            'role' => 'Super Admin',
            'password' => Hash::make('123456'),
        ]);
        $class = SchoolClass::create(['branch_id' => null, 'name' => 'Grade 3']);
        $subject = Subject::create(['name' => 'Mathematics']);
        $exam = Exam::create([
            'branch_id' => null,
            'school_class_id' => $class->id,
            'subject_id' => $subject->id,
            'title' => 'Demo',
            'total_marks' => 0,
            'duration_minutes' => 20,
            'maximum_attempts' => 1,
            'status' => Exam::STATUS_DRAFT,
        ]);
        $question = Question::create([
            'exam_id' => $exam->id,
            'question_text' => '<p>What is 7 + 5?</p>',
            'question_type' => 'mcq',
            'marks' => 1,
        ]);

        $html = $this->actingAs($admin)->get(route('admin.questions.edit', $question))->assertOk()->getContent();

        $this->assertSame(1, preg_match('~<template data-math-mode-template>(.*?)</template>~s', $html, $match));
        $this->assertSame(1, preg_match('~<textarea[^>]*>(.*?)</textarea>~s', $match[1], $textarea));
        $this->assertSame('', trim($textarea[1]), 'Math template textarea must start empty.');
        $this->assertStringNotContainsString('name="question_text"', $match[1]);

        // The real question field still carries the saved text.
        $this->assertMatchesRegularExpression('~<textarea[^>]*name="question_text"[^>]*>\s*&lt;p&gt;What is 7 \+ 5\?&lt;/p&gt;\s*</textarea>~', $html);
    }
}
