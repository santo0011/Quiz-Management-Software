@extends('layouts.admin')

@section('title', 'Student Details')
@section('page-title', 'Student Details')

@section('content')
    @include('students.partials.show', [
        'prefix' => 'admin',
        'student' => $student,
        'selectedBranch' => $student->branch,
    ])
@endsection