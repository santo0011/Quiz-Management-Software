<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Purely additive: nullable columns for the Branch Panel's Review &
     * Send Result feedback text, saved alongside the result so it remains
     * available whenever the result is viewed again. Never touches any
     * existing result calculation column.
     */
    public function up(): void
    {
        Schema::table('exam_attempts', function (Blueprint $table) {
            $table->text('branch_review')->nullable()->after('result_email_sent_by');
            $table->foreignId('branch_review_by')->nullable()->after('branch_review')->constrained('users')->nullOnDelete();
            $table->timestamp('branch_review_at')->nullable()->after('branch_review_by');
        });
    }

    public function down(): void
    {
        Schema::table('exam_attempts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('branch_review_by');
            $table->dropColumn(['branch_review', 'branch_review_at']);
        });
    }
};
