@extends('layouts.teacher')

@section('title', 'Edit Passage/Summary')
@section('page-title', 'Edit Passage/Summary')

@section('content')
    <section class="content-panel">
        <div class="panel-header">
            <div>
                <h2>{{ $exam->title }}</h2>
                <p>Update the passage/summary for {{ $exam->schoolClass?->name }}.</p>
            </div>
        </div>
        @include('branch.passage-groups.partials.form', [
            'prefix' => 'teacher',
            'exam' => $exam,
            'passageGroup' => $passageGroup,
            'action' => route('teacher.passage-groups.update', $passageGroup),
            'method' => 'PUT',
            'button' => 'Update Passage/Summary',
        ])
    </section>
@endsection
