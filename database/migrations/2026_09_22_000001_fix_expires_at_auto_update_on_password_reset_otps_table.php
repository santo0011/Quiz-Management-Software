<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * `expires_at` was created via `$table->timestamp('expires_at')` with no
 * explicit default. MySQL/MariaDB's legacy rule for the first TIMESTAMP
 * column in a table with no explicit default silently attached
 * `DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP` to it — so any
 * later UPDATE to an OTP row (e.g. bumping `attempts` after a mistyped
 * code in LoginOtpService::verify()) rewrote `expires_at` to that moment,
 * making the code expire immediately even on the very next, correct
 * attempt. This affects every OTP flow that shares this table: Super
 * Admin/Branch/Guardian/Teacher login OTP and password reset OTP.
 *
 * Giving the column an explicit DEFAULT (without ON UPDATE) stops MySQL
 * from auto-attaching one. The app always sets `expires_at` explicitly on
 * insert, so the default's actual value is never relied upon.
 */
return new class extends Migration
{
    public function up(): void
    {
        // The implicit ON UPDATE CURRENT_TIMESTAMP quirk this fixes is
        // MySQL/MariaDB-specific; SQLite (used by the test suite) has no
        // such behavior and no MODIFY COLUMN syntax, so there's nothing to
        // fix there.
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        DB::statement('ALTER TABLE password_reset_otps MODIFY expires_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP');
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        DB::statement('ALTER TABLE password_reset_otps MODIFY expires_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');
    }
};
