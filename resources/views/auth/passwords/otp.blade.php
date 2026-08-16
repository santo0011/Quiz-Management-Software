@include('auth.passwords.partials.shell', [
    'title' => 'Verify Reset Code',
    'heading' => 'Verify reset code',
    'copy' => 'Enter the 6-digit code sent to the Super Admin email address.',
    'slot' => 'auth.passwords.partials.otp-form',
])
