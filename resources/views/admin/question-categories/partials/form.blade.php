<form method="POST" action="{{ $action }}" class="admin-form">
    @csrf
    @isset($drawerId)
        <input type="hidden" name="_drawer" value="{{ $drawerId }}">
    @endisset
    @if ($method !== 'POST')
        @method($method)
    @endif

    @php($useOldInput = isset($drawerId) ? old('_drawer') === $drawerId : true)
    @php($isNewCategory = $method === 'POST')

    @if ($isNewCategory)
        <div class="feedback-alert success mb-4">
            <i class="bi bi-globe2"></i>
            <div>This category will be available to <strong>all branches</strong> automatically.</div>
        </div>
    @elseif (isset($selectedBranch) && $selectedBranch)
        <input type="hidden" name="branch_id" value="{{ $selectedBranch->id }}">

        <div class="feedback-alert success mb-4">
            <i class="bi bi-building-check"></i>
            <div><strong>Branch:</strong> {{ $selectedBranch->name }}</div>
        </div>
    @else
        <div class="feedback-alert success mb-4">
            <i class="bi bi-globe2"></i>
            <div>This category is available to <strong>all branches</strong> automatically.</div>
        </div>
    @endif

    <div class="mb-4">
        <label for="name" class="form-label">Category Name <span class="required-mark">*</span></label>
        <input id="name" type="text" name="name" value="{{ $useOldInput ? old('name', $category->name) : $category->name }}" class="form-control{{ $useOldInput && $errors->has('name') ? ' is-invalid' : '' }}" required maxlength="100">
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
            <a href="{{ route('admin.question-categories.index') }}" class="btn btn-outline-secondary">Cancel</a>
        @endif
    </div>
</form>
