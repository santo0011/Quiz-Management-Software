@extends('layouts.student')

@section('title', 'Student Dashboard')
@section('page-title', 'Dashboard')

@section('content')
    <section class="student-hero" id="student-profile">
        <div class="student-hero-avatar">{{ strtoupper(substr($student->student_name, 0, 1)) }}</div>
        <div class="student-hero-copy">
            <span>{{ $student->branch?->name ?? 'Branch not assigned' }}</span>
            <h1>Welcome, {{ $student->student_name }}</h1>
            <p>{{ $student->schoolClass?->name ?? $student->class }} · {{ $student->email }}</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('student.profile') }}" class="btn btn-light">
                <i class="bi bi-person-badge"></i> My Profile
            </a>
        </div>
    </section>

    <section class="student-stat-grid">
        <div class="student-stat"><span>Available</span><strong>{{ $availableExams->count() }}</strong></div>
        <div class="student-stat"><span>Upcoming</span><strong>{{ $upcomingExams->count() }}</strong></div>
        <div class="student-stat"><span>Completed</span><strong>{{ $completedExams }}</strong></div>
        <div class="student-stat"><span>Average Score</span><strong>{{ $averageScore }}%</strong></div>
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
        </div>
    </section>

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
                            <span>{{ $attempt->submitted_at?->format('d M Y') }}</span>
                        </div>
                        <span class="status-badge status-published">{{ $attempt->percentage }}%</span>
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
                                backgroundColor: 'rgba(59, 130, 246, 0.8)',
                                borderColor: '#3b82f6',
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
            });
        </script>
    @endpush
@endsection