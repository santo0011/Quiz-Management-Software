@extends('layouts.student')

@section('title', 'Student Dashboard')
@section('page-title', 'Dashboard')

@section('content')
    <section class="student-hero" id="student-profile">
        <div>
            <span>{{ $student->branch?->name ?? 'Branch not assigned' }}</span>
            <h1>Welcome, {{ $student->student_name }}</h1>
            <p>{{ $student->schoolClass?->name ?? $student->class }} · {{ $student->email }}</p>
        </div>
    </section>

    <section class="student-section" id="performance-charts">
        <div class="student-section-header">
            <div>
                <span>Analytics</span>
                <h2>Exam Performance</h2>
            </div>
        </div>

        <div class="dashboard-charts-grid">
            <div class="chart-card">
                <div class="chart-card-header">
                    <h3>Marks / Percentage by Exam</h3>
                    <span class="chart-card-subtitle">Your performance across all exams</span>
                </div>
                <div class="chart-container">
                    <canvas id="performanceChart"></canvas>
                </div>
            </div>

            <div class="chart-card">
                <div class="chart-card-header">
                    <h3>Passed vs Failed</h3>
                    <span class="chart-card-subtitle">Overall exam results</span>
                </div>
                <div class="chart-container">
                    <canvas id="passFailChart"></canvas>
                </div>
            </div>
        </div>
    </section>

    <section class="student-section" id="available-exams">
        <div class="student-section-header">
            <div>
                <span>Available Exams</span>
                <h2>Ready To Attempt</h2>
            </div>
        </div>

        @if ($availableExams->isEmpty())
            <div class="empty-state">
                <i class="bi bi-journal-check"></i>
                <h3>No exams available</h3>
                <p>Published exams for your class will appear here during their scheduled time.</p>
            </div>
        @else
            <div class="exam-card-grid">
                @foreach ($availableExams as $exam)
                    <article class="exam-card">
                        <span class="status-badge status-published">Available</span>
                        <h3>{{ $exam->title }}</h3>
                        <p>{{ $exam->description ?: 'Read the instructions and begin when ready.' }}</p>
                        <dl>
                            <div><dt>Total Marks</dt><dd>{{ $exam->total_marks }}</dd></div>
                            <div><dt>Duration</dt><dd>{{ $exam->duration_minutes }} min</dd></div>
                            <div><dt>Passing</dt><dd>{{ $exam->passing_marks ?? 'Not set' }}</dd></div>
                            <div><dt>Questions</dt><dd>{{ $exam->questions_count }}</dd></div>
                            <div><dt>Ends</dt><dd>{{ $exam->ends_at?->format('d M Y, h:i A') ?? 'Open' }}</dd></div>
                            <div><dt>Attempts Left</dt><dd>{{ $exam->remainingAttemptsFor($student) }}</dd></div>
                        </dl>
                        <a href="{{ route('student.exams.show', $exam) }}" class="btn btn-primary w-100">
                            <i class="bi bi-play-circle-fill"></i>
                            Start Exam
                        </a>
                    </article>
                @endforeach
            </div>
        @endif
    </section>

    @if ($upcomingExams->isNotEmpty())
        <section class="student-section" id="upcoming-exams">
            <div class="student-section-header">
                <div>
                    <span>Scheduled</span>
                    <h2>Upcoming Exams</h2>
                </div>
            </div>
            <div class="exam-card-grid">
                @foreach ($upcomingExams as $exam)
                    <article class="exam-card">
                        <span class="status-badge status-upcoming">Upcoming</span>
                        <h3>{{ $exam->title }}</h3>
                        <p>{{ $exam->description ?: 'This exam will be available at the scheduled start time.' }}</p>
                        <dl>
                            <div><dt>Start</dt><dd>{{ $exam->starts_at?->format('d M Y, h:i A') }}</dd></div>
                            <div><dt>Total Marks</dt><dd>{{ $exam->total_marks }}</dd></div>
                            <div><dt>Duration</dt><dd>{{ $exam->duration_minutes }} min</dd></div>
                            <div><dt>Passing</dt><dd>{{ $exam->passing_marks ?? 'Not set' }}</dd></div>
                            <div><dt>Questions</dt><dd>{{ $exam->questions_count }}</dd></div>
                            <div><dt>Attempts Left</dt><dd>{{ $exam->remainingAttemptsFor($student) }}</dd></div>
                        </dl>
                        <button class="btn btn-soft w-100" disabled>
                            <i class="bi bi-clock"></i>
                            Not Started Yet
                        </button>
                    </article>
                @endforeach
            </div>
        </section>
    @endif

    @if ($expiredExams->isNotEmpty())
        <section class="student-section" id="expired-exams">
            <div class="student-section-header">
                <div>
                    <span>Expired</span>
                    <h2>Expired Exams</h2>
                </div>
            </div>
            <div class="exam-card-grid">
                @foreach ($expiredExams as $exam)
                    <article class="exam-card">
                        <span class="status-badge status-closed">Expired</span>
                        <h3>{{ $exam->title }}</h3>
                        <p>{{ $exam->description ?: 'This exam window has passed.' }}</p>
                        <dl>
                            <div><dt>Total Marks</dt><dd>{{ $exam->total_marks }}</dd></div>
                            <div><dt>Duration</dt><dd>{{ $exam->duration_minutes }} min</dd></div>
                            <div><dt>Ended</dt><dd>{{ $exam->ends_at?->format('d M Y, h:i A') }}</dd></div>
                            <div><dt>Questions</dt><dd>{{ $exam->questions_count }}</dd></div>
                        </dl>
                        <button class="btn btn-outline-secondary w-100" disabled>
                            <i class="bi bi-x-circle"></i>
                            Exam Closed
                        </button>
                    </article>
                @endforeach
            </div>
        </section>
    @endif

    <section class="student-section">
        <div class="student-section-header">
            <div>
                <span>History</span>
                <h2>Recent Results</h2>
            </div>
            <a href="{{ route('student.results.index') }}" class="btn btn-soft">My Results</a>
        </div>
        @if ($recentResults->isEmpty())
            <div class="empty-state">
                <i class="bi bi-bar-chart"></i>
                <h3>No results yet</h3>
                <p>Your completed exam results will be listed here.</p>
            </div>
        @else
            <div class="result-list">
                @foreach ($recentResults as $attempt)
                    <a href="{{ route('student.results.show', $attempt) }}" class="result-row">
                        <div>
                            <strong>{{ $attempt->exam?->title }}</strong>
                            <span>{{ $attempt->submitted_at?->format('d M Y, h:i A') }}</span>
                        </div>
                        <span class="status-badge {{ $attempt->is_passed ? 'status-published' : 'status-closed' }}">{{ $attempt->percentage }}%</span>
                    </a>
                @endforeach
            </div>
        @endif
    </section>

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const performanceData = @json($performanceData);
                const passedCount = {{ $passedCount }};
                const failedCount = {{ $failedCount }};

                // Performance Chart (Bar)
                const perfCtx = document.getElementById('performanceChart');
                if (perfCtx && performanceData.length > 0) {
                    new Chart(perfCtx, {
                        type: 'bar',
                        data: {
                            labels: performanceData.map(d => d.label),
                            datasets: [{
                                label: 'Percentage (%)',
                                data: performanceData.map(d => d.percentage),
                                backgroundColor: performanceData.map(d => d.percentage >= 50 ? 'rgba(16, 185, 129, 0.8)' : 'rgba(239, 68, 68, 0.8)'),
                                borderColor: performanceData.map(d => d.percentage >= 50 ? '#10b981' : '#ef4444'),
                                borderWidth: 2,
                                borderRadius: 8,
                                maxBarThickness: 40,
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: { display: false },
                                tooltip: {
                                    callbacks: {
                                        label: function (context) {
                                            const d = performanceData[context.dataIndex];
                                            return ` ${d.percentage}% (${d.obtained}/${d.total} marks)`;
                                        }
                                    }
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    max: 100,
                                    grid: { color: 'rgba(0,0,0,0.05)' },
                                    ticks: { callback: v => v + '%' }
                                },
                                x: {
                                    grid: { display: false },
                                    ticks: {
                                        maxRotation: 45,
                                        minRotation: 0,
                                        font: { size: 10 }
                                    }
                                }
                            }
                        }
                    });
                } else if (perfCtx) {
                    perfCtx.parentElement.innerHTML = '<div class="chart-empty"><i class="bi bi-bar-chart"></i><p>Complete exams to see your performance chart.</p></div>';
                }

                // Pass/Fail Chart (Doughnut)
                const pfCtx = document.getElementById('passFailChart');
                if (pfCtx && (passedCount > 0 || failedCount > 0)) {
                    new Chart(pfCtx, {
                        type: 'doughnut',
                        data: {
                            labels: ['Passed', 'Failed'],
                            datasets: [{
                                data: [passedCount, failedCount],
                                backgroundColor: ['#10b981', '#ef4444'],
                                borderColor: ['#059669', '#dc2626'],
                                borderWidth: 2,
                                hoverOffset: 8,
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            cutout: '65%',
                            plugins: {
                                legend: {
                                    position: 'bottom',
                                    labels: {
                                        padding: 16,
                                        usePointStyle: true,
                                        pointStyle: 'circle',
                                        font: { size: 12, weight: '600' }
                                    }
                                },
                                tooltip: {
                                    callbacks: {
                                        label: function (context) {
                                            const total = passedCount + failedCount;
                                            const pct = total > 0 ? Math.round((context.parsed / total) * 100) : 0;
                                            return ` ${context.label}: ${context.parsed} (${pct}%)`;
                                        }
                                    }
                                }
                            }
                        }
                    });
                } else if (pfCtx) {
                    pfCtx.parentElement.innerHTML = '<div class="chart-empty"><i class="bi bi-pie-chart"></i><p>Complete exams to see your pass/fail breakdown.</p></div>';
                }
            });
        </script>
    @endpush
@endsection