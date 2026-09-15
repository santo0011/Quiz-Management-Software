@extends('layouts.branch')

@section('title', 'Students')
@section('page-title', 'Students')

@section('content')
    <section class="content-panel">
        <div class="panel-header">
            <div>
                <h2>{{ $branch->name }} Students</h2>
                <p>Manage only the students assigned to this branch.</p>
            </div>
        </div>

        <form method="GET" action="{{ route('branch.students.index') }}" class="filter-bar">
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

        @include('branch.students.partials.table', ['students' => $students])
    </section>

    @foreach ($students as $studentRecord)
            <div class="offcanvas offcanvas-end student-drawer" tabindex="-1" id="manageSubjectsDrawer{{ $studentRecord->id }}" aria-labelledby="manageSubjectsDrawerLabel{{ $studentRecord->id }}">
                <div class="offcanvas-header student-drawer-header">
                    <div>
                        <span class="page-kicker">Student Management</span>
                        <h2 class="offcanvas-title" id="manageSubjectsDrawerLabel{{ $studentRecord->id }}">Manage Subjects — {{ $studentRecord->student_name }}</h2>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
                </div>
                <div class="offcanvas-body">
                    @include('students.partials.manage-subjects-form', [
                        'student' => $studentRecord,
                        'subjects' => $subjects,
                        'action' => route('branch.students.subjects.update', $studentRecord),
                        'drawer' => true,
                        'drawerId' => 'manageSubjectsDrawer'.$studentRecord->id,
                    ])
                </div>
            </div>
        @endforeach
@endsection
