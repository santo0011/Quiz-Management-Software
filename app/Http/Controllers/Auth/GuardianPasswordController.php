<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\GuardianLoginOtpMail;
use App\Models\Guardian;
use App\Models\Student;
use App\Services\LoginOtpService;
use App\Services\SingleSessionService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

/**
 * First-time Guardian login: enter the email used as a Student's Guardian
 * Email -> verify it's linked to at least one Student -> OTP -> set a
 * password. Mirrors StudentPasswordController's flow/shape exactly, so the
 * unified login page's existing two-step JS can drive both.
 */
class GuardianPasswordController extends Controller
{
    public function checkEmail(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
        ], [
            'email.required' => 'Guardian email is required.',
            'email.email' => 'Please enter a valid guardian email address.',
        ]);

        if (! Student::where('guardian_email', $validated['email'])->exists()) {
            throw ValidationException::withMessages([
                'email' => 'No student is linked to this guardian email address.',
            ]);
        }

        $guardian = Guardian::where('email', $validated['email'])->first();

        return response()->json([
            'status' => $guardian?->password ? 'password_required' : 'password_setup_required',
            'message' => $guardian?->password
                ? 'Guardian account verified. Please enter your password.'
                : 'Guardian account verified. Create a password using a secure OTP.',
        ]);
    }

    public function sendOtp(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
        ], [
            'email.required' => 'Guardian email is required.',
            'email.email' => 'Please enter a valid guardian email address.',
        ]);

        if (! Student::where('guardian_email', $validated['email'])->exists()) {
            throw ValidationException::withMessages([
                'email' => 'No student is linked to this guardian email address.',
            ]);
        }

        $email = $validated['email'];
        $otp = (string) random_int(100000, 999999);

        DB::table('password_reset_otps')
            ->where('email', $email)
            ->where('user_type', 'guardian')
            ->whereNull('used_at')
            ->update([
                'used_at' => now(),
                'updated_at' => now(),
            ]);

        DB::table('password_reset_otps')->insert([
            'email' => $email,
            'user_type' => 'guardian',
            'otp_hash' => Hash::make($otp),
            'expires_at' => now()->addMinutes(LoginOtpService::OTP_EXPIRY_MINUTES),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        try {
            Mail::to($email)->send(new GuardianLoginOtpMail($otp));
        } catch (\Throwable $e) {
            Log::error('Guardian login OTP email failed to send.', [
                'email' => $email,
                'exception' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'OTP could not be sent. Please try again later.',
            ], 500);
        }

        $request->session()->put('guardian_password_email', $email);
        $request->session()->forget('guardian_password_verified_otp_id');

        return response()->json([
            'message' => 'OTP has been sent to your registered email.',
        ]);
    }

    public function verifyOtp(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'otp' => ['required', 'digits:6'],
        ], [
            'otp.required' => 'OTP is required.',
            'otp.digits' => 'OTP must be 6 digits.',
        ]);

        $record = DB::table('password_reset_otps')
            ->where('email', $validated['email'])
            ->where('user_type', 'guardian')
            ->whereNull('used_at')
            ->latest()
            ->first();

        if (! $record || Carbon::parse($record->expires_at)->isPast() || ! Hash::check($validated['otp'], $record->otp_hash)) {
            throw ValidationException::withMessages([
                'otp' => 'Invalid or expired OTP. Please request a new code.',
            ]);
        }

        session([
            'guardian_password_email' => $validated['email'],
            'guardian_password_verified_otp_id' => $record->id,
        ]);

        return response()->json([
            'message' => 'OTP verified. Please create your password.',
        ]);
    }

    public function createPassword(Request $request): JsonResponse
    {
        abort_unless(session('guardian_password_email') && session('guardian_password_verified_otp_id'), 403);

        $validated = $request->validate([
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ], [
            'password.required' => 'Please enter a new password.',
            'password.min' => 'Password must be at least 6 characters.',
            'password.confirmed' => 'Password confirmation does not match.',
        ]);

        $email = session('guardian_password_email');
        $otpId = session('guardian_password_verified_otp_id');

        $guardian = Guardian::firstOrCreate(['email' => $email]);
        $guardian->update([
            'password' => Hash::make($validated['password']),
        ]);

        DB::table('password_reset_otps')
            ->where('id', $otpId)
            ->where('email', $email)
            ->whereNull('used_at')
            ->update([
                'used_at' => now(),
                'updated_at' => now(),
            ]);

        session()->forget(['guardian_password_email', 'guardian_password_verified_otp_id']);

        Auth::guard('guardian')->login($guardian, true);
        $request->session()->regenerate();
        SingleSessionService::establish($guardian, 'guardian');

        return response()->json([
            'message' => 'Password created successfully. Redirecting to your dashboard.',
            'redirect' => route('guardian.dashboard'),
        ]);
    }
}
