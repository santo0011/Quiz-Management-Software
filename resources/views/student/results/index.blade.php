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
            <div class="table-responsive">
                <table class="table align-middle admin-table">
                    <thead>
                        <tr>
                            <th>Exam</th>
                            <th>Total Marks</th>
                            <th>Obtained</th>
                            <th>Percentage</th>
                            <th>Correct</th>
                            <th>Wrong</th>
                            <th>Unanswered</th>
                            <th>Result</th>
                            <th>Exam Date</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($attempts as $attempt)
                            <tr>
                                <td><strong>{{ $attempt->exam?->title }}</strong></td>
                                <td>{{ $attempt->exam?->total_marks }}</td>
                                <td>{{ $attempt->obtained_marks }}</td>
                                <td>{{ $attempt->percentage }}%</td>
                                <td><span class="text-success">{{ $attempt->correct_count }}</span></td>
                                <td><span class="text-danger">{{ $attempt->wrong_count }}</span></td>
                                <td>{{ $attempt->unanswered_count }}</td>
                                <td>
                                    <span class="status-badge {{ $attempt->is_passed ? 'status-published' : 'status-closed' }}">
                                        {{ $attempt->is_passed ? 'Passed' : 'Failed' }}
                                    </span>
                                </td>
                                <td>{{ $attempt->submitted_at?->format('d M Y, h:i A') }}</td>
                                <td class="text-end">
                                    <a href="{{ route('student.results.show', $attempt) }}" class="btn btn-sm btn-soft">
                                        <i class="bi bi-eye-fill"></i> View
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            {{ $attempts->links() }}
        @endif
    </section>
@endsection