@extends('layouts.admin')

@section('title', 'Results')
@section('page-title', 'Results')

@section('content')
    <section class="content-panel mb-4">
        <div class="panel-header">
            <div>
                <h2>Results</h2>
                <p>Review submitted attempts across all branches.</p>
            </div>
        </div>
        <form method="GET" action="{{ route('admin.results.index') }}" class="filter-bar filter-bar-oneline mb-0">
            <select name="branch_id" class="form-select">
                <option value="">All Branches</option>
                @foreach ($branches as $branch)
                    <option value="{{ $branch->id }}" @selected(($filters['branch_id'] ?? '') == $branch->id)>{{ $branch->name }}</option>
                @endforeach
            </select>
            <input type="search" name="search" value="{{ $filters['search'] ?? '' }}" class="form-control" placeholder="Search student or exam">
            <select name="result" class="form-select form-control">
                <option value="">All results</option>
                <option value="passed" @selected(($filters['result'] ?? '') === 'passed')>Passed</option>
                <option value="failed" @selected(($filters['result'] ?? '') === 'failed')>Failed</option>
            </select>
            <div class="filter-bar-actions">
                <button type="submit" class="btn btn-soft"><i class="bi bi-search"></i> Filter</button>
            </div>
        </form>
    </section>

    <section class="content-panel">
        @include('results.partials.table', ['prefix' => 'admin'])
    </section>
@endsection
