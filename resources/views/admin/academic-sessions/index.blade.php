@extends('layouts.admin')

@section('title', 'Academic Sessions')
@section('page-title', 'Academic Sessions')

@section('content')
    <section class="content-panel">
        <div class="panel-header">
            <div>
                <h2>Academic Sessions</h2>
                <p>Manage the academic years students, exams, and results are organized under.</p>
            </div>
            <button type="button" class="btn btn-primary" data-bs-toggle="offcanvas" data-bs-target="#addSessionDrawer" aria-controls="addSessionDrawer">
                <i class="bi bi-plus-circle-fill"></i>
                Add Session
            </button>
        </div>

        <form method="GET" action="{{ route('admin.academic-sessions.index') }}" class="filter-bar compact-filter-bar">
            <input type="search" name="search" value="{{ $filters['search'] ?? '' }}" class="form-control" placeholder="Search session name">
            <button type="submit" class="btn btn-soft">
                <i class="bi bi-search"></i>
                Filter
            </button>
        </form>

        @if ($sessions->isEmpty())
            <div class="empty-state">
                <i class="bi bi-calendar-range"></i>
                <h3>No academic sessions found</h3>
                <p>Add a session before creating students or exams.</p>
            </div>
        @else
            <div class="table-responsive">
                <table class="table align-middle admin-table">
                    <thead>
                        <tr>
                            <th>Session Name</th>
                            <th>Start Date</th>
                            <th>End Date</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($sessions as $item)
                            <tr>
                                <td><strong>{{ $item->name }}</strong></td>
                                <td>{{ $item->start_date->format('d M Y') }}</td>
                                <td>{{ $item->end_date->format('d M Y') }}</td>
                                <td>
                                    <span class="status-badge {{ $item->is_active ? 'status-published' : 'status-closed' }}">
                                        <i class="bi {{ $item->is_active ? 'bi-check-circle-fill' : 'bi-x-circle-fill' }}"></i>
                                        {{ $item->is_active ? 'Active' : 'Closed' }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    <div class="action-group">
                                        <a href="{{ route('admin.academic-sessions.show', $item) }}" class="btn btn-sm btn-soft" title="View">
                                            <i class="bi bi-eye-fill"></i>
                                        </a>
                                        <button type="button" class="btn btn-sm btn-soft" title="Edit" data-bs-toggle="offcanvas" data-bs-target="#editSessionDrawer{{ $item->id }}" aria-controls="editSessionDrawer{{ $item->id }}">
                                            <i class="bi bi-pencil-fill"></i>
                                        </button>
                                        <form method="POST" action="{{ route('admin.academic-sessions.toggle-active', $item) }}" data-confirm-toggle>
                                            @csrf
                                            <button class="btn btn-sm {{ $item->is_active ? 'btn-danger-soft' : 'btn-soft' }}" type="submit" title="{{ $item->is_active ? 'Close session' : 'Activate session' }}">
                                                <i class="bi {{ $item->is_active ? 'bi-pause-circle-fill' : 'bi-play-circle-fill' }}"></i>
                                            </button>
                                        </form>
                                        @if ($item->students_count || $item->exams_count || $item->exam_attempts_count)
                                            <span class="publish-lock-hint" data-bs-toggle="tooltip" data-bs-title="{{ \App\Models\AcademicSession::DELETE_LOCK_MESSAGE }}">
                                                <i class="bi bi-lock-fill"></i>
                                            </span>
                                        @else
                                            <form method="POST" action="{{ route('admin.academic-sessions.destroy', $item) }}" data-confirm-delete>
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn btn-sm btn-danger-soft" type="submit" title="Delete">
                                                    <i class="bi bi-trash-fill"></i>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{ $sessions->links() }}
        @endif
    </section>

    <div class="offcanvas offcanvas-end student-drawer" tabindex="-1" id="addSessionDrawer" aria-labelledby="addSessionDrawerLabel">
        <div class="offcanvas-header student-drawer-header">
            <div>
                <span class="page-kicker">Session Management</span>
                <h2 class="offcanvas-title" id="addSessionDrawerLabel">Add New Session</h2>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body">
            @include('admin.academic-sessions.partials.form', [
                'academicSession' => $academicSession,
                'action' => route('admin.academic-sessions.store'),
                'method' => 'POST',
                'button' => 'Save Session',
                'drawer' => true,
                'drawerId' => 'addSessionDrawer',
            ])
        </div>
    </div>

    @foreach ($sessions as $item)
        <div class="offcanvas offcanvas-end student-drawer" tabindex="-1" id="editSessionDrawer{{ $item->id }}" aria-labelledby="editSessionDrawerLabel{{ $item->id }}">
            <div class="offcanvas-header student-drawer-header">
                <div>
                    <span class="page-kicker">Session Management</span>
                    <h2 class="offcanvas-title" id="editSessionDrawerLabel{{ $item->id }}">Edit Session</h2>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
            </div>
            <div class="offcanvas-body">
                @include('admin.academic-sessions.partials.form', [
                    'academicSession' => $item,
                    'action' => route('admin.academic-sessions.update', $item),
                    'method' => 'PUT',
                    'button' => 'Update Session',
                    'drawer' => true,
                    'drawerId' => 'editSessionDrawer'.$item->id,
                ])
            </div>
        </div>
    @endforeach
@endsection
