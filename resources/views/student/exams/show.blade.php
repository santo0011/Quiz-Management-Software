@extends('layouts.student')

@section('title', $exam->title)
@section('page-title', 'Exam Instructions')

@section('content')

    {{-- Back Button --}}
    <div class="student-profile-top-actions">
        <a href="{{ route('student.exams.available') }}"
           class="btn btn-outline-secondary btn-student-back">
            <i class="bi bi-arrow-left"></i>
            Back
        </a>
    </div>


    {{-- Exam Instructions --}}
    <section class="student-section exam-instructions">

        <div class="student-section-header">
            <div>
                <span>{{ $exam->schoolClass?->name }}</span>
                <h2>{{ $exam->title }}</h2>
            </div>

            <span class="status-badge status-published">
                {{ $remainingAttempts }} attempts left
            </span>
        </div>


        {{-- Description --}}
        <p>
            {{ $exam->description ?: 'Please read the exam conditions carefully before starting.' }}
        </p>


        {{-- Exam Information Cards --}}
        <div class="student-info-card-grid">

            {{-- Total Marks --}}
            <div class="info-card color-blue">
                <div class="info-card-icon">
                    <i class="bi bi-trophy-fill"></i>
                </div>
                <span>Total Marks</span>
                <strong>{{ $exam->total_marks }}</strong>
            </div>


            {{-- Passing Marks --}}
            <div class="info-card color-green">
                <div class="info-card-icon">
                    <i class="bi bi-flag-fill"></i>
                </div>
                <span>Passing Marks</span>
                <strong>{{ $exam->passing_marks ?? 'Not set' }}</strong>
            </div>


            {{-- Duration --}}
            <div class="info-card color-orange">
                <div class="info-card-icon">
                    <i class="bi bi-stopwatch-fill"></i>
                </div>
                <span>Duration</span>
                <strong>
                    {{ $exam->duration_minutes }}
                    <small>min</small>
                </strong>
            </div>


            {{-- Questions --}}
            <div class="info-card color-purple">
                <div class="info-card-icon">
                    <i class="bi bi-patch-question-fill"></i>
                </div>
                <span>Questions</span>
                <strong>{{ $exam->questions_count }}</strong>
            </div>


            {{-- Start Date --}}
            <div class="info-card color-teal">
                <div class="info-card-icon">
                    <i class="bi bi-play-circle-fill"></i>
                </div>
                <span>Starts</span>
                <strong>
                    {{ $exam->starts_at?->format('d M, h:i A') ?? 'Open' }}
                </strong>
            </div>


            {{-- End Date --}}
            <div class="info-card color-red">
                <div class="info-card-icon">
                    <i class="bi bi-stop-circle-fill"></i>
                </div>
                <span>Ends</span>
                <strong>
                    {{ $exam->ends_at?->format('d M, h:i A') ?? 'Open' }}
                </strong>
            </div>

        </div>


        {{-- Exam Warning --}}
        <div class="feedback-alert info mt-4">
            <i class="bi bi-info-circle-fill"></i>

            <div>
                Once started, the timer continues until submission.
                The exam auto-submits when time expires.
            </div>
        </div>


        {{-- Start Exam Form --}}
        <form
            method="POST"
            action="{{ route('student.exams.start', $exam) }}"
            class="mt-4"
            id="beginExamForm"
        >
            @csrf

            {{-- IMPORTANT: type="button" prevents form submission --}}
            <button
                class="btn btn-primary btn-lg"
                type="button"
                id="beginExamButton"
                @disabled($remainingAttempts <= 0)
            >
                <i class="bi bi-play-circle-fill"></i>
                 Begin Exam
            </button>

        </form>

    </section>


    {{-- Start Exam Modal --}}
    <div
        class="modal fade"
        id="beginExamModal"
        tabindex="-1"
        aria-labelledby="beginExamModalLabel"
        aria-hidden="true"
    >
        <div class="modal-dialog modal-dialog-centered">

            <div class="modal-content confirm-modal">

                {{-- Modal Header --}}
                <div class="modal-header">

                    <div>
                        <span class="page-kicker">
                            Exam Ready
                        </span>

                        <h2
                            class="modal-title fs-5"
                            id="beginExamModalLabel"
                        >
                            Start Exam
                        </h2>
                    </div>

                    <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="modal"
                        aria-label="Close"
                    ></button>

                </div>


                {{-- Modal Body --}}
                <div class="modal-body">

                    <div class="begin-exam-icon">
                        <i class="bi bi-rocket-takeoff-fill"></i>
                    </div>

                    <p class="mb-0 text-center">
                        Are you ready to start this exam?
                        Once started, the exam timer will begin.
                    </p>

                </div>


                {{-- Modal Footer --}}
                <div class="modal-footer">

                    {{-- Cancel --}}
                    <button
                        type="button"
                        class="btn btn-outline-secondary"
                        data-bs-dismiss="modal"
                    >
                        Cancel
                    </button>


                    {{-- Confirm --}}
                    <button
                        type="button"
                        class="btn btn-primary"
                        id="confirmBeginExamButton"
                    >
                        <i class="bi bi-play-circle-fill"></i>
                        Begin Exam
                    </button>

                </div>

            </div>

        </div>
    </div>


    {{-- JavaScript --}}
    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {

                const form = document.getElementById('beginExamForm');
                const beginButton = document.getElementById('beginExamButton');
                const modalEl = document.getElementById('beginExamModal');
                const confirmButton = document.getElementById('confirmBeginExamButton');

                if (!form || !beginButton || !modalEl || !confirmButton) {
                    return;
                }


                // ==================================================
                // START BUTTON
                // Only opens the modal.
                // Does NOT submit the form.
                // Does NOT show a loader.
                // ==================================================
                beginButton.addEventListener('click', function () {
                    const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
                    modal.show();
                });


                // ==================================================
                // CONFIRM BUTTON INSIDE MODAL
                // Submit the exam form.
                // No spinner is added here.
                // ==================================================
                confirmButton.addEventListener('click', function () {
                    form.submit();
                });

            });
        </script>
    @endpush

@endsection