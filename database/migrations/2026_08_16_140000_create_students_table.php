<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->string('student_name');
            $table->string('guardian_name');
            $table->string('class');
            $table->string('phone_number');
            $table->string('email')->unique();
            $table->string('password')->nullable();
            $table->string('login_code_hash')->nullable();
            $table->string('zoho_student_id')->nullable()->unique();
            $table->json('zoho_payload')->nullable();
            $table->timestamp('zoho_synced_at')->nullable();
            $table->timestamps();

            $table->index(['branch_id', 'class']);
            $table->index(['branch_id', 'student_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
