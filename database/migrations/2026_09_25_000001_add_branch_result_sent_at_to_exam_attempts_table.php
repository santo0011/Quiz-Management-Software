<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * When the Branch's Review & Send Result actually went out. Kept
     * separate from zoho_result_synced_at, which is also set by the
     * automatic sync on exam submission, so a result is only treated as
     * "sent by the Branch" once the Branch itself sends it.
     */
    public function up(): void
    {
        Schema::table('exam_attempts', function (Blueprint $table) {
            $table->timestamp('branch_result_sent_at')->nullable()->after('branch_review_at');
        });
    }

    public function down(): void
    {
        Schema::table('exam_attempts', function (Blueprint $table) {
            $table->dropColumn('branch_result_sent_at');
        });
    }
};
