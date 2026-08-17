@extends('layouts.student')

@section('title', $exam->title)
@section('page-title', 'Exam Instructions')

@section('content')
    <section class="student-section exam-instructions">
        <div class="student-section-header">
            <div>
                <span>{{ $exam->schoolClass?->name }}</span>
                <h2>{{ $exam->title }}</h2>
            </div>
            <span class="status-badge status-published">{{ $remainingAttempts }} attempts left</span>
        </div>

        <p>{{ $exam->description ?: 'Please read the exam conditions carefully before starting.' }}</p>

        <div class="student-info-card-grid">
            <div class="info-card color-blue">
                <div class="info-card-icon"><i class="bi bi-trophy-fill"></i></div>
                <span>Total Marks</span>
                <strong>{{ $exam->total_marks }}</strong>
            </div>
            <div class="info-card color-green">
                <div class="info-card-icon"><i class="bi bi-flag-fill"></i></div>
                <span>Passing Marks</span>
                <strong>{{ $exam->passing_marks ?? 'Not set' }}</strong>
            </div>
            <div class="info-card color-orange">
                <div class="info-card-icon"><i class="bi bi-stopwatch-fill"></i></div>
                <span>Duration</span>
                <strong>{{ $exam->duration_minutes }} <small>min</small></strong>
            </div>
            <div class="info-card color-purple">
                <div class="info-card-icon"><i class="bi bi-patch-question-fill"></i></div>
                <span>Questions</span>
                <strong>{{ $exam->questions_count }}</strong>
            </div>
            <div class="info-card color-teal">
                <div class="info-card-icon"><i class="bi bi-play-circle-fill"></i></div>
                <span>Starts</span>
                <strong>{{ $exam->starts_at?->format('d M, h:i A') ?? 'Open' }}</strong>
            </div>
            <div class="info-card color-red">
                <div class="info-card-icon"><i class="bi bi-stop-circle-fill"></i></div>
                <span>Ends</span>
                <strong>{{ $exam->ends_at?->format('d M, h:i A') ?? 'Open' }}</strong>
            </div>
        </div>

        <div class="feedback-alert info mt-4">
            <i class="bi bi-info-circle-fill"></i>
            <div>Once started, the timer continues until submission. The exam auto-submits when time expires.</div>
        </div>

        <form method="POST" action="{{ route('student.exams.start', $exam) }}" class="mt-4" id="beginExamForm">
            @csrf
            <button class="btn btn-primary btn-lg" type="submit" id="beginExamButton" @disabled($remainingAttempts <= 0)>
                <i class="bi bi-play-circle-fill"></i>
                Begin Exam
            </button>
        </form>
    </section>

    <div class="modal fade" id="beginExamModal" tabindex="-1" aria-labelledby="beginExamModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content confirm-modal">
                <div class="modal-header">
                    <div>
                        <span class="page-kicker">Exam Ready</span>
                        <h2 class="modal-title fs-5" id="beginExamModalLabel">Start Exam</h2>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="begin-exam-icon">
                        <i class="bi bi-rocket-takeoff-fill"></i>
                    </div>
                    <p class="mb-0 text-center">Are you ready to start this exam? Once started, the exam timer will begin.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="confirmBeginExamButton">
                        <i class="bi bi-play-circle-fill"></i>
                        Begin Exam
                    </button>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const form = document.getElementById('beginExamForm');
                const beginButton = document.getElementById('beginExamButton');
                const modalEl = document.getElementById('beginExamModal');
                const confirmButton = document.getElementById('confirmBeginExamButton');

                if (!form || !confirmButton || !beginButton) return;

                form.addEventListener('submit', (event) => {
                    event.preventDefault();
                    bootstrap.Modal.getOrCreateInstance(modalEl).show();
                });

                confirmButton.addEventListener('click', () => {
                    const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
                    modal.hide();
                    beginButton.disabled = true;
                    beginButton.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Starting...';
                    form.submit();
                });
            });
        </script>
    @endpush
@endsection