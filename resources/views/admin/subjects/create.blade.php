@extends('layouts.admin')

@section('title', 'Add Subject')
@section('page-title', 'Add Subject')

@section('content')
    <section class="content-panel narrow-panel">
        <div class="panel-header">
            <div>
                <h2>New Subject</h2>
                <p>Add a subject. It will be available across all branches automatically.</p>
            </div>
        </div>

        @include('admin.subjects.partials.form', [
            'subject' => $subject,
            'action' => route('admin.subjects.store'),
            'method' => 'POST',
            'button' => 'Create Subject',
        ])
    </section>
@endsection
