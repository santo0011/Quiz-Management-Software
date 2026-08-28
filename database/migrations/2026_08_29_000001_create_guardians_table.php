<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A Guardian account is keyed purely by email — it is matched against
     * students.guardian_email at login/dashboard time (one Guardian row can
     * be linked to many Students who share that email; there is no FK, the
     * match is by value). The row itself is only created once the Guardian
     * completes first-time OTP verification and sets a password.
     */
    public function up(): void
    {
        Schema::create('guardians', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
            $table->string('password')->nullable();
            $table->string('current_session_id')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guardians');
    }
};
