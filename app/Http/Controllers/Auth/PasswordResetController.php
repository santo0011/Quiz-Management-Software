<?php

namespace App\Http\Controllers\Auth;

use App\Mail\SuperAdminOtpMail;
use App\Http\Controllers\Controller;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class PasswordResetController extends Controller
{
    public function request(): View
    {
        return view('auth.passwords.email');
    }

    public function sendOtp(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
        ], [
            'email.required' => 'Email is required.',
            'email.email' => 'Please enter a valid email address.',
        ]);

        $user = User::where('email', $validated['email'])
            ->where('role', 'Super Admin')
            ->first();

        if ($user) {
            $otp = (string) random_int(100000, 999999);

            DB::table('password_reset_otps')->where('email', $user->email)->whereNull('used_at')->update([
                'used_at' => now(),
            ]);

            DB::table('password_reset_otps')->insert([
                'email' => $user->email,
                'otp_hash' => Hash::make($otp),
                'expires_at' => now()->addMinutes(10),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            try {
                Mail::to($user->email)->send(new SuperAdminOtpMail($otp));
                $emailSent = true;
            } catch (\Throwable $e) {
                $emailSent = false;
            }
        } else {
            $emailSent = true; // Pretend success to avoid user enumeration
        }

        $request->session()->put('password_reset_email', $validated['email']);

        if (! $emailSent) {
            return back()
                ->with('error', 'Password reset instructions could not be sent. Please try again later.')
                ->withInput($request->only('email'));
        }

        return redirect()
            ->route('password.otp')
            ->with('success', 'Password reset instructions have been sent to your email.');
    }

    public function otp(): View
    {
        return view('auth.passwords.otp');
    }

    public function verifyOtp(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'otp' => ['required', 'digits:6'],
        ], [
            'otp.required' => 'Reset code is required.',
            'otp.digits' => 'Reset code must be 6 digits.',
        ]);

        $record = DB::table('password_reset_otps')
            ->where('email', $validated['email'])
            ->whereNull('used_at')
            ->latest()
            ->first();

        if (! $record || Carbon::parse($record->expires_at)->isPast() || ! Hash::check($validated['otp'], $record->otp_hash)) {
            return back()
                ->with('reset_error', 'Invalid or expired reset code. Please request a new code.')
                ->withInput(['email' => $validated['email']]);
        }

        session([
            'password_reset_email' => $validated['email'],
            'password_reset_verified_otp_id' => $record->id,
        ]);

        return redirect()->route('password.reset.form')->with('success', 'Reset code verified. Please set a new password.');
    }

    public function resetForm(): View
    {
        abort_unless(session('password_reset_verified_otp_id'), 403);

        return view('auth.passwords.reset');
    }

    public function reset(Request $request): RedirectResponse
    {
        abort_unless(session('password_reset_verified_otp_id'), 403);

        $validated = $request->validate([
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ], [
            'password.required' => 'New password is required.',
            'password.min' => 'New password must be at least 6 characters.',
            'password.confirmed' => 'Password confirmation does not match.',
        ]);

        $email = session('password_reset_email');
        $otpId = session('password_reset_verified_otp_id');

        $user = User::where('email', $email)->where('role', 'Super Admin')->firstOrFail();
        $user->update(['password' => Hash::make($validated['password'])]);

        DB::table('password_reset_otps')->where('id', $otpId)->update([
            'used_at' => now(),
            'updated_at' => now(),
        ]);

        session()->forget(['password_reset_email', 'password_reset_verified_otp_id']);

        return redirect()->route('login')->with('login_success', 'Password reset successful. Please login with your new password.');
    }
}
