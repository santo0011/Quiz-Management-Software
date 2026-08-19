<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\BranchOtpMail;
use App\Mail\StudentOtpMail;
use App\Mail\SuperAdminOtpMail;
use App\Models\Branch;
use App\Models\Student;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PasswordResetController extends Controller
{
    /**
     * Map the selected login type to its user_type value stored in OTP records.
     */
    protected function otpUserType(string $type): string
    {
        return match ($type) {
            'branch' => 'branch',
            'student' => 'student',
            default => 'super_admin',
        };
    }

    /**
     * Get the type-specific account lookup closure.
     */
    protected function findAccountForType(string $type, string $email): User|Student|null
    {
        return match ($type) {
            'branch' => User::where('email', $email)->where('role', 'Branch')->first(),
            'student' => Student::where('email', $email)->first(),
            default => User::where('email', $email)->where('role', 'Super Admin')->first(),
        };
    }

    /**
     * Get the type-specific "account not found" error message.
     */
    protected function notFoundMessage(string $type): string
    {
        return match ($type) {
            'branch' => 'No Branch account found with this email address.',
            'student' => 'No Student account found with this email address.',
            default => 'No Super Admin account found with this email address.',
        };
    }

    public function request(Request $request): View
    {
        $type = $request->query('type', 'super_admin');
        $type = in_array($type, ['super_admin', 'branch', 'student']) ? $type : 'super_admin';

        $configs = [
            'super_admin' => [
                'title' => 'Forgot Password',
                'heading' => 'Reset Super Admin password',
                'copy' => 'Enter the Super Admin email address. A secure 6-digit code will be sent if the account exists.',
                'label' => 'Super Admin Email',
                'placeholder' => 'superadmin@example.com',
            ],
            'branch' => [
                'title' => 'Forgot Password',
                'heading' => 'Reset Branch password',
                'copy' => 'Enter the Branch email address. A secure 6-digit code will be sent if the account exists.',
                'label' => 'Branch Email',
                'placeholder' => 'branch@example.com',
            ],
            'student' => [
                'title' => 'Forgot Password',
                'heading' => 'Reset Student password',
                'copy' => 'Enter the Student email address. A secure 6-digit code will be sent if the account exists.',
                'label' => 'Student Email',
                'placeholder' => 'student@example.com',
            ],
        ];

        $config = $configs[$type];

        return view('auth.passwords.email', [
            'type' => $type,
            'config' => $config,
        ]);
    }

    public function sendOtp(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'type' => ['required', Rule::in(['super_admin', 'branch', 'student'])],
            'email' => ['required', 'email'],
        ], [
            'type.required' => 'Please select a login type.',
            'type.in' => 'Please select a valid login type.',
            'email.required' => 'Email is required.',
            'email.email' => 'Please enter a valid email address.',
        ]);

        $type = $validated['type'];
        $email = $validated['email'];

        // Only search the table/model that matches the selected login type.
        // This prevents cross-type lookups (e.g. a Super Admin email used for Branch reset).
        $account = $this->findAccountForType($type, $email);

        if (! $account) {
            throw ValidationException::withMessages([
                'email' => $this->notFoundMessage($type),
            ]);
        }

        $otp = (string) random_int(100000, 999999);
        $userType = $this->otpUserType($type);

        DB::table('password_reset_otps')
            ->where('email', $email)
            ->where('user_type', $userType)
            ->whereNull('used_at')
            ->update([
                'used_at' => now(),
                'updated_at' => now(),
            ]);

        DB::table('password_reset_otps')->insert([
            'email' => $email,
            'user_type' => $userType,
            'otp_hash' => Hash::make($otp),
            'expires_at' => now()->addMinutes(10),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        try {
            if ($type === 'branch') {
                $branch = Branch::where('email', $email)->first()
                    ?? new Branch(['name' => 'Branch', 'email' => $email]);
                Mail::to($email)->send(new BranchOtpMail($branch, $otp));
            } elseif ($type === 'student') {
                Mail::to($email)->send(new StudentOtpMail($otp, $account instanceof Student ? $account : null));
            } else {
                Mail::to($email)->send(new SuperAdminOtpMail($otp));
            }
            $emailSent = true;
        } catch (\Throwable $e) {
            $emailSent = false;
        }

        $request->session()->put('password_reset_email', $email);
        $request->session()->put('password_reset_type', $type);

        if (! $emailSent) {
            return back()
                ->with('error', 'Password reset instructions could not be sent. Please try again later.')
                ->withInput($request->only('email', 'type'));
        }

        return redirect()
            ->route('password.otp', ['type' => $type])
            ->with('success', 'Password reset instructions have been sent to your email.');
    }

    public function otp(Request $request): View
    {
        $type = $request->query('type', session('password_reset_type', 'super_admin'));
        $type = in_array($type, ['super_admin', 'branch', 'student']) ? $type : 'super_admin';

        $configs = [
            'super_admin' => [
                'title' => 'Verify Reset Code',
                'heading' => 'Verify reset code',
                'copy' => 'Enter the 6-digit code sent to the Super Admin email address.',
                'label' => 'Super Admin Email',
            ],
            'branch' => [
                'title' => 'Verify Reset Code',
                'heading' => 'Verify reset code',
                'copy' => 'Enter the 6-digit code sent to the Branch email address.',
                'label' => 'Branch Email',
            ],
            'student' => [
                'title' => 'Verify Reset Code',
                'heading' => 'Verify reset code',
                'copy' => 'Enter the 6-digit code sent to the Student email address.',
                'label' => 'Student Email',
            ],
        ];

        $config = $configs[$type];

        return view('auth.passwords.otp', [
            'type' => $type,
            'config' => $config,
        ]);
    }

    public function verifyOtp(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'type' => ['required', Rule::in(['super_admin', 'branch', 'student'])],
            'email' => ['required', 'email'],
            'otp' => ['required', 'digits:6'],
        ], [
            'otp.required' => 'Reset code is required.',
            'otp.digits' => 'Reset code must be 6 digits.',
        ]);

        $type = $validated['type'];
        $email = $validated['email'];

        // Only look up OTP records matching the selected account type.
        // This prevents a Branch OTP from being used for Student or Super Admin reset.
        $record = DB::table('password_reset_otps')
            ->where('email', $email)
            ->where('user_type', $this->otpUserType($type))
            ->whereNull('used_at')
            ->latest()
            ->first();

        if (! $record || Carbon::parse($record->expires_at)->isPast() || ! Hash::check($validated['otp'], $record->otp_hash)) {
            return back()
                ->with('reset_error', 'Invalid or expired reset code. Please request a new code.')
                ->withInput(['email' => $email, 'type' => $type]);
        }

        session([
            'password_reset_email' => $email,
            'password_reset_type' => $type,
            'password_reset_verified_otp_id' => $record->id,
        ]);

        return redirect()->route('password.reset.form', ['type' => $type])->with('success', 'Reset code verified. Please set a new password.');
    }

    public function resetForm(Request $request): View
    {
        abort_unless(session('password_reset_verified_otp_id'), 403);

        $type = $request->query('type', session('password_reset_type', 'super_admin'));
        $type = in_array($type, ['super_admin', 'branch', 'student']) ? $type : 'super_admin';

        $configs = [
            'super_admin' => [
                'title' => 'Set New Password',
                'heading' => 'Set a new password',
                'copy' => 'Choose a strong new password for the Super Admin account.',
            ],
            'branch' => [
                'title' => 'Set New Password',
                'heading' => 'Set a new password',
                'copy' => 'Choose a strong new password for the Branch account.',
            ],
            'student' => [
                'title' => 'Set New Password',
                'heading' => 'Set a new password',
                'copy' => 'Choose a strong new password for the Student account.',
            ],
        ];

        $config = $configs[$type];

        return view('auth.passwords.reset', [
            'type' => $type,
            'config' => $config,
        ]);
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
        $type = session('password_reset_type', 'super_admin');
        $otpId = session('password_reset_verified_otp_id');

        if ($type === 'student') {
            $student = Student::where('email', $email)->firstOrFail();
            $student->update([
                'password' => Hash::make($validated['password']),
                'login_code_hash' => null,
            ]);
        } else {
            $role = $type === 'branch' ? 'Branch' : 'Super Admin';
            $user = User::where('email', $email)->where('role', $role)->firstOrFail();
            $user->update(['password' => Hash::make($validated['password'])]);
        }

        DB::table('password_reset_otps')->where('id', $otpId)->update([
            'used_at' => now(),
            'updated_at' => now(),
        ]);

        session()->forget(['password_reset_email', 'password_reset_type', 'password_reset_verified_otp_id']);

        return redirect()->route('login')->with('login_success', 'Password reset successful. Please login with your new password.');
    }
}