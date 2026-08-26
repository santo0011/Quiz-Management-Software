<form method="POST" action="{{ $action }}" class="admin-form">
    @csrf
    @isset($drawerId)
        <input type="hidden" name="_drawer" value="{{ $drawerId }}">
    @endisset
    @if ($method !== 'POST')
        @method($method)
    @endif

    @php($useOldInput = isset($drawerId) ? old('_drawer') === $drawerId : true)

    <div class="row g-3">
        <div class="col-12">
            <label for="name" class="form-label">Session Name <span class="required-mark">*</span></label>
            <input id="name" type="text" name="name" value="{{ $useOldInput ? old('name', $academicSession->name) : $academicSession->name }}" class="form-control{{ $useOldInput && $errors->has('name') ? ' is-invalid' : '' }}" required maxlength="100" placeholder="e.g. 2026-2027">
            @if ($useOldInput)
                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            @endif
        </div>
        <div class="col-md-6">
            <label for="start_date" class="form-label">Start Date <span class="required-mark">*</span></label>
            <input id="start_date" type="date" name="start_date" value="{{ $useOldInput ? old('start_date', $academicSession->start_date?->format('Y-m-d')) : $academicSession->start_date?->format('Y-m-d') }}" class="form-control{{ $useOldInput && $errors->has('start_date') ? ' is-invalid' : '' }}" required>
            @if ($useOldInput)
                @error('start_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
            @endif
        </div>
        <div class="col-md-6">
            <label for="end_date" class="form-label">End Date <span class="required-mark">*</span></label>
            <input id="end_date" type="date" name="end_date" value="{{ $useOldInput ? old('end_date', $academicSession->end_date?->format('Y-m-d')) : $academicSession->end_date?->format('Y-m-d') }}" class="form-control{{ $useOldInput && $errors->has('end_date') ? ' is-invalid' : '' }}" required>
            @if ($useOldInput)
                @error('end_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
            @endif
        </div>
        <div class="col-12">
            <label for="description" class="form-label">Description</label>
            <textarea id="description" name="description" rows="3" class="form-control{{ $useOldInput && $errors->has('description') ? ' is-invalid' : '' }}" maxlength="2000">{{ $useOldInput ? old('description', $academicSession->description) : $academicSession->description }}</textarea>
            @if ($useOldInput)
                @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
            @endif
        </div>
        <div class="col-12">
            <label class="form-check module-check">
                <input type="checkbox" name="is_active" value="1" class="form-check-input" @checked($useOldInput ? old('is_active', $academicSession->is_active) : $academicSession->is_active)>
                <span>Active (open for creating students and exams)</span>
            </label>
        </div>
    </div>

    <div class="d-flex gap-2 flex-wrap mt-4">
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-check-circle-fill"></i>
            {{ $button }}
        </button>
        @if ($drawer ?? false)
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="offcanvas">Cancel</button>
        @else
            <a href="{{ route('admin.academic-sessions.index') }}" class="btn btn-outline-secondary">Cancel</a>
        @endif
    </div>
</form>
