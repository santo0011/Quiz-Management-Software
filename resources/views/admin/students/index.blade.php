@extends('layouts.admin')

@section('title', 'Students')
@section('page-title', 'Students')

@section('content')
    <section class="content-panel mb-4">
        <div class="panel-header">
            <div>
                <h2>{{ $branch->name }} Students</h2>
                <p>Manage only the students assigned to this branch.</p>
            </div>
            <a href="{{ route('admin.branch-selection.index') }}" class="btn btn-soft">
                <i class="bi bi-arrow-left-right"></i>
                Switch Branch
            </a>
        </div>

        <form method="GET" action="{{ route('admin.students.index') }}" class="filter-bar filter-bar-oneline mb-0">
            <input type="search" name="search" value="{{ $filters['search'] ?? '' }}" class="form-control" placeholder="Search name, guardian, email, phone">
            <select name="class" class="form-select">
                <option value="">All grades</option>
                @foreach ($classes as $class)
                    <option value="{{ $class->name }}" @selected(($filters['class'] ?? '') === $class->name)>{{ $class->name }}</option>
                @endforeach
            </select>
            <div class="filter-bar-actions">
                <button type="submit" class="btn btn-soft">
                    <i class="bi bi-search"></i>
                    Filter
                </button>
            </div>
        </form>
    </section>

    <section class="content-panel">
        @include('admin.students.partials.table', ['students' => $students])
    </section>
@endsection
