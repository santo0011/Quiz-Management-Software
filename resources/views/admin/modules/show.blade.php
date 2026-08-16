@extends('layouts.admin')

@section('title', $module)
@section('page-title', $module)

@section('content')
    <section class="content-panel">
        <div class="panel-header">
            <div>
                <h2>{{ $selectedBranch->name }} {{ $module }}</h2>
                <p>{{ $module }} will use the active branch selected in Select Branch.</p>
            </div>
        </div>

        <div class="empty-state">
            <i class="bi {{ $icon }}"></i>
            <h3>{{ $module }} module ready</h3>
            <p>This area is scoped to {{ $selectedBranch->name }}.</p>
        </div>
    </section>
@endsection
