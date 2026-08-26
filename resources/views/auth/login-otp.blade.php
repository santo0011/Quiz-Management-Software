<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Verify Login - {{ config('app.name') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="{{ asset('css/admin.css') }}" rel="stylesheet">
    <style>
        .otp-input {
            font-size: 1.75rem;
            letter-spacing: 0.6em;
            text-align: center;
            font-weight: 700;
            padding-left: 0.6em;
        }

        .otp-meta {
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 0.85rem;
            color: var(--muted);
            margin: 14px 0 18px;
        }

        .otp-resend-btn {
            border: 0;
            background: none;
            padding: 0;
            font-weight: 600;
            color: var(--primary);
        }

        .otp-resend-btn:disabled {
            color: var(--muted);
            cursor: not-allowed;
        }

        .otp-email-note {
            font-size: 0.9rem;
            color: var(--muted);
            margin: -14px 0 22px;
        }
    </style>
</head>
<body class="login-body">
    <main class="login-wrap">
        <section class="login-panel">
            <div class="login-brand">
                <div class="brand-mark">Q</div>
                <div>
                    <strong>QuizCore</strong>
                    <span>Two-Step Verification</span>
                </div>
            </div>

            <h1>Verify your identity</h1>
            <p>Enter the 6-digit code sent to your {{ $typeLabel }} email to finish signing in.</p>
            <p class="otp-email-note"><i class="bi bi-envelope-check"></i> Code sent to <strong>{{ $maskedEmail }}</strong></p>

            @if (session('success'))
                <div class="alert alert-success feedback-alert success" role="alert">
                    <i class="bi bi-check-circle-fill"></i>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            @if (session('otp_success'))
                <div class="alert alert-success feedback-alert success" role="alert">
                    <i class="bi bi-check-circle-fill"></i>
                    <span>{{ session('otp_success') }}</span>
                </div>
            @endif

            @if (session('otp_error'))
                <div class="alert alert-danger feedback-alert" role="alert">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    <span>{{ session('otp_error') }}</span>
                </div>
            @endif

            <form method="POST" action="{{ route('login.otp.verify') }}" class="login-form">
                @csrf
                <div class="mb-2">
                    <label for="otp" class="form-label">6-Digit Verification Code</label>
                    <input
                        id="otp"
                        type="text"
                        name="otp"
                        class="form-control otp-input @error('otp') is-invalid @enderror"
                        inputmode="numeric"
                        pattern="[0-9]{6}"
                        maxlength="6"
                        autocomplete="one-time-code"
                        autofocus
                        required
                    >
                    @error('otp')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="otp-meta">
                    <span><i class="bi bi-clock-history"></i> Expires in <span id="otpExpiryCountdown">05:00</span></span>
                </div>

                <button type="submit" class="btn btn-primary w-100" data-loading="Verifying...">
                    <i class="bi bi-shield-check"></i>
                    Verify &amp; Continue
                </button>
            </form>

            <form method="POST" action="{{ route('login.otp.resend') }}" id="resendForm">
                @csrf
                <button type="submit" class="otp-resend-btn" id="resendBtn" disabled>
                    Resend code <span id="resendCountdown"></span>
                </button>
            </form>

            <a href="{{ route('login') }}" class="auth-link">
                <i class="bi bi-arrow-left"></i> Back to login
            </a>
        </section>
    </main>
    <script>
        (function () {
            var expirySeconds = {{ (int) $expiresInSeconds }};
            var cooldownSeconds = {{ (int) $cooldown }};

            var expiryEl = document.getElementById('otpExpiryCountdown');
            var resendBtn = document.getElementById('resendBtn');
            var resendCountdownEl = document.getElementById('resendCountdown');

            var expiryTimer = setInterval(function () {
                expirySeconds = Math.max(0, expirySeconds - 1);
                var minutes = Math.floor(expirySeconds / 60);
                var seconds = expirySeconds % 60;
                expiryEl.textContent = String(minutes).padStart(2, '0') + ':' + String(seconds).padStart(2, '0');

                if (expirySeconds <= 0) {
                    clearInterval(expiryTimer);
                    expiryEl.textContent = 'expired';
                }
            }, 1000);

            function tickResend() {
                if (cooldownSeconds > 0) {
                    resendBtn.disabled = true;
                    resendCountdownEl.textContent = '(' + cooldownSeconds + 's)';
                    cooldownSeconds -= 1;
                    setTimeout(tickResend, 1000);
                } else {
                    resendBtn.disabled = false;
                    resendCountdownEl.textContent = '';
                }
            }

            tickResend();
        })();
    </script>
    @include('partials.global-forms')
</body>
</html>
