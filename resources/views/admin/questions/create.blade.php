@extends('layouts.admin')

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
        @include('questions.partials.multi-form', [
            'prefix' => 'admin',
            'action' => route('admin.questions.store', $exam),
            'button' => 'Save Questions',
            'defaultMarks' => $exam->marks_per_question ?? 1,
            'existingQuestions' => $exam->questions,
        ])
    </section>
@endsection
