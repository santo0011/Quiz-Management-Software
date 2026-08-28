<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * question_text now comes from the same CKEditor used for Summaries,
     * which can embed base64 images — matches passage_groups.content
     * (longText) so a question with an inline image doesn't risk hitting
     * the ~64KB TEXT limit.
     */
    public function up(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->longText('question_text')->change();
        });
    }

    public function down(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->text('question_text')->change();
        });
    }
};
