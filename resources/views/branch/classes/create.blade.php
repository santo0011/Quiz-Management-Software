@extends('layouts.branch')

@section('title', 'Add Class')
@section('page-title', 'Add Class')

@section('content')
    <section class="content-panel narrow-panel">
        <div class="panel-header">
            <div>
                <h2>New Class</h2>
                <p>Add a class under {{ $branch->name }}.</p>
            </div>
        </div>

        @include('branch.classes.partials.form', [
            'class' => $class,
            'branch' => $branch,
            'action' => route('branch.classes.store'),
            'method' => 'POST',
            'button' => 'Create Class',
        ])
    </section>
@endsection
