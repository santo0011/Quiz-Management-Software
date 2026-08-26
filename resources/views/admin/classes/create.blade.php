@extends('layouts.admin')

@section('title', 'Add Grade')
@section('page-title', 'Add Grade')

@section('content')
    <section class="content-panel narrow-panel">
        <div class="panel-header">
            <div>
                <h2>New Grade</h2>
                <p>Add a grade. It will be available to every branch automatically.</p>
            </div>
        </div>

        @include('admin.classes.partials.form', [
            'class' => $class,
            'branches' => $branches,
            'action' => route('admin.classes.store'),
            'method' => 'POST',
            'button' => 'Create Grade',
        ])
    </section>
@endsection
