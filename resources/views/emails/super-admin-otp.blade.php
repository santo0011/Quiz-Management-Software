@extends('emails.layout')

@section('content')
    <h2 class="greeting" style="margin:0 0 12px; font-size:18px; font-weight:600; color:#1e293b;">Hello Super Admin,</h2>

    <div class="message" style="color:#475569; margin-bottom:20px;">
        <p style="margin:0 0 10px;">We received a request to reset the password for your <strong>QuizCore</strong> Super Admin account.</p>
        <p style="margin:0;">Use the secure code below to verify your identity:</p>
    </div>

    <div class="code-box" style="background-color:#f0f9ff; border:2px dashed #38bdf8; border-radius:8px; padding:20px; text-align:center; margin:20px 0;">
        <p class="code-label" style="margin:0 0 8px; font-size:12px; font-weight:600; text-transform:uppercase; letter-spacing:1px; color:#0284c7;">Your Password Reset Code</p>
        <p class="code-value" style="margin:0; font-size:32px; font-weight:700; letter-spacing:8px; color:#0c4a6e; font-family:'Courier New', Courier, monospace;">{{ $otp }}</p>
    </div>

    <div class="message" style="color:#475569; margin-bottom:20px;">
        <p style="margin:0 0 10px;">This code <strong>expires in 10 minutes</strong> and can be used only once.</p>
        <p style="margin:0;">If you did not request this reset, you can safely ignore this email. Your account remains secure.</p>
    </div>
@endsection