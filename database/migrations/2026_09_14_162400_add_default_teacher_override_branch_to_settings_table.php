<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Teacher Override can verify a Student through Zoho who has no local
 * Student record yet. Logging them in requires creating one (Laravel's
 * session auth needs a real Eloquent row to persist/reload), but branch_id
 * is a required column with nothing reliable in Zoho's response to derive
 * it from — so Super Admin configures one fixed branch here instead of the
 * app guessing.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->foreignId('default_teacher_override_branch_id')->nullable()->after('common_student_password')->constrained('branches')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('default_teacher_override_branch_id');
        });
    }
};
