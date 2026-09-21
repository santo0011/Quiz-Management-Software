<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Login - {{ config('app.name') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="{{ asset('css/admin.css') }}?v={{ filemtime(public_path('css/admin.css')) }}" rel="stylesheet">
</head>
@php($selectedType = old('login_type', 'super_admin'))
<body class="login-body">
    <main class="login-wrap unified-login-wrap">
        <div class="unified-login-shell">
            <aside class="login-hero d-none d-lg-flex">
                <div class="login-hero-glow"></div>
                <div class="login-hero-inner">
                    @include('partials.auth-brand', ['subtitle' => 'Quiz Management Software'])

                    <h2 class="login-hero-title">One secure sign-in for everyone.</h2>
                    <p class="login-hero-subtitle">Select your role to continue.</p>

                    <div class="login-hero-role-select" role="tablist" aria-label="Login type">
                        <button type="button" class="login-hero-role-btn" data-login-type-button="super_admin" role="tab">
                            <span class="login-hero-role-icon"><i class="bi bi-shield-lock-fill"></i></span>
                            <span class="login-hero-role-copy">
                                <strong>Super Admin</strong>
                                <span>Full system control</span>
                            </span>
                            <i class="bi bi-chevron-right login-hero-role-arrow"></i>
                        </button>
                        <button type="button" class="login-hero-role-btn" data-login-type-button="branch" role="tab">
                            <span class="login-hero-role-icon"><i class="bi bi-building-fill"></i></span>
                            <span class="login-hero-role-copy">
                                <strong>Branch</strong>
                                <span>Manage your center</span>
                            </span>
                            <i class="bi bi-chevron-right login-hero-role-arrow"></i>
                        </button>
                        <button type="button" class="login-hero-role-btn" data-login-type-button="student" role="tab">
                            <span class="login-hero-role-icon"><i class="bi bi-mortarboard-fill"></i></span>
                            <span class="login-hero-role-copy">
                                <strong>Student</strong>
                                <span>Take exams &amp; view results</span>
                            </span>
                            <i class="bi bi-chevron-right login-hero-role-arrow"></i>
                        </button>
                    </div>
                </div>
            </aside>

        <section class="login-panel unified-login-panel">
            @include('partials.auth-brand', ['class' => 'login-brand-wide d-lg-none', 'subtitle' => 'Quiz Management Software'])

            <div class="login-type-selector-mobile d-lg-none" role="tablist" aria-label="Login type">
                <button type="button" class="login-type-option" data-login-type-button="super_admin" role="tab">
                    <i class="bi bi-shield-lock-fill"></i>
                    <span>Super Admin</span>
                </button>
                <button type="button" class="login-type-option" data-login-type-button="branch" role="tab">
                    <i class="bi bi-building-fill"></i>
                    <span>Branch</span>
                </button>
                <button type="button" class="login-type-option" data-login-type-button="student" role="tab">
                    <i class="bi bi-mortarboard-fill"></i>
                    <span>Student</span>
                </button>
            </div>

            <div class="login-heading">
                <span id="loginKicker">Super Admin Portal</span>
                <h1 id="loginTitle">Super Admin Login</h1>
                <p id="loginSubtitle">Sign in to manage the Quiz Management System</p>
            </div>

            @if (session('login_error'))
                <div class="alert alert-danger feedback-alert" role="alert" data-auto-dismiss>
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    <span>{{ session('login_error') }}</span>
                </div>
            @endif

            @if (session('login_success'))
                <div class="alert alert-success feedback-alert success" role="alert" data-auto-dismiss>
                    <i class="bi bi-check-circle-fill"></i>
                    <span>{{ session('login_success') }}</span>
                </div>
            @endif

            <form method="POST" action="{{ route('login.store') }}" class="login-form" id="loginForm">
                @csrf
                <input type="hidden" name="login_type" id="loginType" value="{{ $selectedType }}">

                @error('login_type')
                    <div class="feedback-alert mb-3">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                        <span>{{ $message }}</span>
                    </div>
                @enderror

                <div class="mb-3" id="emailGroup">
                    <label for="email" class="form-label" id="emailLabel">Email</label>
                    <div class="login-input-wrap">
                        <i class="bi bi-envelope-fill"></i>
                        <input id="email" type="email" name="email" value="{{ old('email', old('nrich_student_id')) }}" class="form-control @error('email') is-invalid @enderror @error('nrich_student_id') is-invalid @enderror" required autofocus placeholder="Enter your email">
                    </div>
                    @error('email')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                    @error('nrich_student_id')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3" id="passwordGroup">
                    <label for="password" class="form-label" id="passwordLabel">Password</label>
                    <div class="password-field login-input-wrap">
                        <i class="bi bi-key-fill"></i>
                        <input id="password" type="password" name="password" class="form-control @error('password') is-invalid @enderror" required placeholder="Enter your password">
                        <button class="password-toggle" type="button" aria-label="Show password" aria-pressed="false" data-password-toggle>
                            <i class="bi bi-eye-fill"></i>
                        </button>
                    </div>
                    @error('password')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                <div class="login-form-row">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="remember" id="remember">
                        <label class="form-check-label" for="remember" id="rememberLabel">Remember me</label>
                    </div>
                    <a href="{{ route('password.request', ['type' => $selectedType]) }}" class="auth-inline-link" id="forgotPasswordLink">Forgot password?</a>
                </div>

                <button type="submit" class="btn btn-primary w-100 login-submit" id="loginButton" data-loading="Logging in...">
                    <i class="bi bi-shield-lock-fill" id="loginButtonIcon"></i>
                    <span>Login as Super Admin</span>
                    <span class="spinner-border spinner-border-sm d-none ms-2" id="loginButtonSpinner" role="status" aria-hidden="true"></span>
                </button>
            </form>
        </section>
        </div>
    </main>
    <script>
        const loginConfigs = {
            super_admin: {
                kicker: 'Super Admin Portal',
                title: 'Super Admin Login',
                subtitle: 'Sign in to manage the Quiz Management System',
                emailLabel: 'Email',
                emailPlaceholder: 'superadmin@example.com',
                identifierName: 'email',
                identifierType: 'email',
                passwordLabel: 'Password',
                passwordPlaceholder: 'Enter your password',
                requiresPassword: true,
                button: 'Login as Super Admin',
                icon: 'bi bi-shield-lock-fill',
                forgot: true,
                rememberLabel: 'Remember me',
                rememberFieldName: 'remember',
            },
            branch: {
                kicker: 'Branch Workspace',
                title: 'Branch Login',
                subtitle: 'Sign in to manage your branch',
                emailLabel: 'Branch Email',
                emailPlaceholder: 'branch@example.com',
                identifierName: 'email',
                identifierType: 'email',
                passwordLabel: 'Password',
                passwordPlaceholder: 'Enter your branch password',
                requiresPassword: true,
                button: 'Login as Branch',
                icon: 'bi bi-building-fill',
                forgot: true,
                rememberLabel: 'Remember me',
                rememberFieldName: 'remember',
            },
            student: {
                kicker: 'Student Portal',
                title: 'Student Login',
                subtitle: 'Enter your NRICH Student ID to continue',
                emailLabel: 'NRICH Student ID',
                emailPlaceholder: 'e.g. NL1184',
                identifierName: 'nrich_student_id',
                identifierType: 'text',
                passwordLabel: 'Teacher Override Password',
                passwordPlaceholder: 'Enter the common Teacher Override password',
                requiresPassword: false,
                button: 'Login',
                buttonOverride: 'Login',
                icon: 'bi bi-mortarboard-fill',
                forgot: false,
                rememberLabel: 'Teacher Override',
                rememberFieldName: 'teacher_override',
            },
        };

        const LOGIN_TYPE_KEY = 'quizcore.login.type';
        const savedLoginType = localStorage.getItem(LOGIN_TYPE_KEY);
        const validTypes = ['super_admin', 'branch', 'student'];
        const initialType = validTypes.includes(savedLoginType) ? savedLoginType : 'super_admin';

        const typeInput = document.getElementById('loginType');
        const buttons = document.querySelectorAll('[data-login-type-button]');
        const email = document.getElementById('email');
        const password = document.getElementById('password');
        const passwordGroup = document.getElementById('passwordGroup');
        const loginButton = document.getElementById('loginButton');
        const forgotPasswordLink = document.getElementById('forgotPasswordLink');
        const remember = document.getElementById('remember');
        let isSubmitting = false;

        // For every type except Student, the password field's visibility is
        // fixed (config.requiresPassword). For Student, the same checkbox
        // that is "Remember me" elsewhere becomes "Teacher Override", and
        // checking it is what reveals the (otherwise hidden) password field
        // for the common override password.
        function applyPasswordVisibility(type) {
            const config = loginConfigs[type] || loginConfigs.super_admin;
            const show = type === 'student' ? remember.checked : config.requiresPassword;

            passwordGroup.classList.toggle('d-none', !show);
            password.required = show;
            password.disabled = !show;
            if (!show) {
                password.value = '';
            }
        }

        // Student's submit button reads "Login" either way — normally, or
        // once Teacher Override is checked (which skips the OTP step
        // entirely). Every other type keeps its fixed label regardless of
        // the checkbox.
        function currentButtonText(type) {
            const config = loginConfigs[type] || loginConfigs.super_admin;

            if (type === 'student' && remember.checked) {
                return config.buttonOverride || config.button;
            }

            return config.button;
        }

        function updateLoginButtonLabel(type) {
            loginButton.querySelector('span').textContent = currentButtonText(type);
        }

        function setLoginButtonLoading(loading) {
            const icon = document.getElementById('loginButtonIcon');
            const spinner = document.getElementById('loginButtonSpinner');
            const label = loginButton.querySelector('span');
            const loadingText = loginButton.dataset.loading || 'Logging in...';
            const normalText = currentButtonText(typeInput.value);

            loginButton.disabled = loading;
            icon.classList.toggle('d-none', loading);
            spinner.classList.toggle('d-none', !loading);
            label.textContent = loading ? loadingText : normalText;
        }

        function restoreLoginButton() {
            isSubmitting = false;
            setLoginButtonLoading(false);
        }

        function setLoginType(type) {
            const config = loginConfigs[type] || loginConfigs.super_admin;

            typeInput.value = type;
            localStorage.setItem(LOGIN_TYPE_KEY, type);
            document.getElementById('loginKicker').textContent = config.kicker;
            document.getElementById('loginTitle').textContent = config.title;
            document.getElementById('loginSubtitle').textContent = config.subtitle;
            document.getElementById('emailLabel').textContent = config.emailLabel;
            document.getElementById('passwordLabel').textContent = config.passwordLabel;
            document.getElementById('rememberLabel').textContent = config.rememberLabel;
            document.getElementById('loginButtonIcon').className = config.icon;
            email.name = config.identifierName;
            email.type = config.identifierType;
            email.placeholder = config.emailPlaceholder;
            password.placeholder = config.passwordPlaceholder;
            forgotPasswordLink.classList.toggle('d-none', !config.forgot);
            const baseUrl = @json(route('password.request'));
            forgotPasswordLink.href = baseUrl + (baseUrl.includes('?') ? '&' : '?') + 'type=' + type;

            remember.name = config.rememberFieldName;
            remember.checked = false;
            applyPasswordVisibility(type);
            updateLoginButtonLabel(type);

            // Reset login button loading state when switching types
            const spinner = document.getElementById('loginButtonSpinner');
            const icon = document.getElementById('loginButtonIcon');
            isSubmitting = false;
            loginButton.disabled = false;
            icon.classList.remove('d-none');
            spinner.classList.add('d-none');

            buttons.forEach((button) => {
                const isActive = button.dataset.loginTypeButton === type;
                button.classList.toggle('active', isActive);
                button.setAttribute('aria-selected', String(isActive));
            });
        }

        buttons.forEach((button) => {
            button.addEventListener('click', () => setLoginType(button.dataset.loginTypeButton));
        });

        remember.addEventListener('change', () => {
            applyPasswordVisibility(typeInput.value);
            updateLoginButtonLabel(typeInput.value);
        });

        setLoginType(initialType);

        document.getElementById('loginForm').addEventListener('submit', (event) => {
            if (isSubmitting) {
                event.preventDefault();
                return;
            }

            isSubmitting = true;
            setLoginButtonLoading(true);
        });

        document.querySelectorAll('[data-password-toggle]').forEach((button) => {
            button.addEventListener('click', () => {
                const input = button.closest('.password-field').querySelector('input');
                const icon = button.querySelector('i');
                const showPassword = input.type === 'password';

                input.type = showPassword ? 'text' : 'password';
                button.setAttribute('aria-label', showPassword ? 'Hide password' : 'Show password');
                button.setAttribute('aria-pressed', String(showPassword));
                icon.className = showPassword ? 'bi bi-eye-slash-fill' : 'bi bi-eye-fill';
            });
        });

        window.addEventListener('pageshow', () => {
            restoreLoginButton();
        });

        document.querySelectorAll('[data-auto-dismiss]').forEach((alertEl) => {
            setTimeout(() => {
                alertEl.style.transition = 'opacity 0.4s ease';
                alertEl.style.opacity = '0';
                setTimeout(() => alertEl.remove(), 400);
            }, 6000);
        });
    </script>
</body>
</html>
