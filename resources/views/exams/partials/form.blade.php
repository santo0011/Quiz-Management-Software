@php($prefix = $prefix ?? 'admin')
@php($isLocked = $exam->exists && $exam->hasBeenAttempted())
@php($examSession = $exam->exists ? $exam->session : ($selectedAcademicSession ?? null))

@if ($isLocked)
    <div class="feedback-alert info mb-4">
        <i class="bi bi-lock-fill"></i>
        <div>
            <strong>This exam is locked.</strong>
            <p class="mb-0">{{ \App\Models\Exam::LOCK_MESSAGE }}</p>
        </div>
    </div>
@endif

<form method="POST" action="{{ $action }}" class="admin-form">
    @csrf
    @if (($method ?? 'POST') !== 'POST')
        @method($method)
    @endif
    @isset($drawerId)
        <input type="hidden" name="_drawer" value="{{ $drawerId }}">
    @endisset
    <fieldset {{ $isLocked ? 'disabled' : '' }}>

    <div class="exam-form-sections">
        @if ($examSession)
            <div class="feedback-alert info mb-3">
                <i class="bi bi-calendar-range"></i>
                <div><strong>Academic Session:</strong> {{ $examSession->name }} — this exam will only be available to students assigned to this session.</div>
            </div>
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
                <h3>Grade & Exam Settings</h3>
            </div>
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="school_class_id" class="form-label">Grade <span class="required-mark">*</span></label>
                    <select id="school_class_id" name="school_class_id" class="form-select form-control @error('school_class_id') is-invalid @enderror" required data-class-select data-selected-class="{{ old('school_class_id', $exam->school_class_id) }}">
                        <option value="">Select grade</option>
                        @foreach ($classes as $schoolClass)
                            <option value="{{ $schoolClass->id }}" @selected(old('school_class_id', $exam->school_class_id) == $schoolClass->id)>{{ $schoolClass->name }}</option>
                        @endforeach
                    </select>
                    @error('school_class_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    <div class="form-text empty-class-hint d-none">No grades found for this branch.</div>
                </div>
                <div class="col-md-6">
                    <label for="subject_id" class="form-label">Subject <span class="required-mark">*</span></label>
                    <select id="subject_id" name="subject_id" class="form-select form-control @error('subject_id') is-invalid @enderror" required>
                        <option value="">Select subject</option>
                        @foreach ($subjects as $subject)
                            <option value="{{ $subject->id }}" @selected(old('subject_id', $exam->subject_id) == $subject->id)>{{ $subject->name }}</option>
                        @endforeach
                    </select>
                    @error('subject_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    @if ($subjects->isEmpty())
                        <div class="form-text">No subjects found. Add one from Subjects first.</div>
                    @endif
                </div>
            </div>
        </section>

        <section class="exam-form-section">
            <div class="exam-form-section-title">
                <span>03</span>
                <h3>Marks & Duration</h3>
            </div>
            <div class="row g-3">
                <div class="col-md-4">
                    <label for="total_marks" class="form-label">Total Marks</label>
                    <input id="total_marks" type="number" min="0" name="total_marks" value="{{ old('total_marks', $exam->total_marks ?? 0) }}" class="form-control @error('total_marks') is-invalid @enderror">
                    @error('total_marks')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label for="passing_marks" class="form-label">Pass Marks</label>
                    <input id="passing_marks" type="number" min="0" name="passing_marks" value="{{ old('passing_marks', $exam->passing_marks) }}" class="form-control @error('passing_marks') is-invalid @enderror">
                    @error('passing_marks')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label for="duration_minutes" class="form-label">Exam Time (minutes) <span class="required-mark">*</span></label>
                    <input id="duration_minutes" type="number" min="1" max="1440" name="duration_minutes" value="{{ old('duration_minutes', $exam->duration_minutes ?? 30) }}" class="form-control @error('duration_minutes') is-invalid @enderror" required>
                    @error('duration_minutes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
        </section>

        <section class="exam-form-section">
            <div class="exam-form-section-title">
                <span>04</span>
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
                <span>05</span>
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
        @if (!$isLocked)
            <button class="btn btn-primary" type="submit">
                <i class="bi bi-check-circle-fill"></i>
                {{ $button }}
            </button>
        @endif
        <a href="{{ route($prefix.'.exams.index') }}" class="btn btn-soft">Cancel</a>
    </div>
</form>
