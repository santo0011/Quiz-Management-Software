<form method="POST" action="{{ $action }}" class="admin-form">
    @csrf
    @isset($drawerId)
        <input type="hidden" name="_drawer" value="{{ $drawerId }}">
    @endisset
    @if ($method !== 'POST')
        @method($method)
    @endif

    @php($useOldInput = isset($drawerId) ? old('_drawer') === $drawerId : true)

    <div class="mb-4">
        <label for="name{{ $fieldSuffix ?? '' }}" class="form-label">Teacher Name <span class="required-mark">*</span></label>
        <input id="name{{ $fieldSuffix ?? '' }}" type="text" name="name" value="{{ $useOldInput ? old('name', $teacher->name) : $teacher->name }}" class="form-control{{ $useOldInput && $errors->has('name') ? ' is-invalid' : '' }}" required maxlength="255">
        @if ($useOldInput)
            @error('name')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        @endif
    </div>

    <div class="mb-4">
        <label for="email{{ $fieldSuffix ?? '' }}" class="form-label">Email <span class="required-mark">*</span></label>
        <input id="email{{ $fieldSuffix ?? '' }}" type="email" name="email" value="{{ $useOldInput ? old('email', $teacher->email) : $teacher->email }}" class="form-control{{ $useOldInput && $errors->has('email') ? ' is-invalid' : '' }}" required maxlength="255">
        @if ($useOldInput)
            @error('email')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        @endif
        @if ($method === 'POST')
            <div class="form-text">A secure password will be generated and emailed to this address.</div>
        @endif
    </div>

    <div class="mb-4">
        <label for="phone_number{{ $fieldSuffix ?? '' }}" class="form-label">Phone Number <span class="required-mark">*</span></label>
        <input id="phone_number{{ $fieldSuffix ?? '' }}" type="text" name="phone_number" value="{{ $useOldInput ? old('phone_number', $teacher->phone_number) : $teacher->phone_number }}" class="form-control{{ $useOldInput && $errors->has('phone_number') ? ' is-invalid' : '' }}" required maxlength="30">
        @if ($useOldInput)
            @error('phone_number')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        @endif
    </div>

    <div class="d-flex gap-2 flex-wrap">
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-check-circle-fill"></i>
            {{ $button }}
        </button>
        @if ($drawer ?? false)
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="offcanvas">Cancel</button>
        @else
            <a href="{{ route('branch.teachers.index') }}" class="btn btn-outline-secondary">Cancel</a>
        @endif
    </div>
</form>
