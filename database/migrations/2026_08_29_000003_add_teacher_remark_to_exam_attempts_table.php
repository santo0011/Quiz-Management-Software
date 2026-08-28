<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Purely additive: nullable Teacher-remark columns on the existing
     * `exam_attempts` table. Never touches existing rows/result data.
     */
    public function up(): void
    {
        Schema::table('exam_attempts', function (Blueprint $table) {
            $table->text('teacher_remark')->nullable()->after('is_passed');
            $table->foreignId('teacher_remark_by')->nullable()->after('teacher_remark')->constrained('teachers')->nullOnDelete();
            $table->timestamp('teacher_remark_at')->nullable()->after('teacher_remark_by');
        });
    }

    public function down(): void
    {
        Schema::table('exam_attempts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('teacher_remark_by');
            $table->dropColumn(['teacher_remark', 'teacher_remark_at']);
        });
    }
};
