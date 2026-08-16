@extends('layouts.admin')

@section('title', 'Select Branch')
@section('page-title', 'Select Branch')

@section('content')
    <section class="content-panel narrow-panel">
        <div class="panel-header">
            <div>
                <h2>Branch Context</h2>
                <p>Choose the branch whose students and future branch data you want to manage.</p>
            </div>
        </div>

        @if ($selectedBranch)
            <div class="feedback-alert success mb-4">
                <i class="bi bi-building-check"></i>
                <div><strong>Current Branch:</strong> {{ $selectedBranch->name }}</div>
            </div>
        @else
            <div class="feedback-alert info mb-4">
                <i class="bi bi-info-circle-fill"></i>
                <div>Please select a branch first to manage branch-related data.</div>
            </div>
        @endif

        <form method="POST" action="{{ route('admin.branch-selection.store') }}" class="admin-form">
            @csrf
            <div class="mb-4">
                <label for="branch_id" class="form-label">Branch</label>
                <select id="branch_id" name="branch_id" class="form-select form-control @error('branch_id') is-invalid @enderror" required>
                    <option value="">Select branch</option>
                    @foreach ($branches as $branch)
                        <option value="{{ $branch->id }}" @selected(old('branch_id', $selectedBranch?->id) == $branch->id)>
                            {{ $branch->name }} - {{ $branch->email }}
                        </option>
                    @endforeach
                </select>
                @error('branch_id')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="d-flex gap-2 flex-wrap">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-circle-fill"></i>
                    Use Branch
                </button>
                @if ($selectedBranch)
                    <a href="{{ route('admin.students.index') }}" class="btn btn-soft">
                        <i class="bi bi-people-fill"></i>
                        Manage Students
                    </a>
                @endif
            </div>
        </form>

        @if ($selectedBranch)
            <form method="POST" action="{{ route('admin.branch-selection.clear') }}" class="mt-3">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-outline-secondary">Clear Selection</button>
            </form>
        @endif
    </section>
@endsection
