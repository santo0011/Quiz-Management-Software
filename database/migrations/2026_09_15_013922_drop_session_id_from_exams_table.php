<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Academic Session has been removed from the application entirely — exams
 * are no longer organized/filtered by session (see Exam::scopeEligibleForStudent()
 * and the removed AcademicSessionResolver), so this column is dead.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            $table->dropConstrainedForeignId('session_id');
        });
    }

    public function down(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            $table->foreignId('session_id')->nullable()->after('school_class_id')->constrained('academic_sessions')->nullOnDelete();
        });
    }
};
