<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Guardian;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use App\Services\LoginOtpService;
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

        $loginType = $credentials['login_type'];
        unset($credentials['login_type']);

        if ($loginType === 'student') {
            return $this->loginStudent($request, $credentials);
        }

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

            return $this->issueOtpAndRedirect($request, $loginType, $user->email);
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

    private function loginStudent(Request $request, array $credentials): RedirectResponse
    {
        $student = Student::where('email', $credentials['email'])->first();

        $validPassword = $student?->password && Hash::check($credentials['password'], $student->password);
        $validLoginCode = $student?->login_code_hash && Hash::check($credentials['password'], $student->login_code_hash);

        if ($student && ($validPassword || $validLoginCode)) {
            if (! $student->isActive()) {
                return back()
                    ->with('login_error', 'This student account has been deactivated. Please contact your administrator.')
                    ->withInput($request->only('email', 'login_type'));
            }

            if ($student->branch && ! $student->branch->isActive()) {
                return back()
                    ->with('login_error', 'This branch has been deactivated. Please contact your administrator.')
                    ->withInput($request->only('email', 'login_type'));
            }

            return $this->issueOtpAndRedirect($request, 'student', $student->email);
        }

        return back()
            ->with('login_error', 'The password you entered is incorrect. Please try again.')
            ->withInput($request->only('email', 'login_type'));
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
    private function issueOtpAndRedirect(Request $request, string $loginType, string $email): RedirectResponse
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
        ]);

        return redirect()->route('login.otp');
    }
}
