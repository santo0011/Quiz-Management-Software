@extends('layouts.admin')

@section('title', 'Logs')
@section('page-title', 'Login Activity Logs')

@section('content')
    <section class="content-panel mb-4">
        <div class="panel-header">
            <div>
                <h2>Login Activity</h2>
                <p>Every login attempt across Super Admin, Branch, Teacher, and Student accounts — newest first.</p>
            </div>
        </div>

        <form method="GET" action="{{ route('admin.logs.index') }}" class="filter-bar logs-filter-bar mb-0">
            <select name="role" class="form-select form-control">
                <option value="">All roles</option>
                @foreach ($roles as $role)
                    <option value="{{ $role }}" @selected(($filters['role'] ?? '') === $role)>{{ $role }}</option>
                @endforeach
            </select>
            <select name="login_method" class="form-select form-control">
                <option value="">All login methods</option>
                @foreach ($loginMethods as $method)
                    <option value="{{ $method }}" @selected(($filters['login_method'] ?? '') === $method)>{{ $method }}</option>
                @endforeach
            </select>
            <select name="status" class="form-select form-control">
                <option value="">All statuses</option>
                <option value="success" @selected(($filters['status'] ?? '') === 'success')>Success</option>
                <option value="failed" @selected(($filters['status'] ?? '') === 'failed')>Failed</option>
            </select>
            <input type="date" name="date" value="{{ $filters['date'] ?? '' }}" class="form-control" placeholder="Date">
            <div class="filter-bar-actions">
                <button type="submit" class="btn btn-soft"><i class="bi bi-search"></i> Filter</button>
                @if (array_filter($filters))
                    <a href="{{ route('admin.logs.index') }}" class="btn btn-outline-secondary">Clear</a>
                @endif
            </div>
        </form>
    </section>

    <section class="content-panel">
        @if ($logs->isEmpty())
            <div class="empty-state">
                <i class="bi bi-clock-history"></i>
                <h3>No login activity found</h3>
                <p>No login records match these filters.</p>
            </div>
        @else
            <div class="table-responsive">
                <table class="table align-middle admin-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>ID/Email</th>
                            <th>Role</th>
                            <th>Branch</th>
                            <th>Login Method</th>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($logs as $log)
                            <tr>
                                <td><strong>{{ $log->name ?? '—' }}</strong></td>
                                <td>{{ $log->identifier ?? '—' }}</td>
                                <td>{{ $log->role }}</td>
                                <td>{{ $log->branch_name ?? '—' }}</td>
                                <td>{{ $log->login_method }}</td>
                                <td>{{ $log->created_at->format('d-m-Y') }}</td>
                                <td>{{ $log->created_at->format('h:i A') }}</td>
                                <td>
                                    @if ($log->status === 'success')
                                        <span class="status-badge status-published">Success</span>
                                    @else
                                        <span class="status-badge status-closed" title="{{ $log->failure_reason }}">Failed</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{ $logs->links() }}
        @endif
    </section>
@endsection
