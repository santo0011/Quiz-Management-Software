@extends('layouts.student')

@section('title', 'My Results')
@section('page-title', 'Results')

@section('content')
    <section class="student-section mb-4">
        <div class="student-section-header">
            <div>
                <span>{{ $student->student_name }}</span>
                <h2>My Results</h2>
            </div>
        </div>

        <form method="GET" action="{{ route('student.results.index') }}" class="filter-bar mb-0">
            <input type="search" name="search" value="{{ $filters['search'] ?? '' }}" class="form-control" placeholder="Search exam title">
            <button type="submit" class="btn btn-soft">
                <i class="bi bi-search"></i> Filter
            </button>
        </form>
    </section>

    <section class="student-section">
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
                            <div class="performance-icon">
                                <i class="bi bi-graph-up-arrow"></i>
                            </div>
                            <div class="performance-info">
                                <h4>{{ $attempt->exam?->title }}</h4>
                                <span class="performance-date">
                                    <i class="bi bi-calendar-check"></i>
                                    {{ $attempt->submitted_at?->format('d M Y') }}
                                </span>
                            </div>
                        </div>
                        <div class="performance-metrics">
                            <div class="performance-metric">
                                <span>Marks Obtained</span>
                                <strong>{{ $attempt->obtained_marks }} / {{ $attempt->exam?->total_marks }}</strong>
                            </div>
                            <div class="performance-metric">
                                <span>Percentage</span>
                                <strong>{{ $attempt->percentage }}%</strong>
                            </div>
                            <div class="performance-meter" aria-label="Result percentage">
                                <span style="width: {{ min(100, max(0, $attempt->percentage)) }}%"></span>
                            </div>
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
