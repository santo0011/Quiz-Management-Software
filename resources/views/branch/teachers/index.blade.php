@extends('layouts.branch')

@section('title', 'Teachers')
@section('page-title', 'Teachers')

@section('content')
    <section class="content-panel">
        <div class="panel-header">
            <div>
                <h2>{{ $branch->name }} Teachers</h2>
                <p>Create and manage teacher accounts for this branch.</p>
            </div>
            <button type="button" class="btn btn-primary" data-bs-toggle="offcanvas" data-bs-target="#addTeacherDrawer" aria-controls="addTeacherDrawer">
                <i class="bi bi-person-plus-fill"></i>
                Add Teacher
            </button>
        </div>

        @if ($teachers->isEmpty())
            <div class="empty-state">
                <i class="bi bi-person-workspace"></i>
                <h3>No teachers found</h3>
                <p>Add a teacher to let them review results and add remarks.</p>
            </div>
        @else
            <div class="table-responsive">
                <table class="table align-middle admin-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Created</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($teachers as $teacher)
                            <tr>
                                <td><strong>{{ $teacher->name }}</strong></td>
                                <td>{{ $teacher->email }}</td>
                                <td>{{ $teacher->phone_number ?? '—' }}</td>
                                <td>{{ $teacher->created_at->format('d M Y, h:i A') }}</td>
                                <td class="text-end">
                                    <div class="action-group">
                                        <button type="button" class="btn btn-sm btn-soft" title="Edit" data-bs-toggle="offcanvas" data-bs-target="#editTeacherDrawer{{ $teacher->id }}" aria-controls="editTeacherDrawer{{ $teacher->id }}">
                                            <i class="bi bi-pencil-fill"></i>
                                        </button>
                                        <form method="POST" action="{{ route('branch.teachers.destroy', $teacher) }}" data-confirm-delete>
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

            {{ $teachers->links() }}
        @endif
    </section>

    <div class="offcanvas offcanvas-end student-drawer" tabindex="-1" id="addTeacherDrawer" aria-labelledby="addTeacherDrawerLabel">
        <div class="offcanvas-header student-drawer-header">
            <div>
                <span class="page-kicker">Teacher Management</span>
                <h2 class="offcanvas-title" id="addTeacherDrawerLabel">Add New Teacher</h2>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body">
            @include('branch.teachers.partials.form', [
                'teacher' => new App\Models\Teacher(),
                'action' => route('branch.teachers.store'),
                'method' => 'POST',
                'button' => 'Save Teacher',
                'drawer' => true,
                'drawerId' => 'addTeacherDrawer',
            ])
        </div>
    </div>

    @foreach ($teachers as $teacher)
        <div class="offcanvas offcanvas-end student-drawer" tabindex="-1" id="editTeacherDrawer{{ $teacher->id }}" aria-labelledby="editTeacherDrawerLabel{{ $teacher->id }}">
            <div class="offcanvas-header student-drawer-header">
                <div>
                    <span class="page-kicker">Teacher Management</span>
                    <h2 class="offcanvas-title" id="editTeacherDrawerLabel{{ $teacher->id }}">Edit Teacher</h2>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
            </div>
            <div class="offcanvas-body">
                @include('branch.teachers.partials.form', [
                    'teacher' => $teacher,
                    'action' => route('branch.teachers.update', $teacher),
                    'method' => 'PUT',
                    'button' => 'Update Teacher',
                    'drawer' => true,
                    'drawerId' => 'editTeacherDrawer'.$teacher->id,
                    'fieldSuffix' => '_teacher_'.$teacher->id,
                ])
                @include('admin.partials.change-password-form', [
                    'action' => route('branch.teachers.password.update', $teacher),
                    'fieldSuffix' => '_teacher_'.$teacher->id,
                ])
            </div>
        </div>
    @endforeach
@endsection
