@php($fieldSuffix = $fieldSuffix ?? '')
@php($useOldInput = isset($drawerId) ? old('_drawer') === $drawerId : true)
@php($hasPasswordError = $useOldInput && $errors->has('password'))

<section class="exam-form-section change-password-card mt-4">
    <div class="exam-form-section-title">
        <span><i class="bi bi-shield-lock-fill"></i></span>
        <div>
            <h3>Change Password</h3>
            <p class="text-muted small mb-0">Set a new login password for this account.</p>
        </div>
    </div>

    <form method="POST" action="{{ $action }}" class="password-change-form">
        @csrf
        @method('PUT')
        @isset($drawerId)
            <input type="hidden" name="_drawer" value="{{ $drawerId }}">
        @endisset

        @if ($hasPasswordError)
            <div class="feedback-alert mb-3">
                <i class="bi bi-exclamation-triangle-fill"></i>
                <span>{{ $errors->first('password') }}</span>
            </div>
        @endif

        <div class="row g-3">
            <div class="col-md-6">
                <label for="password{{ $fieldSuffix }}" class="form-label">New Password <span class="required-mark">*</span></label>
                <input id="password{{ $fieldSuffix }}" type="password" name="password" class="form-control{{ $hasPasswordError ? ' is-invalid' : '' }}" required minlength="6" autocomplete="new-password" placeholder="At least 6 characters">
            </div>
            <div class="col-md-6">
                <label for="password_confirmation{{ $fieldSuffix }}" class="form-label">Confirm Password <span class="required-mark">*</span></label>
                <input id="password_confirmation{{ $fieldSuffix }}" type="password" name="password_confirmation" class="form-control{{ $hasPasswordError ? ' is-invalid' : '' }}" required minlength="6" autocomplete="new-password" placeholder="Re-enter password">
            </div>
        </div>

        <div class="d-flex gap-2 flex-wrap mt-3">
            <button type="submit" class="btn btn-soft">
                <i class="bi bi-key-fill"></i>
                Update Password
            </button>
        </div>
    </form>
</section>
