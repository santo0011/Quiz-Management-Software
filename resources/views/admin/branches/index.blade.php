@extends('layouts.admin')

@section('title', 'Branches')
@section('page-title', 'Branches')

@section('content')
    <section class="content-panel">
        <div class="panel-header">
            <div>
                <h2>Branch Management</h2>
                <p>Create and maintain all organizational branches.</p>
            </div>
            <button type="button" class="btn btn-primary" data-bs-toggle="offcanvas" data-bs-target="#addBranchDrawer" aria-controls="addBranchDrawer">
                <i class="bi bi-plus-circle-fill"></i>
                Add Branch
            </button>
        </div>

        @if ($branches->isEmpty())
            <div class="empty-state">
                <i class="bi bi-building"></i>
                <h3>No branches found</h3>
                <p>Add a branch to start building the quiz management structure.</p>
            </div>
        @else
            <div class="table-responsive">
                <table class="table align-middle admin-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th>Updated</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($branches as $branch)
                            <tr>
                                <td><strong>{{ $branch->name }}</strong></td>
                                <td>{{ $branch->email }}</td>
                                <td>
                                    <span class="status-badge {{ $branch->is_active ? 'status-published' : 'status-closed' }}">
                                        <i class="bi {{ $branch->is_active ? 'bi-check-circle-fill' : 'bi-x-circle-fill' }}"></i>
                                        {{ $branch->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td>{{ $branch->created_at->format('d M Y, h:i A') }}</td>
                                <td>{{ $branch->updated_at->format('d M Y, h:i A') }}</td>
                                <td class="text-end">
                                    <div class="action-group">
                                        <a href="{{ route('admin.branches.show', $branch) }}" class="btn btn-sm btn-soft" title="View">
                                            <i class="bi bi-eye-fill"></i>
                                        </a>
                                        <button type="button" class="btn btn-sm btn-soft" title="Edit" data-bs-toggle="offcanvas" data-bs-target="#editBranchDrawer{{ $branch->id }}" aria-controls="editBranchDrawer{{ $branch->id }}">
                                            <i class="bi bi-pencil-fill"></i>
                                        </button>
                                        <form method="POST" action="{{ route('admin.branches.toggle-active', $branch) }}" data-confirm-toggle>
                                            @csrf
                                            <button class="btn btn-sm {{ $branch->is_active ? 'btn-danger-soft' : 'btn-soft' }}" type="submit" title="{{ $branch->is_active ? 'Deactivate' : 'Activate' }}">
                                                <i class="bi {{ $branch->is_active ? 'bi-pause-circle-fill' : 'bi-play-circle-fill' }}"></i>
                                            </button>
                                        </form>
                                        <form method="POST" action="{{ route('admin.branches.destroy', $branch) }}" data-confirm-delete>
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

            {{ $branches->links() }}
        @endif
    </section>

    <div class="offcanvas offcanvas-end student-drawer" tabindex="-1" id="addBranchDrawer" aria-labelledby="addBranchDrawerLabel">
        <div class="offcanvas-header student-drawer-header">
            <div>
                <span class="page-kicker">Branch Management</span>
                <h2 class="offcanvas-title" id="addBranchDrawerLabel">Add New Branch</h2>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body">
            @include('admin.branches.partials.form', [
                'branch' => new App\Models\Branch(),
                'action' => route('admin.branches.store'),
                'method' => 'POST',
                'button' => 'Save Branch',
                'drawer' => true,
                'drawerId' => 'addBranchDrawer',
            ])
        </div>
    </div>

    @foreach ($branches as $branch)
        <div class="offcanvas offcanvas-end student-drawer" tabindex="-1" id="editBranchDrawer{{ $branch->id }}" aria-labelledby="editBranchDrawerLabel{{ $branch->id }}">
            <div class="offcanvas-header student-drawer-header">
                <div>
                    <span class="page-kicker">Branch Management</span>
                    <h2 class="offcanvas-title" id="editBranchDrawerLabel{{ $branch->id }}">Edit Branch</h2>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
            </div>
            <div class="offcanvas-body">
                @include('admin.branches.partials.form', [
                    'branch' => $branch,
                    'action' => route('admin.branches.update', $branch),
                    'method' => 'PUT',
                    'button' => 'Update Branch',
                    'drawer' => true,
                    'drawerId' => 'editBranchDrawer'.$branch->id,
                ])
                @include('admin.partials.change-password-form', [
                    'action' => route('admin.branches.password.update', $branch),
                    'fieldSuffix' => '_branch_'.$branch->id,
                    'drawerId' => 'editBranchDrawer'.$branch->id,
                ])
            </div>
        </div>
    @endforeach
@endsection
