@php($prefix = $prefix ?? 'admin')

@if ($exam->exists && $exam->hasBeenAttempted())
    <div class="feedback-alert info mb-4">
        <i class="bi bi-info-circle-fill"></i>
        <div>
            <strong>A student has already attended this exam.</strong>
            <p class="mb-0">You can still edit its details below. Existing results will not be affected.</p>
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
    <fieldset>

    <div class="exam-form-sections">
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
                @php($selectedGradeIds = array_map('intval', (array) old('school_class_ids', $exam->exists ? $exam->grades->pluck('id')->all() : [])))
                @php($sortedClasses = $classes->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)->values())
                <div class="col-md-6" data-grade-picker>
                    <label class="form-label">Grade(s) <span class="required-mark">*</span></label>
                    @if ($sortedClasses->isEmpty())
                        <div class="form-text">No grades found. Add one from Grades first.</div>
                    @else
                        <div class="grade-ms @error('school_class_ids') is-invalid @enderror">
                            <button type="button" class="grade-ms-field form-control" data-grade-field aria-haspopup="listbox" aria-expanded="false">
                                <span class="grade-ms-value" data-grade-value>
                                    <span class="grade-ms-placeholder">Select grades</span>
                                </span>
                                <i class="bi bi-chevron-down grade-ms-caret"></i>
                            </button>
                            <div class="grade-ms-menu" data-grade-menu hidden>
                                <div class="grade-ms-search">
                                    <i class="bi bi-search"></i>
                                    <input type="text" placeholder="Search grades" data-grade-search autocomplete="off">
                                </div>
                                <div class="grade-ms-actions">
                                    <button type="button" data-grade-select-all>Select all</button>
                                    <button type="button" data-grade-clear>Clear</button>
                                </div>
                                <div class="grade-ms-options" role="listbox" aria-multiselectable="true">
                                    @foreach ($sortedClasses as $schoolClass)
                                        <label class="grade-ms-option" data-grade-option data-name="{{ strtolower($schoolClass->name) }}">
                                            <input type="checkbox" name="school_class_ids[]" value="{{ $schoolClass->id }}" data-label="{{ $schoolClass->name }}" @checked(in_array($schoolClass->id, $selectedGradeIds, true))>
                                            <span>{{ $schoolClass->name }}</span>
                                        </label>
                                    @endforeach
                                    <div class="grade-ms-empty" data-grade-empty hidden>No matching grades</div>
                                </div>
                            </div>
                        </div>
                    @endif
                    @error('school_class_ids')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    @error('school_class_ids.*')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    <div class="invalid-feedback d-none" data-grade-required>Please select at least one grade for this exam.</div>
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
                <div class="col-md-6">
                    <label for="total_marks" class="form-label">Total Marks</label>
                    <input id="total_marks" type="number" min="0" name="total_marks" value="{{ old('total_marks', $exam->total_marks ?? 0) }}" class="form-control @error('total_marks') is-invalid @enderror" data-strip-leading-zero>
                    @error('total_marks')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
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
                    <label for="maximum_attempts" class="form-label">Attempt Limit <span class="required-mark">*</span></label>
                    <input id="maximum_attempts" type="number" min="1" max="20" name="maximum_attempts" value="{{ old('maximum_attempts', $exam->maximum_attempts ?? 1) }}" class="form-control @error('maximum_attempts') is-invalid @enderror" required>
                    @error('maximum_attempts')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    <div class="form-text">How many times a student can take this exam. Results are shown to the student after all attempts are completed.</div>
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
        <button class="btn btn-primary" type="submit">
            <i class="bi bi-check-circle-fill"></i>
            {{ $button }}
        </button>
        <a href="{{ route($prefix.'.exams.index') }}" class="btn btn-soft">Cancel</a>
    </div>
</form>

