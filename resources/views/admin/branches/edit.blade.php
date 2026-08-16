@extends('layouts.admin')

@section('title', 'Edit Branch')
@section('page-title', 'Edit Branch')

@section('content')
    <section class="content-panel narrow-panel">
        <div class="panel-header">
            <div>
                <h2>Edit Branch</h2>
                <p>Update the branch name and keep records tidy.</p>
            </div>
        </div>

        @include('admin.branches.partials.form', [
            'branch' => $branch,
            'action' => route('admin.branches.update', $branch),
            'method' => 'PUT',
            'button' => 'Update Branch',
        ])
    </section>
@endsection
