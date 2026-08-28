@extends('emails.layout')

@section('content')
    <h2 class="greeting" style="margin:0 0 12px; font-size:18px; font-weight:600; color:#1e293b;">Welcome, {{ $teacher->name }}!</h2>

    <div class="message" style="color:#475569; margin-bottom:20px;">
        <p style="margin:0 0 10px;">Your Teacher Panel account has been created for <strong>QuizCore</strong> — Quiz Management Software.</p>
        <p style="margin:0;">Please use the credentials below to access your teacher dashboard.</p>
    </div>

    <div class="info-card" style="background-color:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:20px 24px; margin:20px 0;">
        <p class="label" style="margin:0 0 4px; font-size:12px; font-weight:600; text-transform:uppercase; letter-spacing:0.5px; color:#64748b;">Login Email</p>
        <p class="value" style="margin:0; font-size:16px; font-weight:600; color:#1e293b; word-break:break-all;">{{ $teacher->email }}</p>

        <p class="label" style="margin:16px 0 4px; font-size:12px; font-weight:600; text-transform:uppercase; letter-spacing:0.5px; color:#64748b;">Temporary Password</p>
        <p class="value" style="margin:0; font-size:16px; font-weight:600; color:#1e293b; word-break:break-all;">{{ $temporaryPassword }}</p>
    </div>

    <div class="message" style="color:#475569; margin-bottom:20px;">
        <p style="margin:0 0 10px;">Login requires this password plus a one-time verification code sent to this email address.</p>
        <p style="margin:0 0 10px;">For security reasons, please <strong>log in and change this password</strong> from your Teacher Panel at your earliest convenience.</p>
        <p style="margin:0;">If you did not expect this account, please contact your branch administrator immediately.</p>
    </div>
@endsection
