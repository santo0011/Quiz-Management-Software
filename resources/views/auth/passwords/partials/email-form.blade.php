<form method="POST" action="{{ route('password.email') }}" class="login-form">
    @csrf
    <div class="mb-4">
        <label for="email" class="form-label">Super Admin Email</label>
        <input id="email" type="email" name="email" value="{{ old('email') }}" class="form-control @error('email') is-invalid @enderror" required autofocus>
        @error('email')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <button type="submit" class="btn btn-primary w-100">
        <i class="bi bi-send-fill"></i>
        Send Reset Code
    </button>
    <a href="{{ route('login') }}" class="auth-link">Back to login</a>
</form>
