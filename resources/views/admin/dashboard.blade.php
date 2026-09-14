@extends('layouts.admin')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('content')
    <div class="dashboard-hero">
        <div>
            <span>Operational Overview</span>
            <h2>Quiz Management is ready for all-branch operations.</h2>
            <p class="mobile-hide">Manage students, exams, questions, and results across all branches from one place.</p>
        </div>
    </div>

    <div class="row g-3 mb-4 dashboard-metric-row">
        <div class="col-md-6">
            <div class="metric-card dashboard-metric-card metric-primary">
                <i class="bi bi-diagram-3-fill"></i>
                <div>
                    <span>Total Branches</span>
                    <strong>{{ $branchCount }}</strong>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="metric-card dashboard-metric-card metric-success">
                <i class="bi bi-people-fill"></i>
                <div>
                    <span>Total Students</span>
                    <strong>{{ $studentCount }}</strong>
                </div>
            </div>
        </div>
    </div>

    <section class="content-panel">
        <div class="panel-header">
            <div>
                <h2>Recent Branches</h2>
                <p>Your latest branch records appear here.</p>
            </div>
            <a href="{{ route('admin.branches.index') }}" class="btn btn-outline-primary btn-sm">View all</a>
        </div>

        @if ($recentBranches->isEmpty())
            <div class="empty-state">
                <i class="bi bi-building-add"></i>
                <h3>No branches yet</h3>
                <p>Create your first branch to begin organizing the platform.</p>
            </div>
        @else
            <div class="table-responsive">
                <table class="table align-middle admin-table" data-mobile-direct-details>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Created</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($recentBranches as $branch)
                            <tr>
                                <td>{{ $branch->name }}</td>
                                <td>{{ $branch->created_at->format('d M Y') }}</td>
                                <td class="text-end">
                                    <a href="{{ route('admin.branches.show', $branch) }}" class="btn btn-sm btn-soft">Details</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
@endsection
