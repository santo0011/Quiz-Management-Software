@extends('layouts.admin')

@section('title', 'Edit Class')
@section('page-title', 'Edit Class')

@section('content')
    <section class="content-panel narrow-panel">
        <div class="panel-header">
            <div>
                <h2>Edit Class</h2>
                <p>
                    @if ($class->branch)
                        Update this class for {{ $class->branch->name }}.
                    @else
                        Update this class. It is available to all branches.
                    @endif
                </p>
            </div>
        </div>

        @include('admin.classes.partials.form', [
            'class' => $class,
            'selectedBranch' => $class->branch,
            'action' => route('admin.classes.update', $class),
            'method' => 'PUT',
            'button' => 'Update Class',
        ])
    </section>
@endsection
