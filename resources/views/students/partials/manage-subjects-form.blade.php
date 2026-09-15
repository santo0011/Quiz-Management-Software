@php($useOldInput = isset($drawerId) ? old('_drawer') === $drawerId : true)
@php($selectedSubjectIds = $useOldInput ? old('subject_ids', $student->subjects->pluck('id')->all()) : $student->subjects->pluck('id')->all())

<form method="POST" action="{{ $action }}" class="admin-form">
    @csrf
    @method('PUT')
    @isset($drawerId)
        <input type="hidden" name="_drawer" value="{{ $drawerId }}">
    @endisset

    <div class="feedback-alert info mb-3">
        <i class="bi bi-info-circle-fill"></i>
        <div>Only this Student's Subject assignments are changed here. Their name, Grade, Zoho ID, and other core details always come from Zoho and are never edited from this system.</div>
    </div>

    @if (($subjects ?? collect())->isEmpty())
        <div class="form-text">No subjects found. Add one from Subjects first.</div>
    @else
        <div class="subject-checkbox-grid">
            @foreach ($subjects as $subject)
                <label class="form-check module-check">
                    <input type="checkbox" name="subject_ids[]" value="{{ $subject->id }}" class="form-check-input" @checked(in_array($subject->id, $selectedSubjectIds))>
                    <span>{{ $subject->name }}</span>
                </label>
            @endforeach
        </div>
    @endif
    @if ($useOldInput)
        @error('subject_ids')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
    @endif

    <div class="d-flex gap-2 flex-wrap mt-4">
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-check-circle-fill"></i>
            Save Subjects
        </button>
        @if ($drawer ?? false)
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="offcanvas">Cancel</button>
        @endif
    </div>
</form>
