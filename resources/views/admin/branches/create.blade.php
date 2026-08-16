@extends('layouts.admin')

@section('title', 'Add Branch')
@section('page-title', 'Add Branch')

@section('content')
    <section class="content-panel narrow-panel">
        <div class="panel-header">
            <div>
                <h2>New Branch</h2>
                <p>Add a branch name that will be used across future modules.</p>
            </div>
        </div>

        @include('admin.branches.partials.form', [
            'branch' => new App\Models\Branch(),
            'action' => route('admin.branches.store'),
            'method' => 'POST',
            'button' => 'Create Branch',
        ])
    </section>
@endsection
