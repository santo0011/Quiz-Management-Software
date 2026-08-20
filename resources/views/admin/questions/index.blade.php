@extends('layouts.admin')

@section('title', 'Questions')
@section('page-title', 'Questions')

@section('content')
    <section class="content-panel">
        <div class="panel-header">
            <div>
                <h2>Question Banks</h2>
                <p>Select an exam to add or manage MCQ questions.</p>
            </div>
        </div>
        <form method="GET" action="{{ route('admin.questions.index') }}" class="filter-bar compact-filter-bar">
            <select name="branch_id" class="form-select">
                <option value="">All Branches</option>
                @foreach ($branches as $branch)
                    <option value="{{ $branch->id }}" @selected(($filters['branch_id'] ?? '') == $branch->id)>{{ $branch->name }}</option>
                @endforeach
            </select>
            <button type="submit" class="btn btn-soft"><i class="bi bi-search"></i> Filter</button>
        </form>
        @include('questions.partials.exam-list', ['prefix' => 'admin'])
    </section>
@endsection
