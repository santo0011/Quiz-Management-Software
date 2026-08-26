<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\User;
use App\Services\LoginOtpService;
use App\Services\SingleSessionService;
use App\Support\RoleRedirector;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LoginOtpController extends Controller
{
    private const SESSION_KEY = 'pending_login';

    private const EXPIRED_MESSAGE = 'Your login session has expired. Please log in again.';

    public function show(Request $request): View|RedirectResponse
    {
        $pending = $request->session()->get(self::SESSION_KEY);

        if (! $pending) {
            return redirect()->route('login')->with('login_error', self::EXPIRED_MESSAGE);
        }

        $typeLabel = match ($pending['type']) {
            'branch' => 'Branch',
            'student' => 'Student',
            default => 'Super Admin',
        };

        return view('auth.login-otp', [
            'typeLabel' => $typeLabel,
            'maskedEmail' => $this->mask($pending['email']),
            'cooldown' => LoginOtpService::secondsUntilResendAllowed($pending['type'], $pending['email']),
            'expiresInSeconds' => LoginOtpService::OTP_EXPIRY_MINUTES * 60,
        ]);
    }

    public function verify(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'otp' => ['required', 'digits:6'],
        ], [
            'otp.required' => 'Verification code is required.',
            'otp.digits' => 'Verification code must be 6 digits.',
        ]);

        $pending = $request->session()->get(self::SESSION_KEY);

        if (! $pending) {
            return redirect()->route('login')->with('login_error', self::EXPIRED_MESSAGE);
        }

        $result = LoginOtpService::verify($pending['type'], $pending['email'], $validated['otp']);

        if (! $result['ok']) {
            if ($result['reason'] === 'max_attempts') {
                $request->session()->forget(self::SESSION_KEY);

                return redirect()->route('login')->with('login_error', 'Too many incorrect attempts. Please log in again.');
            }

            $message = $result['reason'] === 'expired'
                ? 'This code has expired. Please request a new one.'
                : "Incorrect code. {$result['attemptsRemaining']} attempt(s) remaining.";

            return back()->with('otp_error', $message);
        }

        $loginType = $pending['type'];
        $email = $pending['email'];
        $request->session()->forget(self::SESSION_KEY);

        if ($loginType === 'student') {
            $student = Student::where('email', $email)->firstOrFail();

            Auth::guard('student')->login($student, true);
            $request->session()->regenerate();
            SingleSessionService::establish($student, 'student');

            return redirect()
                ->intended(RoleRedirector::dashboardUrl($student))
                ->with('success', 'Login successful. Welcome back!');
        }

        $user = User::where('email', $email)->first();

        Auth::guard('web')->login($user, true);
        $request->session()->regenerate();

        if ($user->role === 'Branch') {
            SingleSessionService::establish($user, 'web');
        }

        return redirect()
            ->intended(RoleRedirector::dashboardUrl($user))
            ->with('success', 'Login successful. Welcome back!');
    }

    public function resend(Request $request): RedirectResponse
    {
        $pending = $request->session()->get(self::SESSION_KEY);

        if (! $pending) {
            return redirect()->route('login')->with('login_error', self::EXPIRED_MESSAGE);
        }

        $wait = LoginOtpService::secondsUntilResendAllowed($pending['type'], $pending['email']);

        if ($wait > 0) {
            return back()->with('otp_error', "Please wait {$wait} seconds before requesting a new code.");
        }

        if (! LoginOtpService::send($pending['type'], $pending['email'])) {
            return back()->with('otp_error', 'We could not send a new code. Please try again shortly.');
        }

        return back()->with('otp_success', 'A new verification code has been sent to your email.');
    }

    private function mask(string $email): string
    {
        [$name, $domain] = array_pad(explode('@', $email, 2), 2, '');

        $visible = min(2, strlen($name));
        $masked = substr($name, 0, $visible).str_repeat('*', max(1, strlen($name) - $visible));

        return $masked.'@'.$domain;
    }
}
