<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exam_attempts', function (Blueprint $table) {
            $table->string('result_pdf_path')->nullable()->after('teacher_remark_at');
            $table->string('result_pdf_token')->nullable()->unique()->after('result_pdf_path');
            $table->timestamp('zoho_result_synced_at')->nullable()->after('result_pdf_token');
        });
    }

    public function down(): void
    {
        Schema::table('exam_attempts', function (Blueprint $table) {
            $table->dropColumn(['result_pdf_path', 'result_pdf_token', 'zoho_result_synced_at']);
        });
    }
};
