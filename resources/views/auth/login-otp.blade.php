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
        .otp-page-heading {
            margin-bottom: 16px;
        }

        .otp-page-heading h1 {
            margin-bottom: 4px;
        }

        .otp-page-heading p {
            margin-bottom: 0;
        }

        .otp-email-card {
            display: flex;
            align-items: center;
            gap: 10px;
            background: #f3f7ff;
            border: 1px solid #dde6fb;
            border-radius: 10px;
            padding: 12px 14px;
            margin-bottom: 20px;
        }

        .otp-email-card i {
            flex: 0 0 auto;
            font-size: 1.05rem;
            color: var(--primary);
        }

        .otp-email-card-text {
            min-width: 0;
            font-size: 0.88rem;
            color: var(--muted);
            line-height: 1.4;
            word-break: break-word;
        }

        .otp-email-card-text strong {
            color: #1b2536;
            font-weight: 700;
        }

        .otp-form-group {
            margin-bottom: 18px;
        }

        .otp-input-wrap {
            width: 100%;
        }

        .otp-input {
            width: 100%;
            height: 42px;
            padding: 0.25rem 0.6em;
            font-size: 1.3rem;
            letter-spacing: 0.5em;
            text-align: center;
            font-weight: 700;
            line-height: 1.2;
        }

        .otp-input.is-invalid {
            margin-bottom: 0;
        }

        .otp-input + .invalid-feedback {
            text-align: center;
        }

        .otp-meta {
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.85rem;
            color: var(--muted);
            margin: 14px 0 20px;
        }

        .otp-resend-row {
            text-align: center;
            margin-top: 14px;
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

        @media (max-width: 480px) {
            .otp-email-card {
                padding: 10px 12px;
            }

            .otp-input {
                font-size: 1.15rem;
                letter-spacing: 0.35em;
            }
        }
    </style>
</head>
<body class="login-body">
    <main class="login-wrap">
        <section class="login-panel">
            @include('partials.auth-brand', ['subtitle' => 'Two-Step Verification'])

            <div class="otp-page-heading">
                <h1>Verify your identity</h1>
                <p>Enter the 6-digit code sent to your {{ $typeLabel }} email to finish signing in.</p>
            </div>

            <div class="otp-email-card">
                <i class="bi bi-envelope-check"></i>
                <span class="otp-email-card-text">OTP sent to: <strong>{{ $maskedEmail }}</strong></span>
            </div>

            @if (session('success'))
                <div class="alert alert-success feedback-alert success" role="alert" data-auto-dismiss>
                    <i class="bi bi-check-circle-fill"></i>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            @if (session('otp_success'))
                <div class="alert alert-success feedback-alert success" role="alert" data-auto-dismiss>
                    <i class="bi bi-check-circle-fill"></i>
                    <span>{{ session('otp_success') }}</span>
                </div>
            @endif

            @if (session('otp_error'))
                <div class="alert alert-danger feedback-alert" role="alert" data-auto-dismiss>
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    <span>{{ session('otp_error') }}</span>
                </div>
            @endif

            <form method="POST" action="{{ route('login.otp.verify') }}" class="login-form">
                @csrf
                <div class="otp-form-group">
                    <label for="otp" class="form-label text-center d-block">6-Digit Verification Code</label>
                    <div class="otp-input-wrap">
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
                </div>

                <div class="otp-meta">
                    <span><i class="bi bi-clock-history"></i> Expires in <span id="otpExpiryCountdown">05:00</span></span>
                </div>

                <button type="submit" class="btn btn-primary w-100" data-loading="Verifying...">
                    <i class="bi bi-shield-check"></i>
                    Verify &amp; Continue
                </button>
            </form>

            <form method="POST" action="{{ route('login.otp.resend') }}" id="resendForm" class="otp-resend-row">
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

            document.querySelectorAll('[data-auto-dismiss]').forEach((alertEl) => {
                setTimeout(() => {
                    alertEl.style.transition = 'opacity 0.4s ease';
                    alertEl.style.opacity = '0';
                    setTimeout(() => alertEl.remove(), 400);
                }, 6000);
            });
        })();
    </script>
    @include('partials.global-forms')
</body>
</html>
