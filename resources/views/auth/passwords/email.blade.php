@include('auth.passwords.partials.shell', [
    'title' => 'Forgot Password',
    'heading' => 'Reset Super Admin password',
    'copy' => 'Enter the Super Admin email address. A secure 6-digit code will be sent if the account exists.',
    'slot' => 'auth.passwords.partials.email-form',
])
