@extends('layouts.admin')

@section('title', 'Add Class')
@section('page-title', 'Add Class')

@section('content')
    <section class="content-panel narrow-panel">
        <div class="panel-header">
            <div>
                <h2>New Class</h2>
                <p>Add a class under a branch.</p>
            </div>
        </div>

        @include('admin.classes.partials.form', [
            'class' => $class,
            'branches' => $branches,
            'action' => route('admin.classes.store'),
            'method' => 'POST',
            'button' => 'Create Class',
        ])
    </section>
@endsection
