@extends('layouts.branch')

@section('title', 'Edit Student')
@section('page-title', 'Edit Student')

@section('content')
    <section class="content-panel narrow-panel">
        <div class="panel-header">
            <div>
                <h2>Edit Student</h2>
                <p>Update student information for {{ $branch->name }}.</p>
            </div>
        </div>

        @include('branch.students.partials.form', [
            'student' => $student,
            'branch' => $branch,
            'classes' => $classes,
            'action' => route('branch.students.update', $student),
            'method' => 'PUT',
            'button' => 'Update Student',
        ])
    </section>
@endsection
