<form method="POST" action="{{ route('password.email') }}" class="login-form">
    @csrf
    <input type="hidden" name="type" value="{{ $type }}">
    <div class="mb-4">
        <label for="email" class="form-label">{{ $config['label'] }}</label>
        <input id="email" type="email" name="email" value="{{ old('email') }}" class="form-control @error('email') is-invalid @enderror" required autofocus placeholder="{{ $config['placeholder'] }}">
        @error('email')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <button type="submit" class="btn btn-primary w-100" data-loading="Sending...">
        <i class="bi bi-send-fill"></i>
        Send Reset Code
    </button>
    <a href="{{ route('login') }}" class="auth-link">Back to login</a>
</form>