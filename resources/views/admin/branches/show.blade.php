@extends('layouts.admin')

@section('title', 'Branch Details')
@section('page-title', 'Branch Details')

@section('content')
    <section class="content-panel narrow-panel">
        <div class="panel-header">
            <div>
                <h2>{{ $branch->name }}</h2>
                <p>Branch record details and timeline.</p>
            </div>
            <button type="button" class="btn btn-primary" data-bs-toggle="offcanvas" data-bs-target="#editBranchDrawer{{ $branch->id }}" aria-controls="editBranchDrawer{{ $branch->id }}">
                <i class="bi bi-pencil-fill"></i>
                Edit
            </button>
        </div>

        <dl class="detail-list">
            <div>
                <dt>Branch Name</dt>
                <dd>{{ $branch->name }}</dd>
            </div>
            <div>
                <dt>Branch Email</dt>
                <dd>{{ $branch->email }}</dd>
            </div>
            <div>
                <dt>Created At</dt>
                <dd>{{ $branch->created_at->format('d M Y, h:i A') }}</dd>
            </div>
            <div>
                <dt>Last Updated</dt>
                <dd>{{ $branch->updated_at->format('d M Y, h:i A') }}</dd>
            </div>
        </dl>

        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('admin.branches.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i>
                Back
            </a>
            <form method="POST" action="{{ route('admin.branches.destroy', $branch) }}" data-confirm-delete>
                @csrf
                @method('DELETE')
                <button class="btn btn-outline-danger" type="submit">
                    <i class="bi bi-trash-fill"></i>
                    Delete
                </button>
            </form>
        </div>
    </section>

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
        </div>
    </div>
@endsection
