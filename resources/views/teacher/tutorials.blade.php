@extends('layouts.teacher')

@section('title', 'Video Tutorials')
@section('page-title', 'Video Tutorials')

@section('content')
    <section class="content-panel">
        <div class="panel-header">
            <div>
                <h2>How to use the Teacher Panel</h2>
                <p>Watch how to log in, find results, review answers and write remarks. Click a chapter to jump to that part.</p>
            </div>
        </div>

        @include('tutorials.partials.videos', [
            'videos' => config('tutorials.panels.teacher.videos', []),
            'emptyLabel' => 'Videos are coming soon.',
        ])
    </section>
@endsection
