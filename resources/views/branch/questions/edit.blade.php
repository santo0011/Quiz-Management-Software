@extends('layouts.branch')

@section('title', 'Edit Question')
@section('page-title', 'Edit Question')

@section('content')
    <section class="content-panel">
        <div class="panel-header">
            <div>
                <h2>{{ $exam->title }}</h2>
                <p>Edit this MCQ and its answer options.</p>
            </div>
        </div>
        @include('questions.partials.form', [
            'prefix' => 'branch',
            'action' => route('branch.questions.update', $question),
            'method' => 'PUT',
            'button' => 'Update Question',
            'categories' => $categories,
        ])
    </section>
@endsection
