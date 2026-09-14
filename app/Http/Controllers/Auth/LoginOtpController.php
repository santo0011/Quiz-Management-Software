<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Guardian;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use App\Services\AcademicSessionResolver;
use App\Services\LoginOtpService;
use App\Services\SingleSessionService;
use App\Services\ZohoStudentService;
use App\Support\RoleRedirector;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LoginOtpController extends Controller
{
    private const SESSION_KEY = 'pending_login';

    private const EXPIRED_MESSAGE = 'Your login session has expired. Please log in again.';

    /** Local retry cap for wrong-code guesses against Zoho, mirroring LoginOtpService::MAX_ATTEMPTS. */
    private const STUDENT_MAX_ATTEMPTS = 5;

    /** Local resend cooldown for the student flow (Zoho itself has no cooldown concept). */
    private const STUDENT_RESEND_COOLDOWN_SECONDS = 60;

    public function show(Request $request): View|RedirectResponse
    {
        $pending = $request->session()->get(self::SESSION_KEY);

        if (! $pending) {
            return redirect()->route('login')->with('login_error', self::EXPIRED_MESSAGE);
        }

        $typeLabel = match ($pending['type']) {
            'branch' => 'Branch',
            'student' => 'Student',
            'guardian' => 'Guardian',
            'teacher' => 'Teacher',
            default => 'Super Admin',
        };

        if ($pending['type'] === 'student') {
            return view('auth.login-otp', [
                'typeLabel' => $typeLabel,
                'maskedEmail' => $pending['parent_email'] ? $this->mask($pending['parent_email']) : $this->maskId($pending['nrich_student_id']),
                'cooldown' => $this->studentResendCooldown($pending),
                'expiresInSeconds' => (int) $pending['otp_validity_minutes'] * 60,
            ]);
        }

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

        if ($pending['type'] === 'student') {
            return $this->verifyStudentOtp($request, $pending, $validated['otp']);
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

        if ($loginType === 'guardian') {
            $guardian = Guardian::where('email', $email)->firstOrFail();

            Auth::guard('guardian')->login($guardian, true);
            $request->session()->regenerate();
            SingleSessionService::establish($guardian, 'guardian');

            return redirect()
                ->intended(RoleRedirector::dashboardUrl($guardian))
                ->with('success', 'Login successful. Welcome back!');
        }

        if ($loginType === 'teacher') {
            $teacher = Teacher::where('email', $email)->firstOrFail();

            Auth::guard('teacher')->login($teacher, true);
            $request->session()->regenerate();
            SingleSessionService::establish($teacher, 'teacher');
            AcademicSessionResolver::autoSelectOnLogin($request, 'Teacher');

            return redirect()
                ->intended(RoleRedirector::dashboardUrl($teacher))
                ->with('success', 'Login successful. Welcome back!');
        }

        $user = User::where('email', $email)->first();

        Auth::guard('web')->login($user, $pending['remember'] ?? false);
        $request->session()->regenerate();

        if ($user->role === 'Branch') {
            SingleSessionService::establish($user, 'web');
        }

        AcademicSessionResolver::autoSelectOnLogin($request, $user->role);

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

        if ($pending['type'] === 'student') {
            return $this->resendStudentOtp($request, $pending);
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

    /**
     * Zoho owns OTP generation/delivery for students — this just re-triggers
     * Zoho's send_otp and refreshes the local cooldown/expiry bookkeeping
     * kept in the session (there is no local OTP row to touch).
     */
    private function verifyStudentOtp(Request $request, array $pending, string $otp): RedirectResponse
    {
        $attempts = (int) ($pending['otp_attempts'] ?? 0);

        if ($attempts >= self::STUDENT_MAX_ATTEMPTS) {
            $request->session()->forget(self::SESSION_KEY);

            return redirect()->route('login')->with('login_error', 'Too many incorrect attempts. Please log in again.');
        }

        $zohoStudentService = app(ZohoStudentService::class);
        $result = $zohoStudentService->verifyOtp($pending['nrich_student_id'], $otp);

        if (! $result['ok']) {
            $pending['otp_attempts'] = $attempts + 1;
            $request->session()->put(self::SESSION_KEY, $pending);

            return back()->with('otp_error', $result['message']);
        }

        // Zoho has now verified both the ID and the OTP. This is the actual
        // point at which a local Student record is required — resolve it
        // here (by NRICH ID, not a student_id stashed earlier) rather than
        // at the initial identify step, so an ID Zoho recognizes but this
        // app has never seen still reaches this point instead of being
        // rejected before the student ever had a chance to prove who they are.
        $student = Student::where('zoho_student_id', $pending['nrich_student_id'])->first();

        if (! $student) {
            $request->session()->forget(self::SESSION_KEY);

            return redirect()->route('login')->with(
                'login_error',
                'Your Student ID was verified, but no matching account exists in this system yet. Please contact your administrator.'
            );
        }

        if (! $student->isActive()) {
            $request->session()->forget(self::SESSION_KEY);

            return redirect()->route('login')->with('login_error', 'This student account has been deactivated. Please contact your administrator.');
        }

        if ($student->branch && ! $student->branch->isActive()) {
            $request->session()->forget(self::SESSION_KEY);

            return redirect()->route('login')->with('login_error', 'This branch has been deactivated. Please contact your administrator.');
        }

        $zohoStudentService->syncStudentFromZoho($student, $result['data']);

        $request->session()->forget(self::SESSION_KEY);

        Auth::guard('student')->login($student, true);
        $request->session()->regenerate();
        SingleSessionService::establish($student, 'student');

        return redirect()
            ->intended(RoleRedirector::dashboardUrl($student))
            ->with('success', 'Login successful. Welcome back!');
    }

    private function resendStudentOtp(Request $request, array $pending): RedirectResponse
    {
        $wait = $this->studentResendCooldown($pending);

        if ($wait > 0) {
            return back()->with('otp_error', "Please wait {$wait} seconds before requesting a new code.");
        }

        $zohoStudentService = app(ZohoStudentService::class);
        $result = $zohoStudentService->identify($pending['nrich_student_id'], sendOtp: true);

        if (! $result['ok']) {
            return back()->with('otp_error', $result['message']);
        }

        $pending['otp_sent_at'] = now()->toIso8601String();
        $pending['otp_validity_minutes'] = $result['otp_validity'];
        $pending['parent_email'] = $zohoStudentService->extractParentEmail($result['data']) ?: ($pending['parent_email'] ?? null);
        $request->session()->put(self::SESSION_KEY, $pending);

        return back()->with('otp_success', 'A new verification code has been sent.');
    }

    private function studentResendCooldown(array $pending): int
    {
        $sentAt = Carbon::parse($pending['otp_sent_at']);
        $elapsed = $sentAt->diffInSeconds(now());

        return max(0, self::STUDENT_RESEND_COOLDOWN_SECONDS - $elapsed);
    }

    /**
     * Same masking idea as mask(), adapted for an alphanumeric ID instead of
     * an email address (no "@domain" part to preserve).
     */
    private function maskId(string $id): string
    {
        $visible = min(2, strlen($id));

        return substr($id, 0, $visible).str_repeat('*', max(1, strlen($id) - $visible));
    }

    private function mask(string $email): string
    {
        [$name, $domain] = array_pad(explode('@', $email, 2), 2, '');

        $visible = min(2, strlen($name));
        $masked = substr($name, 0, $visible).str_repeat('*', max(1, strlen($name) - $visible));

        return $masked.'@'.$domain;
    }
}
