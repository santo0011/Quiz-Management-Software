<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\StudentOtpMail;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class StudentPasswordController extends Controller
{
    public function checkEmail(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
        ], [
            'email.required' => 'Student email is required.',
            'email.email' => 'Please enter a valid student email address.',
        ]);

        $student = Student::where('email', $validated['email'])->first();

        if (! $student) {
            throw ValidationException::withMessages([
                'email' => 'No student account found with this email address.',
            ]);
        }

        return response()->json([
            'status' => $student->password ? 'password_required' : 'password_setup_required',
            'message' => $student->password
                ? 'Student account verified. Please enter your password.'
                : 'Student account verified. Create a password using a secure OTP.',
        ]);
    }

    public function sendOtp(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'exists:students,email'],
        ], [
            'email.required' => 'Student email is required.',
            'email.email' => 'Please enter a valid student email address.',
            'email.exists' => 'No student account found with this email address.',
        ]);

        $student = Student::where('email', $validated['email'])->firstOrFail();
        $otp = (string) random_int(100000, 999999);

        DB::table('password_reset_otps')
            ->where('email', $student->email)
            ->whereNull('used_at')
            ->update([
                'used_at' => now(),
                'updated_at' => now(),
            ]);

        DB::table('password_reset_otps')->insert([
            'email' => $student->email,
            'otp_hash' => Hash::make($otp),
            'expires_at' => now()->addMinutes(10),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        try {
            Mail::to($student->email)->send(new StudentOtpMail($otp));
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'OTP could not be sent. Please try again later.',
            ], 500);
        }

        $request->session()->put('student_password_email', $student->email);
        $request->session()->forget('student_password_verified_otp_id');

        return response()->json([
            'message' => 'OTP has been sent to your registered email.',
        ]);
    }

    public function verifyOtp(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'exists:students,email'],
            'otp' => ['required', 'digits:6'],
        ], [
            'otp.required' => 'OTP is required.',
            'otp.digits' => 'OTP must be 6 digits.',
        ]);

        $record = DB::table('password_reset_otps')
            ->where('email', $validated['email'])
            ->whereNull('used_at')
            ->latest()
            ->first();

        if (! $record || Carbon::parse($record->expires_at)->isPast() || ! Hash::check($validated['otp'], $record->otp_hash)) {
            throw ValidationException::withMessages([
                'otp' => 'Invalid or expired OTP. Please request a new code.',
            ]);
        }

        session([
            'student_password_email' => $validated['email'],
            'student_password_verified_otp_id' => $record->id,
        ]);

        return response()->json([
            'message' => 'OTP verified. Please create your password.',
        ]);
    }

    public function createPassword(Request $request): JsonResponse
    {
        abort_unless(session('student_password_email') && session('student_password_verified_otp_id'), 403);

        $validated = $request->validate([
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ], [
            'password.required' => 'Please enter a new password.',
            'password.min' => 'Password must be at least 6 characters.',
            'password.confirmed' => 'Password confirmation does not match.',
        ]);

        $student = Student::where('email', session('student_password_email'))->firstOrFail();
        $otpId = session('student_password_verified_otp_id');

        $student->update([
            'password' => Hash::make($validated['password']),
            'login_code_hash' => null,
        ]);

        DB::table('password_reset_otps')
            ->where('id', $otpId)
            ->where('email', $student->email)
            ->whereNull('used_at')
            ->update([
                'used_at' => now(),
                'updated_at' => now(),
            ]);

        session()->forget(['student_password_email', 'student_password_verified_otp_id']);

        Auth::guard('student')->login($student, true);
        $request->session()->regenerate();

        return response()->json([
            'message' => 'Password created successfully. Redirecting to your dashboard.',
            'redirect' => route('student.dashboard'),
        ]);
    }
}
