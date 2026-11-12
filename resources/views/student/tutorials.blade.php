@extends('layouts.student')

@section('title', 'Video Tutorials')
@section('page-title', 'Video Tutorials')

@section('content')
    <section class="content-panel">
        <div class="panel-header">
            <div>
                <h2>How to use the Student Portal</h2>
                <p>Watch how to log in, find your exams, take an exam and see your results. Click a chapter to jump to that part.</p>
            </div>
        </div>

        @include('tutorials.partials.videos', [
            'videos' => config('tutorials.panels.student.videos', []),
            'emptyLabel' => 'Videos are coming soon.',
        ])
    </section>
@endsection
