@extends('layouts.student')

@section('title', 'My Results')
@section('page-title', 'Results')

@section('content')
    <section class="student-section">
        <div class="student-section-header">
            <div>
                <span>{{ $student->student_name }}</span>
                <h2>My Results</h2>
            </div>
        </div>

        <form method="GET" action="{{ route('student.results.index') }}" class="filter-bar">
            <input type="search" name="search" value="{{ $filters['search'] ?? '' }}" class="form-control" placeholder="Search exam title">
            <select name="result" class="form-select">
                <option value="">All results</option>
                <option value="passed" @selected(($filters['result'] ?? '') === 'passed')>Passed</option>
                <option value="failed" @selected(($filters['result'] ?? '') === 'failed')>Failed</option>
            </select>
            <button type="submit" class="btn btn-soft">
                <i class="bi bi-search"></i> Filter
            </button>
        </form>

        @if ($attempts->isEmpty())
            <div class="empty-state">
                <i class="bi bi-bar-chart"></i>
                <h3>No results found</h3>
                <p>Your submitted exam results will appear here.</p>
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
                                    <i class="bi {{ $attempt->is_passed ? 'bi-check-circle-fill' : 'bi-x-circle-fill' }}"></i>
                                    {{ $attempt->is_passed ? 'Passed performance' : 'Needs improvement' }}
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
                        <a href="{{ route('student.results.show', $attempt) }}" class="btn btn-sm btn-soft performance-action">
                            <i class="bi bi-eye-fill"></i> View Details
                        </a>
                    </div>
                @endforeach
            </div>
            {{ $attempts->links() }}
        @endif
    </section>
@endsection
