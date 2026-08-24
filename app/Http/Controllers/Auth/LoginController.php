<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Services\SingleSessionService;
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
        $credentials = $request->validate([
            'login_type' => ['required', Rule::in(['super_admin', 'branch', 'student'])],
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

        $expectedRole = $loginType === 'super_admin' ? 'Super Admin' : 'Branch';

        if (Auth::attempt($credentials, true)) {
            $request->session()->regenerate();

            if ($request->user()->role !== $expectedRole) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return back()
                    ->with('login_error', 'These credentials do not match the selected login type.')
                    ->withInput($request->only('email', 'login_type'));
            }

            if ($expectedRole === 'Branch') {
                $branch = $request->user()->branch;

                if ($branch && ! $branch->isActive()) {
                    Auth::logout();
                    $request->session()->invalidate();
                    $request->session()->regenerateToken();

                    return back()
                        ->with('login_error', 'This branch account has been deactivated. Please contact the administrator.')
                        ->withInput($request->only('email', 'login_type'));
                }

                SingleSessionService::establish($request->user(), 'web');

                return redirect()
                    ->intended(RoleRedirector::dashboardUrl($request->user()))
                    ->with('success', 'Login successful. Welcome back!');
            }

            return redirect()
                ->intended(RoleRedirector::dashboardUrl($request->user()))
                ->with('success', 'Login successful. Welcome back!');
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

            Auth::guard('student')->login($student, true);
            $request->session()->regenerate();
            SingleSessionService::establish($student, 'student');

            return redirect()
                ->intended(RoleRedirector::dashboardUrl($student))
                ->with('success', 'Login successful. Welcome back!');
        }

        return back()
            ->with('login_error', 'The password you entered is incorrect. Please try again.')
            ->withInput($request->only('email', 'login_type'));
    }
}
