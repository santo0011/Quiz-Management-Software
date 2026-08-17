@extends('layouts.student')

@section('title', 'My Exams')
@section('page-title', 'My Exams')

@section('content')
    <section class="student-section">
        <div class="student-section-header">
            <div>
                <span>Completed</span>
                <h2>My Exams</h2>
            </div>
        </div>

        @if ($attempts->isEmpty())
            <div class="empty-state">
                <i class="bi bi-clipboard-check"></i>
                <h3>No exams attempted yet</h3>
                <p>Your completed exam attempts will be listed here.</p>
            </div>
        @else
            <div class="table-responsive">
                <table class="table align-middle admin-table">
                    <thead>
                        <tr>
                            <th>Exam</th>
                            <th>Attempt Date</th>
                            <th>Marks</th>
                            <th>Percentage</th>
                            <th>Result</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($attempts as $attempt)
                            <tr>
                                <td><strong>{{ $attempt->exam?->title }}</strong></td>
                                <td>{{ $attempt->submitted_at?->format('d M Y, h:i A') }}</td>
                                <td>{{ $attempt->obtained_marks }} / {{ $attempt->exam?->total_marks }}</td>
                                <td>{{ $attempt->percentage }}%</td>
                                <td>
                                    <span class="status-badge {{ $attempt->is_passed ? 'status-published' : 'status-closed' }}">
                                        {{ $attempt->is_passed ? 'Passed' : 'Failed' }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('student.results.show', $attempt) }}" class="btn btn-sm btn-soft">
                                        <i class="bi bi-eye-fill"></i> View Result
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