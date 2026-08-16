<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }} - {{ config('app.name') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="{{ asset('css/admin.css') }}" rel="stylesheet">
</head>
<body class="login-body">
    <main class="login-wrap">
        <section class="login-panel">
            <div class="login-brand">
                <div class="brand-mark">Q</div>
                <div>
                    <strong>QuizCore</strong>
                    <span>Secure Password Reset</span>
                </div>
            </div>

            <h1>{{ $heading }}</h1>
            <p>{{ $copy }}</p>

            @if (session('success'))
                <div class="alert alert-success feedback-alert success" role="alert">
                    <i class="bi bi-check-circle-fill"></i>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            @if (session('reset_error'))
                <div class="alert alert-danger feedback-alert" role="alert">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    <span>{{ session('reset_error') }}</span>
                </div>
            @endif

            @if (session('error'))
                <div class="alert alert-danger feedback-alert" role="alert">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    <span>{{ session('error') }}</span>
                </div>
            @endif

            @include($slot)
        </section>
    </main>
    <script>
        document.querySelectorAll('[data-password-toggle]').forEach((button) => {
            button.addEventListener('click', () => {
                const input = button.closest('.password-field').querySelector('input');
                const icon = button.querySelector('i');
                const showPassword = input.type === 'password';

                input.type = showPassword ? 'text' : 'password';
                button.setAttribute('aria-label', showPassword ? 'Hide password' : 'Show password');
                icon.className = showPassword ? 'bi bi-eye-slash-fill' : 'bi bi-eye-fill';
            });
        });
    </script>
    @include('partials.global-forms')
</body>
</html>
