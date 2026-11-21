<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Answer options now use the rich option editor, which embeds pasted and
     * uploaded images as base64 — the same reason questions.question_text was
     * widened. Widening only; no existing value changes.
     */
    public function up(): void
    {
        Schema::table('question_options', function (Blueprint $table) {
            $table->longText('option_text')->change();
        });
    }

    public function down(): void
    {
        Schema::table('question_options', function (Blueprint $table) {
            $table->text('option_text')->change();
        });
    }
};
