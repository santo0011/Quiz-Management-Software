@extends('layouts.branch')

@section('title', 'Branch Dashboard')
@section('page-title', 'Branch Dashboard')

@section('content')
    <div class="dashboard-hero">
        <div>
            <span>Branch Workspace</span>
            <h2>{{ $branch?->name ?? 'Branch' }} workspace is ready.</h2>
            <p>Manage branch students here. Exams, questions, and results are reserved for the next stages.</p>
        </div>
        <a href="{{ route('branch.students.index') }}" class="btn btn-light">
            <i class="bi bi-people-fill"></i>
            Manage Students
        </a>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="metric-card">
                <i class="bi bi-people-fill"></i>
                <span>Students</span>
                <strong>{{ $studentCount }}</strong>
            </div>
        </div>
        <div class="col-md-4">
            <div class="metric-card muted">
                <i class="bi bi-journal-check"></i>
                <span>Exams</span>
                <strong>Coming Soon</strong>
            </div>
        </div>
        <div class="col-md-4">
            <div class="metric-card muted">
                <i class="bi bi-bar-chart-fill"></i>
                <span>Results</span>
                <strong>Coming Soon</strong>
            </div>
        </div>
    </div>

    <section class="content-panel">
        <div class="panel-header">
            <div>
                <h2>Recent Students</h2>
                <p>Latest student records for this branch.</p>
            </div>
            <a href="{{ route('branch.students.index') }}" class="btn btn-outline-primary btn-sm">View all</a>
        </div>

        @if ($recentStudents->isEmpty())
            <div class="empty-state">
                <i class="bi bi-person-plus"></i>
                <h3>No students yet</h3>
                <p>Add students manually from the branch student module.</p>
            </div>
        @else
            <div class="table-responsive">
                <table class="table align-middle admin-table" data-mobile-direct-details>
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Class</th>
                            <th>Email</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($recentStudents as $student)
                            <tr>
                                <td><strong>{{ $student->student_name }}</strong></td>
                                <td>{{ $student->class }}</td>
                                <td>{{ $student->email }}</td>
                                <td class="text-end">
                                    <a href="{{ route('branch.students.show', $student) }}" class="btn btn-sm btn-soft">Details</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
@endsection
