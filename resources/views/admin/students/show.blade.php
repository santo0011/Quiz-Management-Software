@extends('layouts.admin')

@section('title', 'Student Details')
@section('page-title', 'Student Details')

@section('content')
    @include('students.partials.show', [
        'prefix' => 'admin',
        'student' => $student,
        'selectedBranch' => $student->branch,
    ])

    <div class="offcanvas offcanvas-end student-drawer" tabindex="-1" id="editStudentDrawer{{ $student->id }}" aria-labelledby="editStudentDrawerLabel{{ $student->id }}">
        <div class="offcanvas-header student-drawer-header">
            <div>
                <span class="page-kicker">Student Management</span>
                <h2 class="offcanvas-title" id="editStudentDrawerLabel{{ $student->id }}">Edit Student</h2>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body">
            @include('admin.students.partials.form', [
                'student' => $student,
                'selectedBranch' => $student->branch,
                'classes' => $student->branch->classes()->orderBy('name')->get(),
                'action' => route('admin.students.update', $student),
                'method' => 'PUT',
                'button' => 'Update Student',
                'drawer' => true,
                'drawerId' => 'editStudentDrawer'.$student->id,
            ])
        </div>
    </div>
@endsection