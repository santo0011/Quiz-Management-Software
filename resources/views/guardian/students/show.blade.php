@extends('layouts.guardian')

@section('title', $student->student_name)
@section('page-title', 'Student Profile')

@section('content')
    <div class="student-profile-top-actions">
        <a href="{{ route('guardian.dashboard') }}" class="btn btn-outline-secondary btn-student-back">
            <i class="bi bi-arrow-left"></i>
            All Students
        </a>
    </div>

    @include('partials.guardian-student-switcher')

    <section class="student-section">
        <div class="student-profile-summary">
            <div class="student-profile-avatar-lg">
                {{ strtoupper(substr($student->student_name, 0, 1)) }}
            </div>
            <div>
                <h3>{{ $student->student_name }}</h3>
                <p class="mb-0">{{ $student->email }}</p>
            </div>
        </div>

        <div class="student-details-grid mt-4">
            <div class="student-detail-item">
                <div class="student-detail-icon">
                    <i class="bi bi-people-fill"></i>
                </div>
                <div>
                    <dt>Grade</dt>
                    <dd>{{ $student->schoolClass?->name ?? $student->class ?? '—' }}</dd>
                </div>
            </div>
            <div class="student-detail-item">
                <div class="student-detail-icon">
                    <i class="bi bi-calendar-range"></i>
                </div>
                <div>
                    <dt>Academic Session</dt>
                    <dd>{{ $student->session?->name ?? '—' }}</dd>
                </div>
            </div>
            <div class="student-detail-item">
                <div class="student-detail-icon">
                    <i class="bi bi-building"></i>
                </div>
                <div>
                    <dt>Branch</dt>
                    <dd>{{ $student->branch?->name ?? '—' }}</dd>
                </div>
            </div>
            <div class="student-detail-item">
                <div class="student-detail-icon">
                    <i class="bi bi-book-fill"></i>
                </div>
                <div>
                    <dt>Subjects</dt>
                    <dd>
                        @forelse ($student->subjects as $subject)
                            <span class="badge text-bg-light border me-1">{{ $subject->name }}</span>
                        @empty
                            —
                        @endforelse
                    </dd>
                </div>
            </div>
            <div class="student-detail-item">
                <div class="student-detail-icon">
                    <i class="bi bi-telephone-fill"></i>
                </div>
                <div>
                    <dt>Phone</dt>
                    <dd>{{ $student->phone_number ?? '—' }}</dd>
                </div>
            </div>
            <div class="student-detail-item">
                <div class="student-detail-icon">
                    <i class="bi bi-envelope-fill"></i>
                </div>
                <div>
                    <dt>Student Email</dt>
                    <dd>{{ $student->email }}</dd>
                </div>
            </div>
        </div>
    </section>

    <section class="student-section mt-4">
        <div class="student-section-header">
            <div>
                <span>Performance</span>
                <h2>Exam Results</h2>
            </div>
            <span class="status-badge status-published">
                <i class="bi bi-file-earmark-text"></i>
                {{ $attempts->count() }} {{ Str::plural('Result', $attempts->count()) }}
            </span>
        </div>

        @if ($attempts->isEmpty())
            <div class="empty-state">
                <i class="bi bi-bar-chart"></i>
                <h3>No results yet</h3>
                <p>{{ $student->student_name }}'s submitted exam results will appear here.</p>
            </div>
        @else
            <div class="performance-list">
                @foreach ($attempts as $attempt)
                    <div class="performance-item">
                        <div class="performance-main">
                            <div class="performance-icon {{ $attempt->is_passed ? 'passed' : 'failed' }}">
                                <i class="bi bi-graph-up-arrow"></i>
                            </div>
                            <div class="performance-info">
                                <h4>{{ $attempt->exam?->title }}</h4>
                                <span class="performance-date {{ $attempt->is_passed ? 'passed' : 'failed' }}">
                                    <i class="bi bi-calendar-check"></i>
                                    {{ $attempt->submitted_at?->format('d M Y, h:i A') }}
                                </span>
                            </div>
                        </div>
                        <div class="performance-metrics">
                            <div class="performance-metric">
                                <span>Marks</span>
                                <strong>{{ $attempt->obtained_marks }} / {{ $attempt->exam?->total_marks }}</strong>
                            </div>
                            <div class="performance-metric">
                                <span>Percentage</span>
                                <strong class="{{ $attempt->percentage >= 50 ? 'text-success' : 'text-danger' }}">{{ $attempt->percentage }}%</strong>
                            </div>
                            <div class="performance-meter" aria-label="Result percentage">
                                <span style="width: {{ min(100, max(0, $attempt->percentage)) }}%"></span>
                            </div>
                        </div>
                        <div class="performance-status">
                            <span class="status-badge {{ $attempt->is_passed ? 'status-published' : 'status-closed' }}">
                                <i class="bi {{ $attempt->is_passed ? 'bi-patch-check-fill' : 'bi-exclamation-circle-fill' }}"></i>
                                {{ $attempt->is_passed ? 'Passed' : 'Failed' }}
                            </span>
                        </div>
                        <a href="{{ route('guardian.students.results.show', [$student, $attempt]) }}" class="btn btn-sm btn-soft performance-action">
                            <i class="bi bi-eye-fill"></i> View Details
                        </a>
                    </div>
                @endforeach
            </div>
        @endif
    </section>
@endsection
