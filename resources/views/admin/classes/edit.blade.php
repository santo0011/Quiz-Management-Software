@extends('layouts.admin')

@section('title', 'Edit Class')
@section('page-title', 'Edit Class')

@section('content')
    <section class="content-panel narrow-panel">
        <div class="panel-header">
            <div>
                <h2>Edit Class</h2>
                <p>Update this class for {{ $selectedBranch->name }}.</p>
            </div>
        </div>

        @include('admin.classes.partials.form', [
            'class' => $class,
            'selectedBranch' => $selectedBranch,
            'action' => route('admin.classes.update', $class),
            'method' => 'PUT',
            'button' => 'Update Class',
        ])
    </section>
@endsection
