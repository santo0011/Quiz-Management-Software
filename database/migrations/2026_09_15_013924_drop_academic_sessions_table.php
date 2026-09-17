<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Academic Session has been removed from the application entirely. Every
 * column that referenced this table (students.session_id, exams.session_id,
 * exam_attempts.session_id) was already dropped by earlier migrations, so
 * it's safe to drop the table itself.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('academic_sessions');
    }

    public function down(): void
    {
        Schema::create('academic_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->date('start_date');
            $table->date('end_date');
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }
};
