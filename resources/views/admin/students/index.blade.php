@extends('layouts.admin')

@section('title', 'Students')
@section('page-title', 'Students')

@section('content')
    <section class="content-panel">
        <div class="panel-header">
            <div>
                <h2>{{ $selectedBranch->name }} Students</h2>
                <p>Current Branch: {{ $selectedBranch->name }}</p>
            </div>
            <button type="button" class="btn btn-primary" data-bs-toggle="offcanvas" data-bs-target="#addStudentDrawer" aria-controls="addStudentDrawer">
                <i class="bi bi-person-plus-fill"></i>
                Add Student
            </button>
        </div>

        <form method="GET" action="{{ route('admin.students.index') }}" class="filter-bar">
            <input type="search" name="search" value="{{ $filters['search'] ?? '' }}" class="form-control" placeholder="Search name, guardian, email, phone">
            <select name="class" class="form-select">
                <option value="">All classes</option>
                @foreach ($classes as $class)
                    <option value="{{ $class->name }}" @selected(($filters['class'] ?? '') === $class->name)>{{ $class->name }}</option>
                @endforeach
            </select>
            <button type="submit" class="btn btn-soft">
                <i class="bi bi-search"></i>
                Filter
            </button>
        </form>

        @include('admin.students.partials.table', ['students' => $students])
    </section>

    <div class="offcanvas offcanvas-end student-drawer" tabindex="-1" id="addStudentDrawer" aria-labelledby="addStudentDrawerLabel">
        <div class="offcanvas-header student-drawer-header">
            <div>
                <span class="page-kicker">Student Management</span>
                <h2 class="offcanvas-title" id="addStudentDrawerLabel">Add New Student</h2>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body">
            @include('admin.students.partials.form', [
                'student' => $student,
                'selectedBranch' => $selectedBranch,
                'action' => route('admin.students.store'),
                'method' => 'POST',
                'button' => 'Save Student',
                'drawer' => true,
                'drawerId' => 'addStudentDrawer',
            ])
        </div>
    </div>

    @foreach ($students as $studentRecord)
        <div class="offcanvas offcanvas-end student-drawer" tabindex="-1" id="editStudentDrawer{{ $studentRecord->id }}" aria-labelledby="editStudentDrawerLabel{{ $studentRecord->id }}">
            <div class="offcanvas-header student-drawer-header">
                <div>
                    <span class="page-kicker">Student Management</span>
                    <h2 class="offcanvas-title" id="editStudentDrawerLabel{{ $studentRecord->id }}">Edit Student</h2>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
            </div>
            <div class="offcanvas-body">
                @include('admin.students.partials.form', [
                    'student' => $studentRecord,
                    'selectedBranch' => $selectedBranch,
                    'classes' => $classes,
                    'action' => route('admin.students.update', $studentRecord),
                    'method' => 'PUT',
                    'button' => 'Update Student',
                    'drawer' => true,
                    'drawerId' => 'editStudentDrawer'.$studentRecord->id,
                ])
            </div>
        </div>
    @endforeach

@endsection
