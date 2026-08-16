@extends('layouts.admin')

@section('title', 'Add Student')
@section('page-title', 'Add Student')

@section('content')
    <section class="content-panel narrow-panel">
        <div class="panel-header">
            <div>
                <h2>New Student</h2>
                <p>Add a student under the currently selected branch.</p>
            </div>
        </div>

        @include('admin.students.partials.form', [
            'student' => $student,
            'selectedBranch' => $selectedBranch,
            'classes' => $classes,
            'action' => route('admin.students.store'),
            'method' => 'POST',
            'button' => 'Create Student',
        ])
    </section>
@endsection
