@extends('layouts.admin')

@section('title', 'Add Student')
@section('page-title', 'Add Student')

@section('content')
    <section class="content-panel narrow-panel">
        <div class="panel-header">
            <div>
                <h2>New Student</h2>
                <p>Add a student under a branch.</p>
            </div>
        </div>

        @include('admin.students.partials.form', [
            'student' => $student,
            'branches' => $branches,
            'classes' => $classes,
            'action' => route('admin.students.store'),
            'method' => 'POST',
            'button' => 'Create Student',
        ])
    </section>
@endsection
