<form method="POST" action="{{ route('password.otp.verify') }}" class="login-form">
    @csrf
    <div class="mb-4">
        <label for="email" class="form-label">Super Admin Email</label>
        <input id="email" type="email" name="email" value="{{ old('email', session('password_reset_email')) }}" class="form-control @error('email') is-invalid @enderror" required>
        @error('email')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="mb-4">
        <label for="otp" class="form-label">6-Digit Reset Code</label>
        <input id="otp" type="text" name="otp" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" class="form-control @error('otp') is-invalid @enderror" required>
        @error('otp')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <button type="submit" class="btn btn-primary w-100">
        <i class="bi bi-shield-check"></i>
        Verify Code
    </button>
    <a href="{{ route('password.request') }}" class="auth-link">Request a new code</a>
</form>
