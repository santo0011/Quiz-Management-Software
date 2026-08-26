@extends('layouts.branch')

@section('title', 'Add Grade')
@section('page-title', 'Add Grade')

@section('content')
    <section class="content-panel narrow-panel">
        <div class="panel-header">
            <div>
                <h2>New Grade</h2>
                <p>Add a grade under {{ $branch->name }}.</p>
            </div>
        </div>

        @include('branch.classes.partials.form', [
            'class' => $class,
            'branch' => $branch,
            'action' => route('branch.classes.store'),
            'method' => 'POST',
            'button' => 'Create Grade',
        ])
    </section>
@endsection
