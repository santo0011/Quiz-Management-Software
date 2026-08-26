@extends('layouts.admin')

@section('title', 'Subject Details')
@section('page-title', 'Subject Details')

@section('content')
    <div class="student-profile-top-actions">
        <a href="{{ route('admin.subjects.index') }}" class="btn btn-outline-secondary btn-student-back">
            <i class="bi bi-arrow-left"></i>
            Back
        </a>
    </div>

    <section class="content-panel narrow-panel">
        <div class="panel-header">
            <div>
                <h2>{{ $subject->name }}</h2>
                <p>Subject record details.</p>
            </div>
            <button type="button" class="btn btn-primary" data-bs-toggle="offcanvas" data-bs-target="#editSubjectDrawer{{ $subject->id }}" aria-controls="editSubjectDrawer{{ $subject->id }}">
                <i class="bi bi-pencil-fill"></i>
                Edit
            </button>
        </div>

        <dl class="detail-list">
            <div>
                <dt>Subject Name</dt>
                <dd>{{ $subject->name }}</dd>
            </div>
            <div>
                <dt>Created At</dt>
                <dd>{{ $subject->created_at->format('d M Y, h:i A') }}</dd>
            </div>
        </dl>
    </section>

    <div class="offcanvas offcanvas-end student-drawer" tabindex="-1" id="editSubjectDrawer{{ $subject->id }}" aria-labelledby="editSubjectDrawerLabel{{ $subject->id }}">
        <div class="offcanvas-header student-drawer-header">
            <div>
                <span class="page-kicker">Subject Management</span>
                <h2 class="offcanvas-title" id="editSubjectDrawerLabel{{ $subject->id }}">Edit Subject</h2>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body">
            @include('admin.subjects.partials.form', [
                'subject' => $subject,
                'action' => route('admin.subjects.update', $subject),
                'method' => 'PUT',
                'button' => 'Update Subject',
                'drawer' => true,
                'drawerId' => 'editSubjectDrawer'.$subject->id,
            ])
        </div>
    </div>
@endsection
