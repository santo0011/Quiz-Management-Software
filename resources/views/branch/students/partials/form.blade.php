<form method="POST" action="{{ $action }}" class="admin-form">
    @csrf
    @isset($drawerId)
        <input type="hidden" name="_drawer" value="{{ $drawerId }}">
    @endisset
    @if ($method !== 'POST')
        @method($method)
    @endif

    @php($useOldInput = isset($drawerId) ? old('_drawer') === $drawerId : true)

    <div class="feedback-alert success mb-4">
        <i class="bi bi-building"></i>
        <div><strong>Branch:</strong> {{ $branch->name }}</div>
    </div>

    <div class="mb-3">
        <label for="branch_display" class="form-label">Branch <span class="required-mark">*</span></label>
        <input id="branch_display" type="text" class="form-control" value="{{ $branch->name }}" disabled>
    </div>

    <div class="row g-3">
        <div class="col-md-6">
            <label for="student_name" class="form-label">Student Name <span class="required-mark">*</span></label>
            <input id="student_name" type="text" name="student_name" value="{{ $useOldInput ? old('student_name', $student->student_name) : $student->student_name }}" class="form-control{{ $useOldInput && $errors->has('student_name') ? ' is-invalid' : '' }}" required maxlength="255">
            @if ($useOldInput)
                @error('student_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            @endif
        </div>
        <div class="col-md-6">
            <label for="guardian_name" class="form-label">Guardian Name <span class="required-mark">*</span></label>
            <input id="guardian_name" type="text" name="guardian_name" value="{{ $useOldInput ? old('guardian_name', $student->guardian_name) : $student->guardian_name }}" class="form-control{{ $useOldInput && $errors->has('guardian_name') ? ' is-invalid' : '' }}" required maxlength="255">
            @if ($useOldInput)
                @error('guardian_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            @endif
        </div>
        <div class="col-md-6">
            <label for="class_id" class="form-label">Class <span class="required-mark">*</span></label>
            <select id="class_id" name="class_id" class="form-select form-control{{ $useOldInput && $errors->has('class_id') ? ' is-invalid' : '' }}" required>
                <option value="">Select class</option>
                @foreach (($classes ?? \App\Models\SchoolClass::visibleToBranch($branch->id)->orderBy('name')->get()) as $class)
                    <option value="{{ $class->id }}" @selected(($useOldInput ? old('class_id', $student->class_id) : $student->class_id) == $class->id)>{{ $class->name }}</option>
                @endforeach
            </select>
            @if ($useOldInput)
                @error('class_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
            @endif
            @if (($classes ?? collect())->isEmpty())
                <div class="form-text">No classes found for this branch. Ask the Super Admin to add one first.</div>
            @endif
        </div>
        <div class="col-md-6">
            <label for="phone_number" class="form-label">Phone <span class="required-mark">*</span></label>
            <input id="phone_number" type="text" name="phone_number" value="{{ $useOldInput ? old('phone_number', $student->phone_number) : $student->phone_number }}" class="form-control{{ $useOldInput && $errors->has('phone_number') ? ' is-invalid' : '' }}" required maxlength="30">
            @if ($useOldInput)
                @error('phone_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
            @endif
        </div>
        <div class="col-12">
            <label for="email" class="form-label">Email <span class="required-mark">*</span></label>
            <input id="email" type="email" name="email" value="{{ $useOldInput ? old('email', $student->email) : $student->email }}" class="form-control{{ $useOldInput && $errors->has('email') ? ' is-invalid' : '' }}" required maxlength="255">
            @if ($useOldInput)
                @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
            @endif
            <div class="form-text">Student login password remains empty for now; email-based login codes can be added later.</div>
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
            <a href="{{ route('branch.students.index') }}" class="btn btn-outline-secondary">Cancel</a>
        @endif
    </div>
</form>
