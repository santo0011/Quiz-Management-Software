<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Student;
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

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
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
                return redirect()
                    ->route('branch.dashboard')
                    ->with('success', 'Login successful. Welcome back!');
            }

            return redirect()
                ->route('admin.dashboard')
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
            Auth::guard('student')->login($student);
            $request->session()->regenerate();

            return redirect()
                ->route('student.dashboard')
                ->with('success', 'Login successful. Welcome back!');
        }

        return back()
            ->with('login_error', 'The password you entered is incorrect. Please try again.')
            ->withInput($request->only('email', 'login_type'));
    }
}
