<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->string('zoho_class_id')->nullable()->after('zoho_synced_at');
            $table->string('zoho_class_name')->nullable()->after('zoho_class_id');
            $table->string('zoho_grade')->nullable()->after('zoho_class_name');
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn(['zoho_class_id', 'zoho_class_name', 'zoho_grade']);
        });
    }
};
