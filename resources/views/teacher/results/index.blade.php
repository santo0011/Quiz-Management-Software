@extends('layouts.teacher')

@section('title', 'Results')
@section('page-title', 'Results')

@section('content')
    <section class="content-panel">
        <div class="panel-header">
            <div>
                <h2>{{ $teacher->branch?->name }} Results</h2>
                <p>Review submitted attempts and add remarks for your branch.</p>
            </div>
        </div>

        @if (! $selectedSessionId)
            <div class="empty-state">
                <i class="bi bi-calendar-range"></i>
                <h3>Select an academic session to continue</h3>
                <p>Choose a session from the navbar dropdown above to view results.</p>
            </div>
        @else
            <form method="GET" action="{{ route('teacher.results.index') }}" class="filter-bar compact-filter-bar">
                <input type="search" name="search" value="{{ $filters['search'] ?? '' }}" class="form-control" placeholder="Search student or exam">
                <select name="remark" class="form-select form-control">
                    <option value="">All results</option>
                    <option value="pending" @selected(($filters['remark'] ?? '') === 'pending')>Remark Pending</option>
                    <option value="remarked" @selected(($filters['remark'] ?? '') === 'remarked')>Remarked</option>
                </select>
                <button type="submit" class="btn btn-soft"><i class="bi bi-search"></i> Filter</button>
            </form>

            @include('results.partials.table', ['prefix' => 'teacher'])
        @endif
    </section>
@endsection
