@extends('layouts.branch')

@section('title', $module)
@section('page-title', $module)

@section('content')
    <section class="content-panel">
        <div class="panel-header">
            <div>
                <h2>{{ $branch->name }} {{ $module }}</h2>
                <p>{{ $module }} are managed for your branch account.</p>
            </div>
        </div>

        <div class="empty-state">
            <i class="bi {{ $icon }}"></i>
            <h3>{{ $module }} module ready</h3>
            <p>This area is connected to {{ $branch->name }}.</p>
        </div>
    </section>
@endsection
