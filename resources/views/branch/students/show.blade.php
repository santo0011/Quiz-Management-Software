@extends('layouts.branch')

@section('title', 'Student Details')
@section('page-title', 'Student Details')

@section('content')
    <div class="branch-student-show">
        @include('students.partials.show', [
            'prefix' => 'branch',
            'student' => $student,
            'selectedBranch' => $student->branch,
        ])
    </div>
@endsection
