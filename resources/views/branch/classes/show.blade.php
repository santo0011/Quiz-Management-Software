@extends('layouts.branch')

@section('title', 'Grade Details')
@section('page-title', 'Grade Details')

@section('content')
    <div class="student-profile-top-actions">
        <a href="{{ route('branch.classes.index') }}" class="btn btn-outline-secondary btn-student-back">
            <i class="bi bi-arrow-left"></i>
            Back
        </a>
    </div>

    <section class="content-panel narrow-panel">
        <div class="panel-header">
            <div>
                <h2>{{ $class->name }}</h2>
                <p>Grade record details.</p>
            </div>
            <button type="button" class="btn btn-primary" data-bs-toggle="offcanvas" data-bs-target="#editClassDrawer{{ $class->id }}" aria-controls="editClassDrawer{{ $class->id }}">
                <i class="bi bi-pencil-fill"></i>
                Edit
            </button>
        </div>

        <dl class="detail-list">
            <div>
                <dt>Grade Name</dt>
                <dd>{{ $class->name }}</dd>
            </div>
            <div>
                <dt>Branch</dt>
                <dd>{{ $branch->name }}</dd>
            </div>
            <div>
                <dt>Created At</dt>
                <dd>{{ $class->created_at->format('d M Y, h:i A') }}</dd>
            </div>
        </dl>
    </section>

    <div class="offcanvas offcanvas-end student-drawer" tabindex="-1" id="editClassDrawer{{ $class->id }}" aria-labelledby="editClassDrawerLabel{{ $class->id }}">
        <div class="offcanvas-header student-drawer-header">
            <div>
                <span class="page-kicker">Grade Management</span>
                <h2 class="offcanvas-title" id="editClassDrawerLabel{{ $class->id }}">Edit Grade</h2>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body">
            @include('branch.classes.partials.form', [
                'class' => $class,
                'branch' => $branch,
                'action' => route('branch.classes.update', $class),
                'method' => 'PUT',
                'button' => 'Update Grade',
                'drawer' => true,
                'drawerId' => 'editClassDrawer'.$class->id,
            ])
        </div>
    </div>
@endsection
