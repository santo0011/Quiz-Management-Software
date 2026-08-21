<form method="POST" action="{{ $action }}" class="admin-form">
    @csrf
    @isset($drawerId)
        <input type="hidden" name="_drawer" value="{{ $drawerId }}">
    @endisset
    @if ($method !== 'POST')
        @method($method)
    @endif

    @php($useOldInput = isset($drawerId) ? old('_drawer') === $drawerId : true)

    @if (isset($selectedBranch) && $selectedBranch)
        <div class="feedback-alert success mb-4">
            <i class="bi bi-building-check"></i>
            <div><strong>Active Branch:</strong> {{ $selectedBranch->name }}</div>
        </div>
    @else
        <div class="feedback-alert success mb-4">
            <i class="bi bi-globe2"></i>
            <div>This class will be available to <strong>all branches</strong> automatically.</div>
        </div>
    @endif

    <div class="mb-4">
        <label for="name" class="form-label">Class Name <span class="required-mark">*</span></label>
        <input id="name" type="text" name="name" value="{{ $useOldInput ? old('name', $class->name) : $class->name }}" class="form-control{{ $useOldInput && $errors->has('name') ? ' is-invalid' : '' }}" required maxlength="100">
        @if ($useOldInput)
            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
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
            <a href="{{ route('admin.classes.index') }}" class="btn btn-outline-secondary">Cancel</a>
        @endif
    </div>
</form>
