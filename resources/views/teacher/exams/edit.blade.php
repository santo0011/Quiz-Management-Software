@extends('layouts.teacher')

@section('title', 'Edit Exam')
@section('page-title', 'Edit Exam')

@section('content')
    <section class="content-panel">
        <div class="panel-header">
            <div>
                <h2>{{ $exam->title }}</h2>
                <p>Update exam conditions for {{ $branch->name }}.</p>
            </div>
        </div>
        @include('exams.partials.form', [
            'prefix' => 'teacher',
            'action' => route('teacher.exams.update', $exam),
            'method' => 'PUT',
            'button' => 'Update Exam',
        ])
    </section>
@endsection
