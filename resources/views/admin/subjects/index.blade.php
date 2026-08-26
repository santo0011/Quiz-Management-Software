@extends('layouts.admin')

@section('title', 'Subjects')
@section('page-title', 'Subjects')

@section('content')
    <section class="content-panel">
        <div class="panel-header">
            <div>
                <h2>Subjects</h2>
                <p>Manage the global list of subjects available across all branches.</p>
            </div>
            <button type="button" class="btn btn-primary" data-bs-toggle="offcanvas" data-bs-target="#addSubjectDrawer" aria-controls="addSubjectDrawer">
                <i class="bi bi-plus-circle-fill"></i>
                Add Subject
            </button>
        </div>

        <form method="GET" action="{{ route('admin.subjects.index') }}" class="filter-bar compact-filter-bar">
            <input type="search" name="search" value="{{ $filters['search'] ?? '' }}" class="form-control" placeholder="Search subject name">
            <button type="submit" class="btn btn-soft">
                <i class="bi bi-search"></i>
                Filter
            </button>
        </form>

        @if ($subjects->isEmpty())
            <div class="empty-state">
                <i class="bi bi-book"></i>
                <h3>No subjects found</h3>
                <p>Add subjects before assigning them to students or exams.</p>
            </div>
        @else
            <div class="table-responsive">
                <table class="table align-middle admin-table">
                    <thead>
                        <tr>
                            <th>Subject Name</th>
                            <th>Created</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($subjects as $item)
                            <tr>
                                <td><strong>{{ $item->name }}</strong></td>
                                <td>{{ $item->created_at->format('d M Y, h:i A') }}</td>
                                <td class="text-end">
                                    <div class="action-group">
                                        <a href="{{ route('admin.subjects.show', $item) }}" class="btn btn-sm btn-soft" title="View">
                                            <i class="bi bi-eye-fill"></i>
                                        </a>
                                        <button type="button" class="btn btn-sm btn-soft" title="Edit" data-bs-toggle="offcanvas" data-bs-target="#editSubjectDrawer{{ $item->id }}" aria-controls="editSubjectDrawer{{ $item->id }}">
                                            <i class="bi bi-pencil-fill"></i>
                                        </button>
                                        <form method="POST" action="{{ route('admin.subjects.destroy', $item) }}" data-confirm-delete>
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

            {{ $subjects->links() }}
        @endif
    </section>

    <div class="offcanvas offcanvas-end student-drawer" tabindex="-1" id="addSubjectDrawer" aria-labelledby="addSubjectDrawerLabel">
        <div class="offcanvas-header student-drawer-header">
            <div>
                <span class="page-kicker">Subject Management</span>
                <h2 class="offcanvas-title" id="addSubjectDrawerLabel">Add New Subject</h2>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body">
            @include('admin.subjects.partials.form', [
                'subject' => $subject,
                'action' => route('admin.subjects.store'),
                'method' => 'POST',
                'button' => 'Save Subject',
                'drawer' => true,
                'drawerId' => 'addSubjectDrawer',
            ])
        </div>
    </div>

    @foreach ($subjects as $item)
        <div class="offcanvas offcanvas-end student-drawer" tabindex="-1" id="editSubjectDrawer{{ $item->id }}" aria-labelledby="editSubjectDrawerLabel{{ $item->id }}">
            <div class="offcanvas-header student-drawer-header">
                <div>
                    <span class="page-kicker">Subject Management</span>
                    <h2 class="offcanvas-title" id="editSubjectDrawerLabel{{ $item->id }}">Edit Subject</h2>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
            </div>
            <div class="offcanvas-body">
                @include('admin.subjects.partials.form', [
                    'subject' => $item,
                    'action' => route('admin.subjects.update', $item),
                    'method' => 'PUT',
                    'button' => 'Update Subject',
                    'drawer' => true,
                    'drawerId' => 'editSubjectDrawer'.$item->id,
                ])
            </div>
        </div>
    @endforeach
@endsection
