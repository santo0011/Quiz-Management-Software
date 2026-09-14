<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Guardian;
use App\Models\Setting;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use App\Services\LoginOtpService;
use App\Services\SingleSessionService;
use App\Services\ZohoStudentService;
use App\Support\RoleRedirector;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function showLoginForm(): View
    {
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $loginType = $request->input('login_type');

        if ($loginType === 'student') {
            $isTeacherOverride = $request->boolean('teacher_override');

            $rules = [
                'login_type' => ['required', Rule::in(['super_admin', 'branch', 'student', 'guardian', 'teacher'])],
                'nrich_student_id' => ['required', 'string'],
            ];
            $messages = [
                'login_type.required' => 'Please select a login type.',
                'login_type.in' => 'Please select a valid login type.',
                'nrich_student_id.required' => 'Please enter your Student ID.',
            ];

            if ($isTeacherOverride) {
                $rules['password'] = ['required', 'string'];
                $messages['password.required'] = 'Please enter the Teacher Override password.';
            }

            $validated = $request->validate($rules, $messages);

            if ($isTeacherOverride) {
                return $this->loginStudentViaTeacherOverride($request, $validated['nrich_student_id'], $validated['password']);
            }

            return $this->loginStudent($request, $validated['nrich_student_id']);
        }

        $credentials = $request->validate([
            'login_type' => ['required', Rule::in(['super_admin', 'branch', 'student', 'guardian', 'teacher'])],
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ], [
            'login_type.required' => 'Please select a login type.',
            'login_type.in' => 'Please select a valid login type.',
            'email.required' => 'Email is required.',
            'email.email' => 'Please enter a valid email address.',
            'password.required' => 'Password is required.',
        ]);

        unset($credentials['login_type']);

        if ($loginType === 'guardian') {
            return $this->loginGuardian($request, $credentials);
        }

        if ($loginType === 'teacher') {
            return $this->loginTeacher($request, $credentials);
        }

        $expectedRole = $loginType === 'super_admin' ? 'Super Admin' : 'Branch';

        if (Auth::guard('web')->validate($credentials)) {
            $user = User::where('email', $credentials['email'])->first();

            if ($user->role !== $expectedRole) {
                return back()
                    ->with('login_error', 'These credentials do not match the selected login type.')
                    ->withInput($request->only('email', 'login_type'));
            }

            if ($expectedRole === 'Branch') {
                $branch = $user->branch;

                if ($branch && ! $branch->isActive()) {
                    return back()
                        ->with('login_error', 'This branch account has been deactivated. Please contact the administrator.')
                        ->withInput($request->only('email', 'login_type'));
                }
            }

            return $this->issueOtpAndRedirect($request, $loginType, $user->email, $request->boolean('remember'));
        }

        return back()
            ->with('login_error', 'Invalid email or password. Please check your credentials and try again.')
            ->withInput($request->only('email', 'login_type'));
    }

    public function logout(Request $request): RedirectResponse
    {
        if (Auth::guard('student')->check()) {
            Auth::guard('student')->logout();
        }

        if (Auth::guard('guardian')->check()) {
            Auth::guard('guardian')->logout();
        }

        if (Auth::guard('teacher')->check()) {
            Auth::guard('teacher')->logout();
        }

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    /**
     * Students are identified by their NRICH Student ID. Zoho is the sole
     * source of truth for whether that ID is real — it is sent to Zoho API 2
     * unconditionally, with no local lookup beforehand. As soon as Zoho
     * confirms the ID, the student proceeds to the OTP screen; whether a
     * local Student record exists is resolved only after the OTP itself is
     * verified (see LoginOtpController::verifyStudentOtp()), since that's
     * the point at which we actually need one to create a session.
     *
     * The one local check kept here is a fast-fail for an ALREADY-KNOWN
     * deactivated account/branch, purely so a disabled student doesn't wait
     * through an OTP email for nothing — it never rejects for a MISSING
     * record, only for one that positively exists and is inactive.
     */
    private function loginStudent(Request $request, string $nrichStudentId): RedirectResponse
    {
        $zohoStudentService = app(ZohoStudentService::class);

        $result = $zohoStudentService->identify($nrichStudentId, sendOtp: true);

        if (! $result['ok']) {
            return back()
                ->with('login_error', $result['message'])
                ->withInput($request->only('login_type'));
        }

        $student = Student::where('zoho_student_id', $nrichStudentId)->first();

        if ($student && ! $student->isActive()) {
            return back()
                ->with('login_error', 'This student account has been deactivated. Please contact your administrator.')
                ->withInput($request->only('login_type'));
        }

        if ($student && $student->branch && ! $student->branch->isActive()) {
            return back()
                ->with('login_error', 'This branch has been deactivated. Please contact your administrator.')
                ->withInput($request->only('login_type'));
        }

        if ($student) {
            $zohoStudentService->syncStudentFromZoho($student, $result['data']);
        }

        $request->session()->regenerate();
        $request->session()->put('pending_login', [
            'type' => 'student',
            'nrich_student_id' => $nrichStudentId,
            'parent_email' => $zohoStudentService->extractParentEmail($result['data']),
            'otp_sent_at' => now()->toIso8601String(),
            'otp_validity_minutes' => $result['otp_validity'],
            'otp_attempts' => 0,
        ]);

        return redirect()->route('login.otp');
    }

    /**
     * Teacher Override: a Teacher signs a Student in directly using one
     * common password (configured by Super Admin in Settings) instead of
     * the Student receiving/entering a Zoho OTP. Zoho API 2 is still called
     * to confirm the Student ID is valid — but with send_otp:false, so it
     * never triggers an OTP email — and login completes immediately with no
     * OTP screen at all.
     */
    private function loginStudentViaTeacherOverride(Request $request, string $nrichStudentId, string $password): RedirectResponse
    {
        $settings = Setting::current();

        if (! $settings->hasCommonStudentPassword() || ! Hash::check($password, $settings->common_student_password)) {
            return back()
                ->with('login_error', 'Incorrect Teacher Override password.')
                ->withInput($request->only('login_type'));
        }

        $student = Student::where('zoho_student_id', $nrichStudentId)->first();

        if (! $student) {
            return back()
                ->with('login_error', 'No student account found with this Student ID.')
                ->withInput($request->only('login_type'));
        }

        if (! $student->isActive()) {
            return back()
                ->with('login_error', 'This student account has been deactivated. Please contact your administrator.')
                ->withInput($request->only('login_type'));
        }

        if ($student->branch && ! $student->branch->isActive()) {
            return back()
                ->with('login_error', 'This branch has been deactivated. Please contact your administrator.')
                ->withInput($request->only('login_type'));
        }

        $zohoStudentService = app(ZohoStudentService::class);
        $result = $zohoStudentService->identify($nrichStudentId, sendOtp: false);

        if (! $result['ok']) {
            return back()
                ->with('login_error', $result['message'])
                ->withInput($request->only('login_type'));
        }

        $zohoStudentService->syncStudentFromZoho($student, $result['data']);

        Auth::guard('student')->login($student, true);
        $request->session()->regenerate();
        SingleSessionService::establish($student, 'student');

        return redirect()
            ->intended(RoleRedirector::dashboardUrl($student))
            ->with('success', 'Login successful. Welcome back!');
    }

    /**
     * "Subsequent login" for a Guardian who already set a password (via
     * GuardianPasswordController's first-time OTP flow). Every login still
     * requires a fresh OTP, same as Student/Branch/Super Admin.
     */
    private function loginGuardian(Request $request, array $credentials): RedirectResponse
    {
        $guardian = Guardian::where('email', $credentials['email'])->first();

        if ($guardian && $guardian->password && Hash::check($credentials['password'], $guardian->password)) {
            return $this->issueOtpAndRedirect($request, 'guardian', $guardian->email);
        }

        return back()
            ->with('login_error', 'The password you entered is incorrect. Please try again.')
            ->withInput($request->only('email', 'login_type'));
    }

    /**
     * Teacher accounts are created by their Branch with a password already
     * set (emailed at creation time), so there is no first-time OTP/set
     * password step here — just password + OTP, same as Guardian/Branch.
     */
    private function loginTeacher(Request $request, array $credentials): RedirectResponse
    {
        $teacher = Teacher::where('email', $credentials['email'])->first();

        if ($teacher && Hash::check($credentials['password'], $teacher->password)) {
            if ($teacher->branch && ! $teacher->branch->isActive()) {
                return back()
                    ->with('login_error', 'This branch has been deactivated. Please contact your administrator.')
                    ->withInput($request->only('email', 'login_type'));
            }

            return $this->issueOtpAndRedirect($request, 'teacher', $teacher->email);
        }

        return back()
            ->with('login_error', 'The password you entered is incorrect. Please try again.')
            ->withInput($request->only('email', 'login_type'));
    }

    /**
     * Credentials (and role/active status) are valid. Instead of completing
     * login immediately, email a 6-digit OTP and park the pending login in
     * the session until it is verified by LoginOtpController.
     */
    private function issueOtpAndRedirect(Request $request, string $loginType, string $email, bool $remember = false): RedirectResponse
    {
        if (! LoginOtpService::send($loginType, $email)) {
            return back()
                ->with('login_error', 'We could not send a verification code to your email. Please try again.')
                ->withInput($request->only('email', 'login_type'));
        }

        $request->session()->regenerate();
        $request->session()->put('pending_login', [
            'type' => $loginType,
            'email' => $email,
            'remember' => $remember,
        ]);

        return redirect()->route('login.otp');
    }
}
