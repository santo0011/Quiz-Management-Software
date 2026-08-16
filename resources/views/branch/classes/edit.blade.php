@extends('layouts.branch')

@section('title', 'Edit Class')
@section('page-title', 'Edit Class')

@section('content')
    <section class="content-panel narrow-panel">
        <div class="panel-header">
            <div>
                <h2>Edit Class</h2>
                <p>Update this class for {{ $branch->name }}.</p>
            </div>
        </div>

        @include('branch.classes.partials.form', [
            'class' => $class,
            'branch' => $branch,
            'action' => route('branch.classes.update', $class),
            'method' => 'PUT',
            'button' => 'Update Class',
        ])
    </section>
@endsection
