@extends('layouts.admin')

@section('title', 'Edit Session')
@section('page-title', 'Edit Session')

@section('content')
    <section class="content-panel narrow-panel">
        <div class="panel-header">
            <div>
                <h2>Edit Session</h2>
                <p>Update this academic session.</p>
            </div>
        </div>

        @include('admin.academic-sessions.partials.form', [
            'academicSession' => $academicSession,
            'action' => route('admin.academic-sessions.update', $academicSession),
            'method' => 'PUT',
            'button' => 'Update Session',
        ])
    </section>
@endsection
