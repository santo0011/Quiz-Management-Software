@extends('layouts.branch')

@section('title', 'Add Passage/Summary')
@section('page-title', 'Add Passage/Summary')

@section('content')
    <section class="content-panel">
        <div class="panel-header">
            <div>
                <h2>{{ $exam->title }}</h2>
                <p>Create the passage/summary for {{ $exam->schoolClass?->name }}. You'll add its questions next.</p>
            </div>
        </div>
        @include('branch.passage-groups.partials.form', [
            'prefix' => 'branch',
            'exam' => $exam,
            'passageGroup' => $passageGroup,
            'action' => route('branch.passage-groups.store', $exam),
            'method' => 'POST',
            'button' => 'Save & Add Questions',
        ])
    </section>
@endsection
