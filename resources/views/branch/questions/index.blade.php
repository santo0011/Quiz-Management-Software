@extends('layouts.branch')

@section('title', 'Questions')
@section('page-title', 'Questions')

@section('content')
    <section class="content-panel">
        <div class="panel-header">
            <div>
                <h2>{{ $branch->name }} Question Banks</h2>
                <p>Select an exam to add or manage MCQ questions.</p>
            </div>
        </div>
        @include('questions.partials.exam-list', ['prefix' => 'branch'])
    </section>
@endsection
