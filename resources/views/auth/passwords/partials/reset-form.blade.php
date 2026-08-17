<form method="POST" action="{{ route('password.update') }}" class="login-form">
    @csrf
    <div class="mb-3">
        <label for="password" class="form-label">New Password</label>
        <div class="password-field">
            <input id="password" type="password" name="password" class="form-control @error('password') is-invalid @enderror" required>
            <button class="password-toggle" type="button" aria-label="Show password" data-password-toggle>
                <i class="bi bi-eye-fill"></i>
            </button>
        </div>
        @error('password')
            <div class="invalid-feedback d-block">{{ $message }}</div>
        @enderror
    </div>

    <div class="mb-4">
        <label for="password_confirmation" class="form-label">Confirm New Password</label>
        <div class="password-field">
            <input id="password_confirmation" type="password" name="password_confirmation" class="form-control" required>
            <button class="password-toggle" type="button" aria-label="Show password" data-password-toggle>
                <i class="bi bi-eye-fill"></i>
            </button>
        </div>
    </div>

    <button type="submit" class="btn btn-primary w-100" data-loading="Resetting...">
        <i class="bi bi-check-circle-fill"></i>
        Reset Password
    </button>
</form>