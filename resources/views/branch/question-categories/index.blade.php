@extends('layouts.branch')

@section('title', 'Question Categories')
@section('page-title', 'Question Categories')

@section('content')
    <section class="content-panel">
        <div class="panel-header">
            <div>
                <h2>{{ $branch->name }} Question Categories</h2>
                <p>Organize your branch's questions into categories.</p>
            </div>
            <button type="button" class="btn btn-primary" data-bs-toggle="offcanvas" data-bs-target="#addCategoryDrawer" aria-controls="addCategoryDrawer">
                <i class="bi bi-plus-circle-fill"></i>
                Add Category
            </button>
        </div>

        <form method="GET" action="{{ route('branch.question-categories.index') }}" class="filter-bar compact-filter-bar">
            <input type="search" name="search" value="{{ $filters['search'] ?? '' }}" class="form-control" placeholder="Search category name">
            <button type="submit" class="btn btn-soft">
                <i class="bi bi-search"></i>
                Filter
            </button>
        </form>

        @if ($categories->isEmpty())
            <div class="empty-state">
                <i class="bi bi-tags"></i>
                <h3>No categories found</h3>
                <p>Add a category to help organize your questions.</p>
            </div>
        @else
            <div class="table-responsive">
                <table class="table align-middle admin-table">
                    <thead>
                        <tr>
                            <th>Category Name</th>
                            <th>Questions</th>
                            <th>Created</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($categories as $categoryRow)
                            <tr>
                                <td><strong>{{ $categoryRow->name }}</strong></td>
                                <td>
                                    <span class="question-count-badge">
                                        <i class="bi bi-file-earmark-text"></i>
                                        {{ $categoryRow->questions_count }}
                                    </span>
                                </td>
                                <td>{{ $categoryRow->created_at->format('d M Y, h:i A') }}</td>
                                <td class="text-end">
                                    <div class="action-group">
                                        <button type="button" class="btn btn-sm btn-soft" title="Edit" data-bs-toggle="offcanvas" data-bs-target="#editCategoryDrawer{{ $categoryRow->id }}" aria-controls="editCategoryDrawer{{ $categoryRow->id }}">
                                            <i class="bi bi-pencil-fill"></i>
                                        </button>
                                        <form method="POST" action="{{ route('branch.question-categories.destroy', $categoryRow) }}" data-confirm-delete>
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-sm btn-danger-soft" type="submit" title="Delete">
                                                <i class="bi bi-trash-fill"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{ $categories->links() }}
        @endif
    </section>

    <div class="offcanvas offcanvas-end student-drawer" tabindex="-1" id="addCategoryDrawer" aria-labelledby="addCategoryDrawerLabel">
        <div class="offcanvas-header student-drawer-header">
            <div>
                <span class="page-kicker">Question Categories</span>
                <h2 class="offcanvas-title" id="addCategoryDrawerLabel">Add New Category</h2>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body">
            @include('branch.question-categories.partials.form', [
                'category' => $category,
                'branch' => $branch,
                'action' => route('branch.question-categories.store'),
                'method' => 'POST',
                'button' => 'Save Category',
                'drawer' => true,
                'drawerId' => 'addCategoryDrawer',
            ])
        </div>
    </div>

    @foreach ($categories as $categoryRow)
        <div class="offcanvas offcanvas-end student-drawer" tabindex="-1" id="editCategoryDrawer{{ $categoryRow->id }}" aria-labelledby="editCategoryDrawerLabel{{ $categoryRow->id }}">
            <div class="offcanvas-header student-drawer-header">
                <div>
                    <span class="page-kicker">Question Categories</span>
                    <h2 class="offcanvas-title" id="editCategoryDrawerLabel{{ $categoryRow->id }}">Edit Category</h2>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
            </div>
            <div class="offcanvas-body">
                @include('branch.question-categories.partials.form', [
                    'category' => $categoryRow,
                    'branch' => $branch,
                    'action' => route('branch.question-categories.update', $categoryRow),
                    'method' => 'PUT',
                    'button' => 'Update Category',
                    'drawer' => true,
                    'drawerId' => 'editCategoryDrawer'.$categoryRow->id,
                ])
            </div>
        </div>
    @endforeach
@endsection
