@extends('layouts.admin')

@section('title', 'Students')
@section('page-title', 'Students')

@section('content')
    <section class="content-panel">
        <div class="panel-header">
            <div>
                <h2>Students</h2>
                <p>Manage students across all branches.</p>
            </div>
            <button type="button" class="btn btn-primary" data-bs-toggle="offcanvas" data-bs-target="#addStudentDrawer" aria-controls="addStudentDrawer">
                <i class="bi bi-person-plus-fill"></i>
                Add Student
            </button>
        </div>

        <form method="GET" action="{{ route('admin.students.index') }}" class="filter-bar">
            <select name="branch_id" class="form-select">
                <option value="">All Branches</option>
                @foreach ($branches as $branch)
                    <option value="{{ $branch->id }}" @selected(($filters['branch_id'] ?? '') == $branch->id)>{{ $branch->name }}</option>
                @endforeach
            </select>
            <input type="search" name="search" value="{{ $filters['search'] ?? '' }}" class="form-control" placeholder="Search name, guardian, email, phone">
            <select name="class" class="form-select">
                <option value="">All grades</option>
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
                'branches' => $branches,
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
                'selectedBranch' => $studentRecord->branch,
                'classes' => \App\Models\SchoolClass::visibleToBranch($studentRecord->branch_id)->orderBy('name')->get(),
                'action' => route('admin.students.update', $studentRecord),
                'method' => 'PUT',
                'button' => 'Update Student',
                'drawer' => true,
                'drawerId' => 'editStudentDrawer'.$studentRecord->id,
            ])
            @include('admin.partials.change-password-form', [
                'action' => route('admin.students.password.update', $studentRecord),
                'fieldSuffix' => '_student_'.$studentRecord->id,
            ])
            </div>
        </div>
    @endforeach

@endsection
