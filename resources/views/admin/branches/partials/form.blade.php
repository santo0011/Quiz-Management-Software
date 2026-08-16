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
        <label for="name" class="form-label">Branch Name <span class="required-mark">*</span></label>
        <input id="name" type="text" name="name" value="{{ $useOldInput ? old('name', $branch->name) : $branch->name }}" class="form-control{{ $useOldInput && $errors->has('name') ? ' is-invalid' : '' }}" required maxlength="255">
        @if ($useOldInput)
            @error('name')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        @endif
    </div>

    <div class="mb-4">
        <label for="email" class="form-label">Branch Email <span class="required-mark">*</span></label>
        <input id="email" type="email" name="email" value="{{ $useOldInput ? old('email', $branch->email) : $branch->email }}" class="form-control{{ $useOldInput && $errors->has('email') ? ' is-invalid' : '' }}" required maxlength="255">
        @if ($useOldInput)
            @error('email')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        @endif
        @if ($method === 'POST')
            <div class="form-text">A secure 6-digit login code will be emailed to this address.</div>
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
            <a href="{{ route('admin.branches.index') }}" class="btn btn-outline-secondary">Cancel</a>
        @endif
    </div>
</form>
