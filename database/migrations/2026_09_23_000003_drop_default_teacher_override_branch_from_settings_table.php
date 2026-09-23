<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The Super-Admin-configured "default Branch for new Students" setting is
 * removed: Teacher Override's just-in-time Student provisioning now maps a
 * Branch from the Student's own Zoho Enrolment Location (first word,
 * case-insensitive match against Branch name) instead of one fixed Branch
 * chosen in Settings.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('default_teacher_override_branch_id');
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->foreignId('default_teacher_override_branch_id')->nullable()->after('common_student_password')->constrained('branches')->nullOnDelete();
        });
    }
};
