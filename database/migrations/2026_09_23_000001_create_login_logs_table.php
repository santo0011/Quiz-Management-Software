<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Persistent, append-only audit log of every login attempt (Super Admin,
 * Branch, Teacher, Student, Guardian) — success and failure. Key display
 * fields (name, branch name) are snapshotted as plain strings rather than
 * only resolved via FK/join, so the log stays accurate even if the source
 * account or branch is later renamed or deleted. Never stores passwords,
 * OTP codes, or the Teacher Override common password — only a short,
 * human-readable failure reason.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('login_logs', function (Blueprint $table) {
            $table->id();
            $table->string('role');
            $table->string('name')->nullable();
            $table->string('identifier')->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->string('branch_name')->nullable();
            $table->string('login_method');
            $table->string('status');
            $table->string('failure_reason')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('role');
            $table->index('status');
            $table->index('login_method');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('login_logs');
    }
};
