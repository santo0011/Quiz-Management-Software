@php($prefix = $prefix ?? 'admin')
@php($contextBranch = $selectedBranch ?? $branch)

<form method="POST" action="{{ $action }}" class="admin-form">
    @csrf
    @if (($method ?? 'POST') !== 'POST')
        @method($method)
    @endif

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
                <h3>Class & Exam Settings</h3>
            </div>
            <div class="row g-3">
                <div class="col-12">
                    <label for="school_class_id" class="form-label">Class <span class="required-mark">*</span></label>
                    <select id="school_class_id" name="school_class_id" class="form-select form-control @error('school_class_id') is-invalid @enderror" required>
                        <option value="">Select class</option>
                        @foreach ($classes as $schoolClass)
                            <option value="{{ $schoolClass->id }}" @selected(old('school_class_id', $exam->school_class_id) == $schoolClass->id)>{{ $schoolClass->name }}</option>
                        @endforeach
                    </select>
                    @error('school_class_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
        </section>

        <section class="exam-form-section">
            <div class="exam-form-section-title">
                <span>03</span>
                <h3>Marks & Duration</h3>
            </div>
            <div class="row g-3">
                <div class="col-md-3">
                    <label for="marks_per_question" class="form-label">Marks Per Question <span class="required-mark">*</span></label>
                    <input id="marks_per_question" type="number" step="0.01" min="0.01" name="marks_per_question" value="{{ old('marks_per_question', $exam->marks_per_question ?? 1) }}" class="form-control marks-per-question-input @error('marks_per_question') is-invalid @enderror" required>
                    @error('marks_per_question')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3">
                    <label for="total_questions" class="form-label">Total Questions <span class="required-mark">*</span></label>
                    <input id="total_questions" type="number" min="1" name="total_questions" value="{{ old('total_questions', $exam->questions()->count() ?: 1) }}" class="form-control total-questions-input @error('total_questions') is-invalid @enderror" required>
                    <div class="form-text">Expected question count</div>
                    @error('total_questions')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3">
                    <label for="total_marks" class="form-label">Total Marks <span class="required-mark">*</span></label>
                    <input id="total_marks" type="number" min="1" name="total_marks" value="{{ old('total_marks', $exam->total_marks) }}" class="form-control total-marks-input @error('total_marks') is-invalid @enderror" readonly>
                    <div class="form-text">Auto-calculated</div>
                    @error('total_marks')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3">
                    <label for="duration_minutes" class="form-label">Duration Minutes <span class="required-mark">*</span></label>
                    <input id="duration_minutes" type="number" min="1" name="duration_minutes" value="{{ old('duration_minutes', $exam->duration_minutes) }}" class="form-control @error('duration_minutes') is-invalid @enderror" required>
                    @error('duration_minutes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3">
                    <label for="passing_marks" class="form-label">Passing Marks</label>
                    <input id="passing_marks" type="number" min="0" name="passing_marks" value="{{ old('passing_marks', $exam->passing_marks) }}" class="form-control @error('passing_marks') is-invalid @enderror">
                    @error('passing_marks')<div class="invalid-feedback">{{ $message }}</div>@enderror
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
                    <label for="starts_at" class="form-label">Start Date & Time</label>
                    <input id="starts_at" type="datetime-local" name="starts_at" value="{{ old('starts_at', $exam->starts_at?->format('Y-m-d\\TH:i')) }}" class="form-control @error('starts_at') is-invalid @enderror">
                    @error('starts_at')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label for="ends_at" class="form-label">End Date & Time</label>
                    <input id="ends_at" type="datetime-local" name="ends_at" value="{{ old('ends_at', $exam->ends_at?->format('Y-m-d\\TH:i')) }}" class="form-control @error('ends_at') is-invalid @enderror">
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

        <section class="exam-form-section">
            <div class="exam-form-section-title">
                <span>06</span>
                <h3>Publish/Status</h3>
            </div>
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="status" class="form-label">Status</label>
                    <select id="status" name="status" class="form-select form-control">
                        @foreach (['draft' => 'Draft', 'published' => 'Published', 'closed' => 'Closed'] as $value => $label)
                            <option value="{{ $value }}" @selected(old('status', $exam->status ?? 'draft') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </section>
    </div>

    <div class="d-flex gap-2 mt-4">
        <button class="btn btn-primary" type="submit">
            <i class="bi bi-check-circle-fill"></i>
            {{ $button }}
        </button>
        <a href="{{ route($prefix.'.exams.index') }}" class="btn btn-soft">Cancel</a>
    </div>
</form>

@push('scripts')
    <script>
        (function () {
            const marksInput = document.getElementById('marks_per_question');
            const questionsInput = document.getElementById('total_questions');
            const totalInput = document.getElementById('total_marks');

            if (! marksInput || ! questionsInput || ! totalInput) {
                return;
            }

            const updateTotal = () => {
                const marks = parseFloat(marksInput.value) || 0;
                const count = parseInt(questionsInput.value, 10) || 0;
                totalInput.value = (marks * count) || '';
            };

            marksInput.addEventListener('input', updateTotal);
            questionsInput.addEventListener('input', updateTotal);
            updateTotal();
        })();
    </script>
@endpush
