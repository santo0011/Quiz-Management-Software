@extends('layouts.branch')

@section('title', 'Student Details')
@section('page-title', 'Student Details')

@section('content')
    <section class="content-panel narrow-panel">
        <div class="panel-header">
            <div>
                <h2>{{ $student->student_name }}</h2>
                <p>Branch: {{ $branch->name }}</p>
            </div>
            <button type="button" class="btn btn-primary" data-bs-toggle="offcanvas" data-bs-target="#editStudentDrawer{{ $student->id }}" aria-controls="editStudentDrawer{{ $student->id }}">
                <i class="bi bi-pencil-fill"></i>
                Edit
            </button>
        </div>

        @include('branch.students.partials.details', ['student' => $student])

        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('branch.students.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i>
                Back
            </a>
            <form method="POST" action="{{ route('branch.students.destroy', $student) }}" data-confirm-delete>
                @csrf
                @method('DELETE')
                <button class="btn btn-outline-danger" type="submit">
                    <i class="bi bi-trash-fill"></i>
                    Delete
                </button>
            </form>
        </div>
    </section>

    <div class="offcanvas offcanvas-end student-drawer" tabindex="-1" id="editStudentDrawer{{ $student->id }}" aria-labelledby="editStudentDrawerLabel{{ $student->id }}">
        <div class="offcanvas-header student-drawer-header">
            <div>
                <span class="page-kicker">Student Management</span>
                <h2 class="offcanvas-title" id="editStudentDrawerLabel{{ $student->id }}">Edit Student</h2>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body">
            @include('branch.students.partials.form', [
                'student' => $student,
                'branch' => $branch,
                'classes' => $branch->classes()->orderBy('name')->get(),
                'action' => route('branch.students.update', $student),
                'method' => 'PUT',
                'button' => 'Update Student',
                'drawer' => true,
                'drawerId' => 'editStudentDrawer'.$student->id,
            ])
        </div>
    </div>
@endsection
