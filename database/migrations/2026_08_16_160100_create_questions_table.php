<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_id')->constrained()->cascadeOnDelete();
            $table->text('question_text');
            $table->string('question_type')->default('mcq');
            $table->decimal('marks', 8, 2);
            $table->text('explanation')->nullable();
            $table->timestamps();

            $table->index('exam_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('questions');
    }
};
