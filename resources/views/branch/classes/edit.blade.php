@extends('layouts.branch')

@section('title', 'Edit Grade')
@section('page-title', 'Edit Grade')

@section('content')
    <section class="content-panel narrow-panel">
        <div class="panel-header">
            <div>
                <h2>Edit Grade</h2>
                <p>Update this grade for {{ $branch->name }}.</p>
            </div>
        </div>

        @include('branch.classes.partials.form', [
            'class' => $class,
            'branch' => $branch,
            'action' => route('branch.classes.update', $class),
            'method' => 'PUT',
            'button' => 'Update Grade',
        ])
    </section>
@endsection
