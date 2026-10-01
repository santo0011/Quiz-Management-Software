@extends('layouts.branch')

@section('title', 'Students')
@section('page-title', 'Students')

@section('content')
    <section class="content-panel mb-4">
        <div class="panel-header">
            <div>
                <h2>{{ $branch->name }} Students</h2>
                <p>Manage only the students assigned to this branch.</p>
            </div>
        </div>

        <form method="GET" action="{{ route('branch.students.index') }}" class="filter-bar mb-0">
            <input type="search" name="search" value="{{ $filters['search'] ?? '' }}" class="form-control" placeholder="Search name, guardian, email, phone">
            <select name="class" class="form-select">
                <option value="">All grades</option>
                @foreach ($classes as $class)
                    <option value="{{ $class->name }}" @selected(($filters['class'] ?? '') === $class->name)>{{ $class->name }}</option>
                @endforeach
            </select>
            <button type="submit" class="btn btn-soft">
                <i class="bi bi-search"></i>
                Filter
            </button>
        </form>
    </section>

    <section class="content-panel">
        @include('branch.students.partials.table', ['students' => $students])
    </section>
@endsection
