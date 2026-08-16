<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Login - {{ config('app.name') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="{{ asset('css/admin.css') }}" rel="stylesheet">
</head>
@php($selectedType = old('login_type', 'super_admin'))
<body class="login-body">
    <main class="login-wrap unified-login-wrap">
        <section class="login-panel unified-login-panel">
            <div class="login-brand login-brand-wide">
                <div class="brand-mark">Q</div>
                <div>
                    <strong>QuizCore</strong>
                    <span>Quiz Management Software</span>
                </div>
            </div>

            <div class="login-type-selector" role="tablist" aria-label="Login type">
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
                <div class="alert alert-danger feedback-alert" role="alert">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    <span>{{ session('login_error') }}</span>
                </div>
            @endif

            @if (session('login_success'))
                <div class="alert alert-success feedback-alert success" role="alert">
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
                        <input id="email" type="email" name="email" value="{{ old('email') }}" class="form-control @error('email') is-invalid @enderror" required autofocus placeholder="Enter your email">
                    </div>
                    @error('email')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                <div class="feedback-alert success mb-3 d-none" id="studentSuccess" role="status">
                    <i class="bi bi-check-circle-fill"></i>
                    <span></span>
                </div>

                <div class="alert alert-danger feedback-alert d-none" id="studentError" role="alert">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    <span></span>
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

                <div class="d-none" id="otpGroup">
                    <div class="mb-3">
                        <label for="studentOtp" class="form-label">6-Digit OTP</label>
                        <div class="login-input-wrap">
                            <i class="bi bi-123"></i>
                            <input id="studentOtp" type="text" inputmode="numeric" autocomplete="one-time-code" class="form-control" maxlength="6" placeholder="Enter OTP">
                        </div>
                    </div>
                    <button type="button" class="btn btn-soft w-100 mb-3" id="verifyOtpButton">
                        <i class="bi bi-shield-check"></i>
                        Verify OTP
                    </button>
                </div>

                <div class="d-none" id="createPasswordGroup">
                    <div class="mb-3">
                        <label for="newStudentPassword" class="form-label">Create Password</label>
                        <div class="password-field login-input-wrap">
                            <i class="bi bi-key-fill"></i>
                            <input id="newStudentPassword" type="password" class="form-control" minlength="8" placeholder="Create a secure password">
                            <button class="password-toggle" type="button" aria-label="Show password" aria-pressed="false" data-password-toggle>
                                <i class="bi bi-eye-fill"></i>
                            </button>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="newStudentPasswordConfirmation" class="form-label">Confirm Password</label>
                        <div class="password-field login-input-wrap">
                            <i class="bi bi-key-fill"></i>
                            <input id="newStudentPasswordConfirmation" type="password" class="form-control" minlength="8" placeholder="Confirm your password">
                            <button class="password-toggle" type="button" aria-label="Show password" aria-pressed="false" data-password-toggle>
                                <i class="bi bi-eye-fill"></i>
                            </button>
                        </div>
                    </div>
                    <button type="button" class="btn btn-primary w-100 mb-3" id="createPasswordButton">
                        <i class="bi bi-check-circle-fill"></i>
                        Create Password & Login
                    </button>
                </div>

                <div class="login-form-row">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="remember" id="remember">
                        <label class="form-check-label" for="remember">Remember me</label>
                    </div>
                    <a href="{{ route('password.request') }}" class="auth-inline-link" id="forgotPasswordLink">Forgot password?</a>
                </div>

                <button type="button" class="btn btn-primary w-100 login-submit d-none" id="studentContinueButton">
                    <i class="bi bi-arrow-right-circle-fill"></i>
                    <span>Continue</span>
                </button>

                <button type="button" class="btn btn-primary w-100 login-submit d-none" id="sendOtpButton">
                    <i class="bi bi-envelope-check-fill"></i>
                    <span>Send OTP & Create Password</span>
                </button>

                <button type="submit" class="btn btn-primary w-100 login-submit" id="loginButton">
                    <i class="bi bi-shield-lock-fill" id="loginButtonIcon"></i>
                    <span>Login as Super Admin</span>
                </button>
            </form>
        </section>
    </main>
    <script>
        const loginConfigs = {
            super_admin: {
                kicker: 'Super Admin Portal',
                title: 'Super Admin Login',
                subtitle: 'Sign in to manage the Quiz Management System',
                emailLabel: 'Email',
                emailPlaceholder: 'superadmin@example.com',
                passwordLabel: 'Password',
                passwordPlaceholder: 'Enter your password',
                button: 'Login as Super Admin',
                icon: 'bi bi-shield-lock-fill',
                forgot: true,
            },
            branch: {
                kicker: 'Branch Workspace',
                title: 'Branch Login',
                subtitle: 'Sign in to manage your branch',
                emailLabel: 'Branch Email',
                emailPlaceholder: 'branch@example.com',
                passwordLabel: 'Password',
                passwordPlaceholder: 'Enter your branch password',
                button: 'Login as Branch',
                icon: 'bi bi-building-fill',
                forgot: true,
            },
            student: {
                kicker: 'Student Portal',
                title: 'Student Login',
                subtitle: 'Enter your email first to continue securely',
                emailLabel: 'Student Email',
                emailPlaceholder: 'student@example.com',
                passwordLabel: 'Password',
                passwordPlaceholder: 'Enter your password',
                button: 'Login as Student',
                icon: 'bi bi-mortarboard-fill',
                forgot: true,
            },
        };

        const typeInput = document.getElementById('loginType');
        const buttons = document.querySelectorAll('[data-login-type-button]');
        const email = document.getElementById('email');
        const password = document.getElementById('password');
        const passwordGroup = document.getElementById('passwordGroup');
        const loginButton = document.getElementById('loginButton');
        const studentContinueButton = document.getElementById('studentContinueButton');
        const sendOtpButton = document.getElementById('sendOtpButton');
        const otpGroup = document.getElementById('otpGroup');
        const studentOtp = document.getElementById('studentOtp');
        const verifyOtpButton = document.getElementById('verifyOtpButton');
        const createPasswordGroup = document.getElementById('createPasswordGroup');
        const newStudentPassword = document.getElementById('newStudentPassword');
        const newStudentPasswordConfirmation = document.getElementById('newStudentPasswordConfirmation');
        const createPasswordButton = document.getElementById('createPasswordButton');
        const studentSuccess = document.getElementById('studentSuccess');
        const studentError = document.getElementById('studentError');
        const forgotPasswordLink = document.getElementById('forgotPasswordLink');
        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
        const endpoints = {
            checkEmail: @json(route('student-login.check-email')),
            sendOtp: @json(route('student-login.send-otp')),
            verifyOtp: @json(route('student-login.verify-otp')),
            createPassword: @json(route('student-login.create-password')),
        };

        function clearStudentMessages() {
            studentSuccess.classList.add('d-none');
            studentSuccess.querySelector('span').textContent = '';
            studentError.classList.add('d-none');
            studentError.querySelector('span').textContent = '';
        }

        function showStudentMessage(type, message) {
            const target = type === 'success' ? studentSuccess : studentError;
            const other = type === 'success' ? studentError : studentSuccess;

            other.classList.add('d-none');
            target.querySelector('span').textContent = message;
            target.classList.remove('d-none');
        }

        function setLoading(button, loading, label) {
            button.disabled = loading;
            if (label) {
                button.querySelector('span').textContent = loading ? 'Please wait...' : label;
            }
        }

        function resetStudentFlow() {
            clearStudentMessages();
            passwordGroup.classList.add('d-none');
            otpGroup.classList.add('d-none');
            createPasswordGroup.classList.add('d-none');
            sendOtpButton.classList.add('d-none');
            loginButton.classList.add('d-none');
            studentContinueButton.classList.remove('d-none');
            password.required = false;
            password.value = '';
            password.disabled = true;
            loginButton.disabled = true;
        }

        async function postJson(url, payload) {
            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify(payload),
            });

            const data = await response.json().catch(() => ({}));

            if (!response.ok) {
                const errors = data.errors || {};
                const firstError = Object.values(errors).flat()[0] || data.message || 'Something went wrong. Please try again.';
                throw new Error(firstError);
            }

            return data;
        }

        async function checkStudentEmail() {
            clearStudentMessages();
            setLoading(studentContinueButton, true, 'Continue');

            try {
                const data = await postJson(endpoints.checkEmail, { email: email.value });
                showStudentMessage('success', data.message);

                studentContinueButton.classList.add('d-none');
                otpGroup.classList.add('d-none');
                createPasswordGroup.classList.add('d-none');

                if (data.status === 'password_required') {
                    passwordGroup.classList.remove('d-none');
                    loginButton.classList.remove('d-none');
                    sendOtpButton.classList.add('d-none');
                    password.disabled = false;
                    password.required = true;
                    loginButton.disabled = password.value.trim() === '';
                    password.focus();
                } else {
                    passwordGroup.classList.add('d-none');
                    loginButton.classList.add('d-none');
                    sendOtpButton.classList.remove('d-none');
                    password.disabled = true;
                    password.required = false;
                }
            } catch (error) {
                resetStudentFlow();
                showStudentMessage('error', error.message);
            } finally {
                setLoading(studentContinueButton, false, 'Continue');
            }
        }

        async function sendStudentOtp() {
            clearStudentMessages();
            setLoading(sendOtpButton, true, 'Send OTP & Create Password');

            try {
                const data = await postJson(endpoints.sendOtp, { email: email.value });
                showStudentMessage('success', data.message);
                sendOtpButton.classList.add('d-none');
                otpGroup.classList.remove('d-none');
                createPasswordGroup.classList.add('d-none');
                studentOtp.focus();
            } catch (error) {
                showStudentMessage('error', error.message);
            } finally {
                setLoading(sendOtpButton, false, 'Send OTP & Create Password');
            }
        }

        async function verifyStudentOtp() {
            clearStudentMessages();
            verifyOtpButton.disabled = true;

            try {
                const data = await postJson(endpoints.verifyOtp, {
                    email: email.value,
                    otp: studentOtp.value,
                });
                showStudentMessage('success', data.message);
                otpGroup.classList.add('d-none');
                createPasswordGroup.classList.remove('d-none');
                newStudentPassword.focus();
            } catch (error) {
                showStudentMessage('error', error.message);
            } finally {
                verifyOtpButton.disabled = false;
            }
        }

        async function createStudentPassword() {
            clearStudentMessages();
            createPasswordButton.disabled = true;

            try {
                const data = await postJson(endpoints.createPassword, {
                    password: newStudentPassword.value,
                    password_confirmation: newStudentPasswordConfirmation.value,
                });
                showStudentMessage('success', data.message);
                window.location.href = data.redirect;
            } catch (error) {
                showStudentMessage('error', error.message);
                createPasswordButton.disabled = false;
            }
        }

        function setLoginType(type) {
            const config = loginConfigs[type] || loginConfigs.super_admin;

            typeInput.value = type;
            document.getElementById('loginKicker').textContent = config.kicker;
            document.getElementById('loginTitle').textContent = config.title;
            document.getElementById('loginSubtitle').textContent = config.subtitle;
            document.getElementById('emailLabel').textContent = config.emailLabel;
            document.getElementById('passwordLabel').textContent = config.passwordLabel;
            document.getElementById('loginButtonIcon').className = config.icon;
            document.querySelector('#loginButton span').textContent = config.button;
            email.placeholder = config.emailPlaceholder;
            password.placeholder = config.passwordPlaceholder;
            forgotPasswordLink.classList.toggle('d-none', !config.forgot);

            if (type === 'student') {
                resetStudentFlow();
            } else {
                clearStudentMessages();
                passwordGroup.classList.remove('d-none');
                otpGroup.classList.add('d-none');
                createPasswordGroup.classList.add('d-none');
                studentContinueButton.classList.add('d-none');
                sendOtpButton.classList.add('d-none');
                loginButton.classList.remove('d-none');
                loginButton.disabled = false;
                password.disabled = false;
                password.required = true;
            }

            buttons.forEach((button) => {
                const isActive = button.dataset.loginTypeButton === type;
                button.classList.toggle('active', isActive);
                button.setAttribute('aria-selected', String(isActive));
            });
        }

        buttons.forEach((button) => {
            button.addEventListener('click', () => setLoginType(button.dataset.loginTypeButton));
        });

        setLoginType(typeInput.value);

        email.addEventListener('input', () => {
            if (typeInput.value === 'student') {
                resetStudentFlow();
            }
        });

        password.addEventListener('input', () => {
            if (typeInput.value === 'student') {
                loginButton.disabled = password.value.trim() === '';
            }
        });

        studentContinueButton.addEventListener('click', checkStudentEmail);
        sendOtpButton.addEventListener('click', sendStudentOtp);
        verifyOtpButton.addEventListener('click', verifyStudentOtp);
        createPasswordButton.addEventListener('click', createStudentPassword);

        document.getElementById('loginForm').addEventListener('submit', (event) => {
            if (typeInput.value === 'student' && passwordGroup.classList.contains('d-none')) {
                event.preventDefault();
                checkStudentEmail();
            }
        });

        forgotPasswordLink.addEventListener('click', (event) => {
            if (typeInput.value !== 'student') {
                return;
            }

            event.preventDefault();
            if (!email.value.trim()) {
                resetStudentFlow();
                showStudentMessage('error', 'Please enter your student email address first.');
                email.focus();
                return;
            }

            passwordGroup.classList.add('d-none');
            loginButton.classList.add('d-none');
            studentContinueButton.classList.add('d-none');
            sendOtpButton.classList.remove('d-none');
            sendStudentOtp();
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

        if (typeInput.value === 'student' && email.value.trim()) {
            checkStudentEmail();
        }
    </script>
</body>
</html>
