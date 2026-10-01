<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Removes the Pass Marks / Pass-Fail system project-wide. Result pages now
 * show only Marks Obtained, Total Marks, and Percentage — all still
 * computed exactly as before in ExamAttemptService::submit(). Nothing else
 * about the marks/percentage/correct/wrong/unanswered calculation changes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            $table->dropColumn('passing_marks');
        });

        Schema::table('exam_attempts', function (Blueprint $table) {
            $table->dropColumn('is_passed');
        });
    }

    public function down(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            $table->unsignedInteger('passing_marks')->nullable();
        });

        Schema::table('exam_attempts', function (Blueprint $table) {
            $table->boolean('is_passed')->default(false);
        });
    }
};
