@extends('emails.layout')

@section('content')
    <h2 class="greeting" style="margin:0 0 12px; font-size:18px; font-weight:600; color:#1e293b;">Hello {{ $name }},</h2>

    <div class="message" style="color:#475569; margin-bottom:20px;">
        <p style="margin:0 0 10px;">A login attempt was made to your <strong>QuizCore {{ $typeLabel }}</strong> account.</p>
        <p style="margin:0;">Use the code below to complete your login:</p>
    </div>

    <div class="code-box" style="background-color:#f0f9ff; border:2px dashed #38bdf8; border-radius:8px; padding:20px; text-align:center; margin:20px 0;">
        <p class="code-label" style="margin:0 0 8px; font-size:12px; font-weight:600; text-transform:uppercase; letter-spacing:1px; color:#0284c7;">Your Login Verification Code</p>
        <p class="code-value" style="margin:0; font-size:32px; font-weight:700; letter-spacing:8px; color:#0c4a6e; font-family:'Courier New', Courier, monospace;">{{ $otp }}</p>
    </div>

    <div class="message" style="color:#475569; margin-bottom:20px;">
        <p style="margin:0 0 10px;">This code <strong>expires in 5 minutes</strong> and can be used only once.</p>
        <p style="margin:0;">If this wasn't you, please secure your account immediately by changing your password.</p>
    </div>
@endsection