<script>
    // Total Marks starts pre-filled with 0. Strip that leading zero as soon
    // as the user types a real digit, so "10" doesn't become "010" — purely
    // a display/input fix, the submitted value and its validation are
    // untouched.
    // Grade(s) multi-select: a compact field showing the picked grades as
    // chips, opening a searchable checklist. The checkboxes inside are the
    // real school_class_ids[] inputs, so the server contract is unchanged.
    document.querySelectorAll('[data-grade-picker]').forEach(function (picker) {
        var field = picker.querySelector('[data-grade-field]');
        var menu = picker.querySelector('[data-grade-menu]');

        if (! field || ! menu) {
            return;
        }

        var wrapper = field.closest('.grade-ms');
        var valueEl = picker.querySelector('[data-grade-value]');
        var search = picker.querySelector('[data-grade-search]');
        var emptyEl = picker.querySelector('[data-grade-empty]');
        var requiredHint = picker.querySelector('[data-grade-required]');
        var options = Array.prototype.slice.call(picker.querySelectorAll('[data-grade-option]'));
        var boxes = options.map(function (option) { return option.querySelector('input'); });
        var maxChips = 3;

        var render = function () {
            var checked = boxes.filter(function (box) { return box.checked; });
            valueEl.innerHTML = '';

            if (checked.length === 0) {
                var placeholder = document.createElement('span');
                placeholder.className = 'grade-ms-placeholder';
                placeholder.textContent = 'Select grades';
                valueEl.appendChild(placeholder);
                return;
            }

            checked.slice(0, maxChips).forEach(function (box) {
                var chip = document.createElement('span');
                chip.className = 'grade-ms-chip';
                chip.textContent = box.dataset.label;

                var remove = document.createElement('span');
                remove.className = 'grade-ms-chip-remove';
                remove.setAttribute('role', 'button');
                remove.setAttribute('aria-label', 'Remove ' + box.dataset.label);
                remove.innerHTML = '&times;';
                remove.addEventListener('click', function (event) {
                    event.stopPropagation();
                    box.checked = false;
                    render();
                });

                chip.appendChild(remove);
                valueEl.appendChild(chip);
            });

            if (checked.length > maxChips) {
                var more = document.createElement('span');
                more.className = 'grade-ms-more';
                more.textContent = '+' + (checked.length - maxChips) + ' more';
                valueEl.appendChild(more);
            }

            requiredHint?.classList.add('d-none');
            wrapper.classList.remove('is-invalid');
        };

        var open = function () {
            menu.hidden = false;
            wrapper.classList.add('is-open');
            field.setAttribute('aria-expanded', 'true');
            search?.focus();
        };

        var close = function () {
            menu.hidden = true;
            wrapper.classList.remove('is-open');
            field.setAttribute('aria-expanded', 'false');
        };

        var visibleBoxes = function () {
            return options.filter(function (option) { return ! option.hidden; })
                .map(function (option) { return option.querySelector('input'); });
        };

        field.addEventListener('click', function () {
            menu.hidden ? open() : close();
        });

        search?.addEventListener('input', function () {
            var term = search.value.trim().toLowerCase();
            var shown = 0;

            options.forEach(function (option) {
                option.hidden = term !== '' && option.dataset.name.indexOf(term) === -1;
                shown += option.hidden ? 0 : 1;
            });

            emptyEl.hidden = shown > 0;
        });

        picker.querySelector('[data-grade-select-all]')?.addEventListener('click', function () {
            visibleBoxes().forEach(function (box) { box.checked = true; });
            render();
        });

        picker.querySelector('[data-grade-clear]')?.addEventListener('click', function () {
            visibleBoxes().forEach(function (box) { box.checked = false; });
            render();
        });

        boxes.forEach(function (box) { box.addEventListener('change', render); });

        document.addEventListener('click', function (event) {
            if (! picker.contains(event.target)) {
                close();
            }
        });

        picker.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && ! menu.hidden) {
                event.stopPropagation();
                close();
                field.focus();
            }
        });

        picker.closest('form')?.addEventListener('submit', function (event) {
            if (! boxes.some(function (box) { return box.checked; })) {
                event.preventDefault();
                requiredHint?.classList.remove('d-none');
                wrapper.classList.add('is-invalid');
                picker.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        });

        render();
    });

    document.querySelectorAll('[data-strip-leading-zero]').forEach(function (input) {
        input.addEventListener('input', function () {
            if (/^0+(?=\d)/.test(this.value)) {
                this.value = this.value.replace(/^0+(?=\d)/, '');
            }
        });
    });
</script>
