@php($prefix = $prefix ?? 'admin')

<section class="question-card exam-settings-card mb-4">
    <div class="question-card-header">
        <span class="question-card-icon accent"><i class="bi bi-sliders2"></i></span>
        <div class="flex-grow-1">
            <h3>Exam Settings</h3>
            <p>Duration, passing marks, and marks per question for this exam. Total marks is calculated automatically from the questions below.</p>
        </div>
    </div>
    <div class="question-card-body">
        <form method="POST" action="{{ route($prefix.'.exams.settings.update', $exam) }}" class="admin-form" data-exam-settings-form>
            @csrf
            @method('PUT')

            <div class="row g-3">
                <div class="col-md-4">
                    <label for="duration_minutes" class="form-label">Duration Minutes <span class="required-mark">*</span></label>
                    <input id="duration_minutes" type="number" min="1" max="1440" name="duration_minutes" value="{{ old('duration_minutes', $exam->duration_minutes) }}" class="form-control @error('duration_minutes') is-invalid @enderror" required>
                    @error('duration_minutes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label for="passing_marks" class="form-label">Passing Marks</label>
                    <input id="passing_marks" type="number" min="0" name="passing_marks" value="{{ old('passing_marks', $exam->passing_marks) }}" class="form-control @error('passing_marks') is-invalid @enderror">
                    @error('passing_marks')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label for="marks_per_question" class="form-label">Marks Per Question <span class="required-mark">*</span></label>
                    <input id="marks_per_question" type="number" step="0.01" min="0.01" name="marks_per_question" value="{{ old('marks_per_question', $exam->marks_per_question) }}" class="form-control @error('marks_per_question') is-invalid @enderror" required>
                    @error('marks_per_question')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="d-flex align-items-center gap-3 mt-3">
                <button type="submit" class="btn btn-soft">
                    <i class="bi bi-check-circle-fill"></i>
                    Save Exam Settings
                </button>
                <span class="text-muted small">Total Marks: <strong>{{ $exam->total_marks }}</strong></span>
            </div>
        </form>
    </div>
</section>
