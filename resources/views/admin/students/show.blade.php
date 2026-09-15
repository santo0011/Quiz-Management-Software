@extends('layouts.admin')

@section('title', 'Student Details')
@section('page-title', 'Student Details')

@section('content')
    @include('students.partials.show', [
        'prefix' => 'admin',
        'student' => $student,
        'selectedBranch' => $student->branch,
    ])

    <div class="offcanvas offcanvas-end student-drawer" tabindex="-1" id="manageSubjectsDrawer{{ $student->id }}" aria-labelledby="manageSubjectsDrawerLabel{{ $student->id }}">
        <div class="offcanvas-header student-drawer-header">
            <div>
                <span class="page-kicker">Student Management</span>
                <h2 class="offcanvas-title" id="manageSubjectsDrawerLabel{{ $student->id }}">Manage Subjects</h2>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body">
            @include('students.partials.manage-subjects-form', [
                'student' => $student,
                'subjects' => \App\Models\Subject::orderBy('name')->get(),
                'action' => route('admin.students.subjects.update', $student),
                'drawer' => true,
                'drawerId' => 'manageSubjectsDrawer'.$student->id,
            ])
        </div>
    </div>
@endsection