@php($prefix = $prefix ?? 'admin')

<div class="modal fade" id="examSettingsModal" tabindex="-1" aria-labelledby="examSettingsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content confirm-modal">
            <form method="POST" action="{{ route($prefix.'.exams.settings.update', $exam) }}">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <div>
                        <span class="page-kicker">Exam Settings</span>
                        <h2 class="modal-title fs-5" id="examSettingsModalLabel">Finish Setting Up This Exam</h2>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="feedback-alert success mb-3">
                        <i class="bi bi-trophy-fill"></i>
                        <div><strong>Total Marks:</strong> {{ $exam->total_marks }} (calculated automatically from all questions)</div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="modal_duration_minutes" class="form-label">Exam Time (minutes) <span class="required-mark">*</span></label>
                            <input id="modal_duration_minutes" type="number" min="1" max="1440" name="duration_minutes" value="{{ old('duration_minutes', $exam->duration_minutes) }}" class="form-control @error('duration_minutes') is-invalid @enderror" required>
                            @error('duration_minutes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label for="modal_passing_marks" class="form-label">Pass Marks</label>
                            <input id="modal_passing_marks" type="number" min="0" name="passing_marks" value="{{ old('passing_marks', $exam->passing_marks) }}" class="form-control @error('passing_marks') is-invalid @enderror">
                            @error('passing_marks')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Later</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-circle-fill"></i>
                        Save Settings
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
