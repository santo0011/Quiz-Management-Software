<?php

use App\Models\Question;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Standalone questions (passage_group_id is null) are back to the plain
     * math-editor and are rendered escaped as plain text — CKEditor is kept
     * only for questions added under a Summary/Passage. Any standalone
     * question saved while CKEditor briefly applied to it too would still
     * hold real HTML (e.g. "<p>What is 2+2?</p>"); rendered as escaped text
     * now, that would show the raw tags to students. Strip any markup back
     * to plain text once here so those questions keep displaying correctly.
     * (Harmless no-op for rows that were already plain text.)
     */
    public function up(): void
    {
        Question::query()
            ->whereNull('passage_group_id')
            ->whereNotNull('question_text')
            ->each(function (Question $question): void {
                $plain = html_entity_decode(strip_tags($question->question_text), ENT_QUOTES, 'UTF-8');

                if ($plain !== $question->question_text) {
                    $question->updateQuietly(['question_text' => $plain]);
                }
            });
    }

    public function down(): void
    {
        // Not reversible: the original HTML (if any) isn't recoverable.
    }
};
