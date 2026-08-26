@extends('layouts.admin')

@section('title', 'Add Session')
@section('page-title', 'Add Session')

@section('content')
    <section class="content-panel narrow-panel">
        <div class="panel-header">
            <div>
                <h2>New Academic Session</h2>
                <p>Add a session. Students, exams, and results will be organized under it.</p>
            </div>
        </div>

        @include('admin.academic-sessions.partials.form', [
            'academicSession' => $academicSession,
            'action' => route('admin.academic-sessions.store'),
            'method' => 'POST',
            'button' => 'Create Session',
        ])
    </section>
@endsection
