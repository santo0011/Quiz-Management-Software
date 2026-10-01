@extends('layouts.branch')

@section('title', 'Results')
@section('page-title', 'Results')

@section('content')
    <section class="content-panel mb-4">
        <div class="panel-header">
            <div>
                <h2>{{ $branch->name }} Results</h2>
                <p>Review submitted attempts for your branch.</p>
            </div>
        </div>
        <form method="GET" action="{{ route('branch.results.index') }}" class="filter-bar filter-bar-oneline mb-0">
            <input type="search" name="search" value="{{ $filters['search'] ?? '' }}" class="form-control" placeholder="Search student or exam">
            <div class="filter-bar-actions">
                <button type="submit" class="btn btn-soft"><i class="bi bi-search"></i> Filter</button>
            </div>
        </form>
    </section>

    <section class="content-panel">
        @include('results.partials.table', ['prefix' => 'branch'])
    </section>
@endsection
