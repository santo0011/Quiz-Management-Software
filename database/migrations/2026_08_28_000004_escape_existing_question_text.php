<?php

use App\Models\Question;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * question_text is now rendered as raw HTML (it switched from the plain
     * text/LaTeX math-editor to the same rich-text CKEditor used for
     * Summaries). Every row saved before this switch is plain text that may
     * contain literal <, >, or & characters (e.g. "x < 5", "a & b" as typed
     * math) — left as-is, raw-HTML rendering would treat those as markup.
     * Escape them once here so existing questions keep displaying exactly
     * as they did before.
     */
    public function up(): void
    {
        Question::query()->whereNotNull('question_text')->each(function (Question $question): void {
            $question->updateQuietly([
                'question_text' => htmlspecialchars($question->question_text, ENT_QUOTES, 'UTF-8'),
            ]);
        });
    }

    public function down(): void
    {
        // Not reversible: cannot distinguish originally-escaped text from
        // text that legitimately contained HTML entity-like sequences.
    }
};
