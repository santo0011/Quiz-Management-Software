@extends('layouts.admin')

@section('title', 'Edit Subject')
@section('page-title', 'Edit Subject')

@section('content')
    <section class="content-panel narrow-panel">
        <div class="panel-header">
            <div>
                <h2>Edit Subject</h2>
                <p>Update this subject.</p>
            </div>
        </div>

        @include('admin.subjects.partials.form', [
            'subject' => $subject,
            'action' => route('admin.subjects.update', $subject),
            'method' => 'PUT',
            'button' => 'Update Subject',
        ])
    </section>
@endsection
