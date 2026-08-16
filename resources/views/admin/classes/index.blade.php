@extends('layouts.admin')

@section('title', 'Classes')
@section('page-title', 'Classes')

@section('content')
    <section class="content-panel">
        <div class="panel-header">
            <div>
                <h2>{{ $selectedBranch->name }} Classes</h2>
                <p>Classes are managed for the active branch selected in Select Branch.</p>
            </div>
            <button type="button" class="btn btn-primary" data-bs-toggle="offcanvas" data-bs-target="#addClassDrawer" aria-controls="addClassDrawer">
                <i class="bi bi-plus-circle-fill"></i>
                Add Class
            </button>
        </div>

        <form method="GET" action="{{ route('admin.classes.index') }}" class="filter-bar compact-filter-bar">
            <input type="search" name="search" value="{{ $filters['search'] ?? '' }}" class="form-control" placeholder="Search class name">
            <button type="submit" class="btn btn-soft">
                <i class="bi bi-search"></i>
                Filter
            </button>
        </form>

        @if ($classes->isEmpty())
            <div class="empty-state">
                <i class="bi bi-collection"></i>
                <h3>No classes found</h3>
                <p>Add classes under branches before assigning students.</p>
            </div>
        @else
            <div class="table-responsive">
                <table class="table align-middle admin-table">
                    <thead>
                        <tr>
                            <th>Class Name</th>
                            <th>Created</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($classes as $schoolClass)
                            <tr>
                                <td><strong>{{ $schoolClass->name }}</strong></td>
                                <td>{{ $schoolClass->created_at->format('d M Y, h:i A') }}</td>
                                <td class="text-end">
                                    <div class="action-group">
                                        <a href="{{ route('admin.classes.show', $schoolClass) }}" class="btn btn-sm btn-soft" title="View">
                                            <i class="bi bi-eye-fill"></i>
                                        </a>
                                        <button type="button" class="btn btn-sm btn-soft" title="Edit" data-bs-toggle="offcanvas" data-bs-target="#editClassDrawer{{ $schoolClass->id }}" aria-controls="editClassDrawer{{ $schoolClass->id }}">
                                            <i class="bi bi-pencil-fill"></i>
                                        </button>
                                        <form method="POST" action="{{ route('admin.classes.destroy', $schoolClass) }}" data-confirm-delete>
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-sm btn-danger-soft" type="submit" title="Delete">
                                                <i class="bi bi-trash-fill"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{ $classes->links() }}
        @endif
    </section>

    <div class="offcanvas offcanvas-end student-drawer" tabindex="-1" id="addClassDrawer" aria-labelledby="addClassDrawerLabel">
        <div class="offcanvas-header student-drawer-header">
            <div>
                <span class="page-kicker">Class Management</span>
                <h2 class="offcanvas-title" id="addClassDrawerLabel">Add New Class</h2>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body">
            @include('admin.classes.partials.form', [
                'class' => $class,
                'selectedBranch' => $selectedBranch,
                'action' => route('admin.classes.store'),
                'method' => 'POST',
                'button' => 'Save Class',
                'drawer' => true,
                'drawerId' => 'addClassDrawer',
            ])
        </div>
    </div>

    @foreach ($classes as $schoolClass)
        <div class="offcanvas offcanvas-end student-drawer" tabindex="-1" id="editClassDrawer{{ $schoolClass->id }}" aria-labelledby="editClassDrawerLabel{{ $schoolClass->id }}">
            <div class="offcanvas-header student-drawer-header">
                <div>
                    <span class="page-kicker">Class Management</span>
                    <h2 class="offcanvas-title" id="editClassDrawerLabel{{ $schoolClass->id }}">Edit Class</h2>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
            </div>
            <div class="offcanvas-body">
                @include('admin.classes.partials.form', [
                    'class' => $schoolClass,
                    'selectedBranch' => $selectedBranch,
                    'action' => route('admin.classes.update', $schoolClass),
                    'method' => 'PUT',
                    'button' => 'Update Class',
                    'drawer' => true,
                    'drawerId' => 'editClassDrawer'.$schoolClass->id,
                ])
            </div>
        </div>
    @endforeach
@endsection
