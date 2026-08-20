@extends('layouts.admin')

@section('title', 'Edit Student')
@section('page-title', 'Edit Student')

@section('content')
    <section class="content-panel narrow-panel">
        <div class="panel-header">
            <div>
                <h2>Edit Student</h2>
                <p>Update student information for {{ $student->branch->name }}.</p>
            </div>
        </div>

        @include('admin.students.partials.form', [
            'student' => $student,
            'selectedBranch' => $student->branch,
            'classes' => $classes,
            'action' => route('admin.students.update', $student),
            'method' => 'PUT',
            'button' => 'Update Student',
        ])
    </section>
@endsection
