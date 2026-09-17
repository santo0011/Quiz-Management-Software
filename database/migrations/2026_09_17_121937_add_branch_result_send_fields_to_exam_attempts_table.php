<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Purely additive: nullable columns tracking the Branch Panel's manual
     * "Review & Send Result" action (PDF path, and when/by whom the result
     * email was sent). Never touches existing result calculation columns.
     */
    public function up(): void
    {
        Schema::table('exam_attempts', function (Blueprint $table) {
            $table->string('branch_result_pdf_path')->nullable()->after('zoho_result_synced_at');
            $table->timestamp('result_email_sent_at')->nullable()->after('branch_result_pdf_path');
            $table->foreignId('result_email_sent_by')->nullable()->after('result_email_sent_at')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('exam_attempts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('result_email_sent_by');
            $table->dropColumn(['branch_result_pdf_path', 'result_email_sent_at']);
        });
    }
};
