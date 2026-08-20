@php($prefix = $prefix ?? 'admin')
@php($contextBranch = $selectedBranch ?? $branch ?? null)
@php($isPublished = $exam->exists && $exam->isPublished())
@php($isSuperAdmin = auth()->user()->role === 'Super Admin')
@php($isNewExam = ! $exam->exists)

@if ($isPublished)
    <div class="feedback-alert info mb-4">
        <i class="bi bi-lock-fill"></i>
        <div>
            <strong>This exam is published and locked.</strong>
            <p class="mb-0">Published exams cannot be edited or deleted. The publish restriction is permanent.</p>
        </div>
    </div>
@endif

<form method="POST" action="{{ $action }}" class="admin-form">
    @csrf
    @if (($method ?? 'POST') !== 'POST')
        @method($method)
    @endif
    <fieldset {{ $isPublished ? 'disabled' : '' }}>

    <div class="exam-form-sections">
        @if ($isSuperAdmin && $isNewExam)
            <section class="exam-form-section">
                <div class="exam-form-section-title">
                    <span>00</span>
                    <h3>Branch</h3>
                </div>
                <div class="row g-3">
                    <div class="col-12">
                        <label for="branch_id" class="form-label">Branch <span class="required-mark">*</span></label>
                        <select id="branch_id" name="branch_id" class="form-select form-control @error('branch_id') is-invalid @enderror" required data-branch-select>
                            <option value="">Select branch</option>
                            @foreach ($branches ?? [] as $branchOption)
                                <option value="{{ $branchOption->id }}" @selected(old('branch_id', $selectedBranchId ?? '') == $branchOption->id)>{{ $branchOption->name }}</option>
                            @endforeach
                        </select>
                        @error('branch_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
            </section>
        @elseif ($isSuperAdmin && ! $isNewExam)
            <section class="exam-form-section">
                <div class="exam-form-section-title">
                    <span>00</span>
                    <h3>Branch</h3>
                </div>
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label">Branch</label>
                        <input type="text" class="form-control" value="{{ $contextBranch->name }}" disabled>
                    </div>
                </div>
            </section>
        @endif

        <section class="exam-form-section">
            <div class="exam-form-section-title">
                <span>01</span>
                <h3>Basic Exam Information</h3>
            </div>
            <div class="row g-3">
                <div class="col-12">
                    <label for="title" class="form-label">Exam Title <span class="required-mark">*</span></label>
                    <input id="title" name="title" value="{{ old('title', $exam->title) }}" class="form-control @error('title') is-invalid @enderror" required>
                    @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-12">
                    <label for="description" class="form-label">Description</label>
                    <textarea id="description" name="description" rows="3" class="form-control @error('description') is-invalid @enderror">{{ old('description', $exam->description) }}</textarea>
                    @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
        </section>

        <section class="exam-form-section">
            <div class="exam-form-section-title">
                <span>02</span>
                <h3>Class & Exam Settings</h3>
            </div>
            <div class="row g-3">
                <div class="col-12">
                    <label for="school_class_id" class="form-label">Class <span class="required-mark">*</span></label>
                    <select id="school_class_id" name="school_class_id" class="form-select form-control @error('school_class_id') is-invalid @enderror" required data-class-select data-selected-class="{{ old('school_class_id', $exam->school_class_id) }}">
                        <option value="">Select class</option>
                        @foreach ($classes as $schoolClass)
                            <option value="{{ $schoolClass->id }}" @selected(old('school_class_id', $exam->school_class_id) == $schoolClass->id)>{{ $schoolClass->name }}{{ $schoolClass->isGlobal() ? ' (All Branches)' : '' }}</option>
                        @endforeach
                    </select>
                    @error('school_class_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    <div class="form-text empty-class-hint d-none">No classes found for this branch.</div>
                </div>
            </div>
        </section>

        <section class="exam-form-section">
            <div class="exam-form-section-title">
                <span>03</span>
                <h3>Schedule</h3>
            </div>
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="starts_at" class="form-label">Start Date & Time <span class="required-mark">*</span></label>
                    <input id="starts_at" type="datetime-local" name="starts_at" value="{{ old('starts_at', $exam->starts_at?->format('Y-m-d\\TH:i')) }}" class="form-control @error('starts_at') is-invalid @enderror" required>
                    @error('starts_at')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label for="ends_at" class="form-label">End Date & Time <span class="required-mark">*</span></label>
                    <input id="ends_at" type="datetime-local" name="ends_at" value="{{ old('ends_at', $exam->ends_at?->format('Y-m-d\\TH:i')) }}" class="form-control @error('ends_at') is-invalid @enderror" required>
                    @error('ends_at')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
        </section>

        <section class="exam-form-section">
            <div class="exam-form-section-title">
                <span>04</span>
                <h3>Attempt & Security Settings</h3>
            </div>
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="maximum_attempts" class="form-label">Maximum Attempts <span class="required-mark">*</span></label>
                    <input id="maximum_attempts" type="number" min="1" name="maximum_attempts" value="{{ old('maximum_attempts', $exam->maximum_attempts ?? 1) }}" class="form-control @error('maximum_attempts') is-invalid @enderror" required>
                    @error('maximum_attempts')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label for="negative_marks" class="form-label">Negative Marks Per Wrong Answer</label>
                    <input id="negative_marks" type="number" min="0" step="0.01" name="negative_marks" value="{{ old('negative_marks', $exam->negative_marks ?? 0) }}" class="form-control @error('negative_marks') is-invalid @enderror">
                    @error('negative_marks')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                @foreach (['randomize_questions' => 'Question Randomization', 'randomize_answers' => 'Answer Randomization', 'negative_marking_enabled' => 'Negative Marking'] as $field => $label)
                    <div class="col-md-4">
                        <label class="form-check module-check">
                            <input type="checkbox" name="{{ $field }}" value="1" class="form-check-input" @checked(old($field, $exam->{$field}))>
                            <span>{{ $label }}</span>
                        </label>
                    </div>
                @endforeach
            </div>
        </section>

    </div>

    </fieldset>

    <div class="d-flex gap-2 mt-4">
        @if (!$isPublished)
            <button class="btn btn-primary" type="submit">
                <i class="bi bi-check-circle-fill"></i>
                {{ $button }}
            </button>
        @endif
        <a href="{{ route($prefix.'.exams.index') }}" class="btn btn-soft">Cancel</a>
    </div>
</form>
