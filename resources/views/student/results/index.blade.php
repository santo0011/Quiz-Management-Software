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

        @if ($attempts->isEmpty())
            <div class="empty-state">
                <i class="bi bi-bar-chart"></i>
                <h3>No completed exams</h3>
                <p>Your submitted exam results will appear here.</p>
            </div>
        @else
            <div class="result-list">
                @foreach ($attempts as $attempt)
                    <a href="{{ route('student.results.show', $attempt) }}" class="result-row">
                        <div>
                            <strong>{{ $attempt->exam?->title }}</strong>
                            <span>{{ $attempt->schoolClass?->name }} · {{ $attempt->submitted_at?->format('d M Y, h:i A') }}</span>
                        </div>
                        <span class="status-badge {{ $attempt->is_passed ? 'status-published' : 'status-closed' }}">{{ $attempt->percentage }}%</span>
                    </a>
                @endforeach
            </div>
            {{ $attempts->links() }}
        @endif
    </section>
@endsection
