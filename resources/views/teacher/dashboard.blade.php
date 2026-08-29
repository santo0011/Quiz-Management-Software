@extends('layouts.teacher')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('content')
    <section class="student-section">
        <div class="student-section-header">
            <div>
                <span>Overview</span>
                <h2>Welcome, {{ $teacher->name }}</h2>
            </div>
        </div>

        @if (! $selectedSessionId)
            <div class="empty-state">
                <i class="bi bi-calendar-range"></i>
                <h3>Select an academic session to continue</h3>
                <p>Choose a session from the navbar dropdown above to view your results.</p>
            </div>
        @else
            <div class="result-card-grid teacher-dashboard-stats">
                <div class="result-card color-blue">
                    <div class="result-card-left">
                        <div class="result-card-icon"><i class="bi bi-bar-chart-fill"></i></div>
                        <span>Total Results</span>
                    </div>
                    <strong>{{ $totalResults }}</strong>
                </div>
                <div class="result-card color-green">
                    <div class="result-card-left">
                        <div class="result-card-icon"><i class="bi bi-chat-square-text-fill"></i></div>
                        <span>Remarked</span>
                    </div>
                    <strong>{{ $remarkedCount }}</strong>
                </div>
                <div class="result-card color-orange">
                    <div class="result-card-left">
                        <div class="result-card-icon"><i class="bi bi-hourglass-split"></i></div>
                        <span>Pending Remark</span>
                    </div>
                    <strong>{{ $pendingCount }}</strong>
                </div>
            </div>
        @endif
    </section>

    @if ($selectedSessionId)
        <section class="student-section mt-4">
            <div class="student-section-header">
                <div>
                    <span>Recent</span>
                    <h2>Latest Results</h2>
                </div>
                <a href="{{ route('teacher.results.index') }}" class="btn btn-sm btn-soft">
                    <i class="bi bi-arrow-right"></i>
                    View All
                </a>
            </div>

            @if ($recentAttempts->isEmpty())
                <div class="empty-state">
                    <i class="bi bi-bar-chart"></i>
                    <h3>No results yet</h3>
                    <p>Submitted exam results for your branch will appear here.</p>
                </div>
            @else
                <div class="performance-list">
                    @foreach ($recentAttempts as $attempt)
                        <div class="performance-item">
                            <div class="performance-main">
                                <div class="performance-icon {{ $attempt->is_passed ? 'passed' : 'failed' }}">
                                    <i class="bi bi-graph-up-arrow"></i>
                                </div>
                                <div class="performance-info">
                                    <h4>{{ $attempt->student?->student_name }} &middot; {{ $attempt->exam?->title }}</h4>
                                    <span class="performance-date {{ $attempt->is_passed ? 'passed' : 'failed' }}">
                                        <i class="bi bi-calendar-check"></i>
                                        {{ $attempt->submitted_at?->format('d M Y, h:i A') }}
                                    </span>
                                </div>
                            </div>
                            <div class="performance-status">
                                <span class="status-badge {{ $attempt->teacher_remark ? 'status-published' : 'status-closed' }}">
                                    <i class="bi {{ $attempt->teacher_remark ? 'bi-check-circle-fill' : 'bi-hourglass-split' }}"></i>
                                    {{ $attempt->teacher_remark ? 'Remarked' : 'Pending' }}
                                </span>
                            </div>
                            <a href="{{ route('teacher.results.show', $attempt) }}" class="btn btn-sm btn-soft performance-action">
                                <i class="bi bi-eye-fill"></i> View
                            </a>
                        </div>
                    @endforeach
                </div>
            @endif
        </section>
    @endif
@endsection
