@extends('layouts.branch')

@section('title', 'Video Tutorials')
@section('page-title', 'Video Tutorials')

@section('content')
    <section class="content-panel">
        <div class="panel-header">
            <div>
                <h2>How to use the Branch Panel</h2>
                <p>Watch how to log in, manage teachers, build and publish exams, and review and send results. Click a chapter to jump to that part.</p>
            </div>
        </div>

        @include('tutorials.partials.videos', [
            'videos' => config('tutorials.panels.branch.videos', []),
            'emptyLabel' => 'Videos are coming soon.',
        ])
    </section>
@endsection
