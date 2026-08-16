@extends('layouts.branch')

@section('title', 'Add Question')
@section('page-title', 'Add Question')

@section('content')
    <section class="content-panel">
        <div class="panel-header">
            <div>
                <h2>{{ $exam->title }}</h2>
                <p>Add an MCQ for {{ $exam->schoolClass?->name }}.</p>
            </div>
        </div>
        @include('questions.partials.form', [
            'prefix' => 'branch',
            'action' => route('branch.questions.store', $exam),
            'method' => 'POST',
            'button' => 'Save Question',
        ])
    </section>
@endsection
