<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Students are now identified/verified through the Zoho API rather than
 * being manually created per Academic Session, so the Session concept no
 * longer applies to them at all (exam eligibility no longer matches on it
 * either — see Exam::scopeEligibleForStudent()).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropConstrainedForeignId('session_id');
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->foreignId('session_id')->nullable()->after('class_id')->constrained('academic_sessions')->nullOnDelete();
        });
    }
};
