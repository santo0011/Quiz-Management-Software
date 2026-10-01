@extends('layouts.admin')

@section('title', 'Students')
@section('page-title', 'Students')

@section('content')
    <section class="content-panel mb-4">
        <div class="panel-header">
            <div>
                <h2>Students</h2>
                <p>Manage students across every branch.</p>
            </div>
            <a href="{{ route('admin.students.create') }}" class="btn btn-primary">
                <i class="bi bi-person-plus-fill"></i>
                Add Student
            </a>
        </div>

        <form method="GET" action="{{ route('admin.students.index') }}" class="filter-bar filter-bar-oneline mb-0">
            <input type="search" name="search" value="{{ $filters['search'] ?? '' }}" class="form-control" placeholder="Search name, guardian, email, phone">
            <select name="branch_id" class="form-select">
                <option value="">All branches</option>
                @foreach ($branches as $branch)
                    <option value="{{ $branch->id }}" @selected((string) $selectedBranchId === (string) $branch->id)>{{ $branch->name }}</option>
                @endforeach
            </select>
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
