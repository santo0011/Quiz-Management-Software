@extends('layouts.branch')

@section('title', 'Add Student')
@section('page-title', 'Add Student')

@section('content')
    <section class="content-panel narrow-panel">
        <div class="panel-header">
            <div>
                <h2>New Student</h2>
                <p>Add a student under {{ $branch->name }}.</p>
            </div>
        </div>

        @include('branch.students.partials.form', [
            'student' => $student,
            'branch' => $branch,
            'classes' => $classes,
            'action' => route('branch.students.store'),
            'method' => 'POST',
            'button' => 'Create Student',
        ])
    </section>
@endsection
