<?php

namespace App\Services;

use App\Mail\BranchLoginOtpMail;
use App\Mail\StudentLoginOtpMail;
use App\Mail\SuperAdminLoginOtpMail;
use App\Models\Branch;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

/**
 * Generates, emails, and verifies the 6-digit login OTP required as a
 * second factor for Super Admin/Branch/Student login, reusing the same
 * `password_reset_otps` table as the "Forgot Password" flow (scoped by
 * `purpose = 'login'` so the two flows never collide).
 */
class LoginOtpService
{
    public const OTP_EXPIRY_MINUTES = 5;

    public const MAX_ATTEMPTS = 5;

    public const RESEND_COOLDOWN_SECONDS = 60;

    private const PURPOSE = 'login';

    /**
     * Invalidate any pending code and issue + email a fresh one.
     */
    public static function send(string $loginType, string $email): bool
    {
        DB::table('password_reset_otps')
            ->where('email', $email)
            ->where('user_type', $loginType)
            ->where('purpose', self::PURPOSE)
            ->whereNull('used_at')
            ->update([
                'used_at' => now(),
                'updated_at' => now(),
            ]);

        $otp = (string) random_int(100000, 999999);

        DB::table('password_reset_otps')->insert([
            'email' => $email,
            'user_type' => $loginType,
            'purpose' => self::PURPOSE,
            'otp_hash' => Hash::make($otp),
            'attempts' => 0,
            'expires_at' => now()->addMinutes(self::OTP_EXPIRY_MINUTES),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        try {
            match ($loginType) {
                'branch' => Mail::to($email)->send(new BranchLoginOtpMail(
                    Branch::where('email', $email)->first() ?? new Branch(['name' => 'Branch', 'email' => $email]),
                    $otp
                )),
                'student' => Mail::to($email)->send(new StudentLoginOtpMail($otp, Student::where('email', $email)->first())),
                default => Mail::to($email)->send(new SuperAdminLoginOtpMail($otp)),
            };

            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * @return array{ok: bool, reason: ?string, attemptsRemaining: ?int}
     */
    public static function verify(string $loginType, string $email, string $otp): array
    {
        $record = DB::table('password_reset_otps')
            ->where('email', $email)
            ->where('user_type', $loginType)
            ->where('purpose', self::PURPOSE)
            ->whereNull('used_at')
            ->latest()
            ->first();

        if (! $record) {
            return ['ok' => false, 'reason' => 'expired', 'attemptsRemaining' => 0];
        }

        if (Carbon::parse($record->expires_at)->isPast()) {
            DB::table('password_reset_otps')->where('id', $record->id)->update([
                'used_at' => now(),
                'updated_at' => now(),
            ]);

            return ['ok' => false, 'reason' => 'expired', 'attemptsRemaining' => 0];
        }

        if (! Hash::check($otp, $record->otp_hash)) {
            $attempts = $record->attempts + 1;

            if ($attempts >= self::MAX_ATTEMPTS) {
                DB::table('password_reset_otps')->where('id', $record->id)->update([
                    'attempts' => $attempts,
                    'used_at' => now(),
                    'updated_at' => now(),
                ]);

                return ['ok' => false, 'reason' => 'max_attempts', 'attemptsRemaining' => 0];
            }

            DB::table('password_reset_otps')->where('id', $record->id)->update([
                'attempts' => $attempts,
                'updated_at' => now(),
            ]);

            return ['ok' => false, 'reason' => 'invalid', 'attemptsRemaining' => self::MAX_ATTEMPTS - $attempts];
        }

        DB::table('password_reset_otps')->where('id', $record->id)->update([
            'used_at' => now(),
            'updated_at' => now(),
        ]);

        return ['ok' => true, 'reason' => null, 'attemptsRemaining' => null];
    }

    /**
     * Seconds remaining before a new code may be requested, based on the
     * most recently issued code for this account (used or not).
     */
    public static function secondsUntilResendAllowed(string $loginType, string $email): int
    {
        $record = DB::table('password_reset_otps')
            ->where('email', $email)
            ->where('user_type', $loginType)
            ->where('purpose', self::PURPOSE)
            ->latest()
            ->first();

        if (! $record) {
            return 0;
        }

        $elapsed = now()->diffInSeconds(Carbon::parse($record->created_at));

        return max(0, self::RESEND_COOLDOWN_SECONDS - $elapsed);
    }
}
