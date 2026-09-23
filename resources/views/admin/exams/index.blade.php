@extends('layouts.admin')

@section('title', 'Exams')
@section('page-title', 'Exams')

@section('content')
    <section class="content-panel mb-4">
        <div class="panel-header">
            <div>
                <h2>Exams</h2>
                <p>Create, schedule, publish, and monitor exams across all branches.</p>
            </div>
            <button type="button" class="btn btn-primary" data-bs-toggle="offcanvas" data-bs-target="#addExamDrawer">
                <i class="bi bi-plus-circle-fill"></i>
                Add Exam
            </button>
        </div>

        <form method="GET" action="{{ route('admin.exams.index') }}" class="filter-bar filter-bar-60-20-20 mb-0">
            <input type="search" name="search" value="{{ $filters['search'] ?? '' }}" class="form-control" placeholder="Search exam title">
            <select name="status" class="form-select form-control">
                <option value="">All statuses</option>
                @foreach (['draft' => 'Draft', 'published' => 'Published', 'closed' => 'Closed'] as $value => $label)
                    <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <button type="submit" class="btn btn-soft"><i class="bi bi-search"></i> Filter</button>
        </form>
    </section>

    <section class="content-panel">
        @include('exams.partials.table', ['prefix' => 'admin'])
    </section>

    <div class="offcanvas offcanvas-end student-drawer exam-drawer" tabindex="-1" id="addExamDrawer">
        <div class="offcanvas-header student-drawer-header">
            <div>
                <span class="page-kicker">Exam Management</span>
                <h2 class="offcanvas-title">Add New Exam</h2>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
        </div>
        <div class="offcanvas-body">
            @include('exams.partials.form', [
                'prefix' => 'admin',
                'branches' => $branches,
                'selectedBranchId' => $selectedBranchId,
                'action' => route('admin.exams.store'),
                'method' => 'POST',
                'button' => 'Save Exam',
                'drawerId' => 'addExamDrawer',
            ])
        </div>
    </div>
@endsection
