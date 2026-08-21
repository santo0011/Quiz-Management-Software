<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->foreignId('passage_group_id')->nullable()->after('exam_id')
                ->constrained('passage_groups')->cascadeOnDelete();
            $table->unsignedInteger('position')->default(0)->after('question_category_id');

            $table->index(['exam_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->dropIndex(['exam_id', 'position']);
            $table->dropConstrainedForeignId('passage_group_id');
            $table->dropColumn('position');
        });
    }
};
