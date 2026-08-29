<?php

use App\Models\Question;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Standalone questions (passage_group_id null) are back on CKEditor and
     * are now rendered as raw HTML, same as Summary questions — see
     * QuestionRequest/MultiQuestionRequest. Migration
     * 2026_08_28_000005_plain_text_standalone_question_text decoded these
     * rows back to literal plain text (e.g. a literal "<" in "if x < 5");
     * rendered raw now, a literal "<"/">"/"&" would be parsed as a stray
     * HTML tag/entity instead of displaying as the character it is. Escape
     * them back to entities once here so existing standalone questions keep
     * displaying correctly. (Harmless no-op for rows with no such characters;
     * new rows saved through the CKEditor field arrive already sanitized.)
     */
    public function up(): void
    {
        Question::query()
            ->whereNull('passage_group_id')
            ->whereNotNull('question_text')
            ->each(function (Question $question): void {
                $escaped = htmlspecialchars($question->question_text, ENT_QUOTES, 'UTF-8');

                if ($escaped !== $question->question_text) {
                    $question->updateQuietly(['question_text' => $escaped]);
                }
            });
    }

    public function down(): void
    {
        // Not reversible: cannot distinguish already-plain text from text
        // that happened to contain literal entity-like sequences.
    }
};
