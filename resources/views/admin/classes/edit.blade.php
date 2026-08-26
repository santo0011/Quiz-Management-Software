@extends('layouts.admin')

@section('title', 'Edit Grade')
@section('page-title', 'Edit Grade')

@section('content')
    <section class="content-panel narrow-panel">
        <div class="panel-header">
            <div>
                <h2>Edit Grade</h2>
                <p>
                    @if ($class->branch)
                        Update this grade for {{ $class->branch->name }}.
                    @else
                        Update this grade. It is available to all branches.
                    @endif
                </p>
            </div>
        </div>

        @include('admin.classes.partials.form', [
            'class' => $class,
            'selectedBranch' => $class->branch,
            'action' => route('admin.classes.update', $class),
            'method' => 'PUT',
            'button' => 'Update Grade',
        ])
    </section>
@endsection
