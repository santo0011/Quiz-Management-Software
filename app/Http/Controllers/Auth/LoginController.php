<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Guardian;
use App\Models\Setting;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use App\Services\LoginLogger;
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
                LoginLogger::failed($expectedRole, 'Password + OTP', 'These credentials do not match the selected login type.', $user->name, $user->email, $user->id, $user->role === 'Branch' ? $user->branch : null);

                return back()
                    ->with('login_error', 'These credentials do not match the selected login type.')
                    ->withInput($request->only('email', 'login_type'));
            }

            if ($expectedRole === 'Branch') {
                $branch = $user->branch;

                if ($branch && ! $branch->isActive()) {
                    LoginLogger::failed($expectedRole, 'Password + OTP', 'This branch account has been deactivated.', $user->name, $user->email, $user->id, $branch);

                    return back()
                        ->with('login_error', 'This branch account has been deactivated. Please contact the administrator.')
                        ->withInput($request->only('email', 'login_type'));
                }
            }

            return $this->issueOtpAndRedirect($request, $loginType, $user->email, $request->boolean('remember'));
        }

        $attemptedUser = User::where('email', $credentials['email'])->first();
        LoginLogger::failed($expectedRole, 'Password + OTP', 'Invalid email or password.', $attemptedUser?->name, $credentials['email'], $attemptedUser?->id, $expectedRole === 'Branch' ? $attemptedUser?->branch : null);

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

        $result = $zohoStudentService->sendOtp($nrichStudentId);

        if (! $result['ok']) {
            LoginLogger::failed('Student', 'OTP', $result['message'], identifier: $nrichStudentId);

            return back()
                ->with('login_error', $result['message'])
                ->withInput($request->only('login_type'));
        }

        $student = Student::where('zoho_student_id', $nrichStudentId)->first();

        if ($student && ! $student->isActive()) {
            LoginLogger::failed('Student', 'OTP', 'Student account is deactivated.', $student->student_name, $nrichStudentId, $student->id, $student->branch);

            return back()
                ->with('login_error', 'This student account has been deactivated. Please contact your administrator.')
                ->withInput($request->only('login_type'));
        }

        if ($student && $student->branch && ! $student->branch->isActive()) {
            LoginLogger::failed('Student', 'OTP', 'Branch is deactivated.', $student->student_name, $nrichStudentId, $student->id, $student->branch);

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
     * the Student receiving/entering a Zoho OTP.
     *
     * The common password is a local authorization check (is this Teacher
     * allowed to use the override at all?), not Student verification, so it
     * can run first as a cheap fail-fast. Actual Student verification still
     * goes through Zoho API 2 unconditionally afterward — with
     * send_otp:false, so it never triggers an OTP email — exactly like the
     * normal flow, and with no local lookup beforehand: a local Student
     * record is only required at the very end, to create the session.
     */
    private function loginStudentViaTeacherOverride(Request $request, string $nrichStudentId, string $password): RedirectResponse
    {
        $settings = Setting::current();

        if (! $settings->hasCommonStudentPassword() || ! Hash::check($password, $settings->common_student_password)) {
            LoginLogger::failed('Student', 'Teacher Override', 'Incorrect Teacher Override password.', identifier: $nrichStudentId);

            return back()
                ->with('login_error', 'Incorrect Teacher Override password.')
                ->withInput($request->only('login_type'));
        }

        $zohoStudentService = app(ZohoStudentService::class);
        $result = $zohoStudentService->verifyForTeacherOverride($nrichStudentId);

        if (! $result['ok']) {
            LoginLogger::failed('Student', 'Teacher Override', $result['message'], identifier: $nrichStudentId);

            return back()
                ->with('login_error', $result['message'])
                ->withInput($request->only('login_type'));
        }

        $student = Student::where('zoho_student_id', $nrichStudentId)->first();

        if (! $student) {
            [$student, $provisionError] = $this->provisionStudentFromZoho($nrichStudentId, $result['data'], $zohoStudentService);

            if (! $student) {
                LoginLogger::failed('Student', 'Teacher Override', $provisionError, identifier: $nrichStudentId);

                return back()
                    ->with('login_error', $provisionError)
                    ->withInput($request->only('login_type'));
            }
        } else {
            if (! $student->isActive()) {
                LoginLogger::failed('Student', 'Teacher Override', 'Student account is deactivated.', $student->student_name, $nrichStudentId, $student->id, $student->branch);

                return back()
                    ->with('login_error', 'This student account has been deactivated. Please contact your administrator.')
                    ->withInput($request->only('login_type'));
            }

            if ($student->branch && ! $student->branch->isActive()) {
                LoginLogger::failed('Student', 'Teacher Override', 'Branch is deactivated.', $student->student_name, $nrichStudentId, $student->id, $student->branch);

                return back()
                    ->with('login_error', 'This branch has been deactivated. Please contact your administrator.')
                    ->withInput($request->only('login_type'));
            }

            $zohoStudentService->syncStudentFromZoho($student, $result['data']);
        }

        Auth::guard('student')->login($student, true);
        $request->session()->regenerate();
        SingleSessionService::establish($student, 'student');

        LoginLogger::success('Student', 'Teacher Override', $student->student_name, $nrichStudentId, $student->id, $student->branch);

        return redirect()
            ->to(RoleRedirector::postLoginUrl($student))
            ->with('success', 'Login successful. Welcome back!');
    }

    /**
     * Teacher Override must be able to log a Student in even when Zoho
     * verifies an ID this app has never seen before — but creating that
     * record still needs real data, never invented placeholders. Every
     * field here is either a genuine Zoho value or, when Zoho didn't return
     * one, causes provisioning to fail with a clear reason rather than
     * fabricate something plausible-looking.
     *
     * @return array{0: ?Student, 1: ?string} [created student, or null with an error message]
     */
    private function provisionStudentFromZoho(string $nrichStudentId, array $zohoData, ZohoStudentService $zohoStudentService): array
    {
        $branchId = Setting::current()->default_teacher_override_branch_id;

        if (! $branchId) {
            return [null, 'This Student has no account in this system yet, and no default branch is configured for Teacher Override to create one. Please ask your Super Admin to set one in Settings.'];
        }

        $info = $zohoStudentService->extractProvisioningData($zohoData);

        if (! $info['guardian_email']) {
            return [null, 'This Student has no account in this system yet, and Zoho did not return a registered email to create one with. Please contact your administrator.'];
        }

        if (Student::where('email', $info['guardian_email'])->exists()) {
            return [null, 'This Student\'s registered email is already used by a different account in this system. Please ask your administrator to resolve this before continuing.'];
        }

        $student = Student::create([
            'branch_id' => $branchId,
            'student_name' => $info['student_name'] ?: $nrichStudentId,
            'guardian_name' => $info['guardian_name'] ?: ($info['student_name'] ?: $nrichStudentId),
            'guardian_email' => $info['guardian_email'],
            'class' => $info['grade'] ?: ($info['class_name'] ?: 'Unassigned'),
            'phone_number' => '',
            'email' => $info['guardian_email'],
            'zoho_student_id' => $nrichStudentId,
            'is_active' => true,
        ]);

        $zohoStudentService->syncStudentFromZoho($student, $zohoData);

        return [$student, null];
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

        LoginLogger::failed('Guardian', 'Password + OTP', 'Invalid email or password.', $guardian?->name, $credentials['email'], $guardian?->id);

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
                LoginLogger::failed('Teacher', 'Password + OTP', 'Branch is deactivated.', $teacher->name, $teacher->email, $teacher->id, $teacher->branch);

                return back()
                    ->with('login_error', 'This branch has been deactivated. Please contact your administrator.')
                    ->withInput($request->only('email', 'login_type'));
            }

            return $this->issueOtpAndRedirect($request, 'teacher', $teacher->email);
        }

        LoginLogger::failed('Teacher', 'Password + OTP', 'Invalid email or password.', $teacher?->name, $credentials['email'], $teacher?->id, $teacher?->branch);

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
