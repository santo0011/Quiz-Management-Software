<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\QuestionCategoryRequest;
use App\Models\Branch;
use App\Models\QuestionCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class QuestionCategoryController extends Controller
{
    public function index(Request $request): View
    {
        $branchId = $request->integer('branch_id') ?: null;

        $categories = QuestionCategory::withCount('questions')
            ->with('branch')
            ->when($branchId, fn ($query) => $query->visibleToBranch($branchId))
            ->when($request->filled('search'), fn ($query) => $query->where('name', 'like', '%'.$request->string('search')->toString().'%'))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('admin.question-categories.index', [
            'category' => new QuestionCategory(['branch_id' => $branchId]),
            'categories' => $categories,
            'branches' => Branch::where('is_active', true)->orderBy('name')->get(),
            'selectedBranchId' => $branchId,
            'filters' => $request->only(['search', 'branch_id']),
        ]);
    }

    public function store(QuestionCategoryRequest $request): RedirectResponse
    {
        QuestionCategory::create($request->validated());

        return redirect()->route('admin.question-categories.index', ['branch_id' => $request->input('branch_id')])
            ->with('success', 'Question category added successfully.');
    }

    public function update(QuestionCategoryRequest $request, QuestionCategory $questionCategory): RedirectResponse
    {
        $questionCategory->update($request->validated());

        return redirect()->route('admin.question-categories.index', ['branch_id' => $questionCategory->branch_id])
            ->with('success', 'Question category updated successfully.');
    }

    public function destroy(QuestionCategory $questionCategory): RedirectResponse
    {
        if ($questionCategory->questions()->exists() || $questionCategory->exams()->exists()) {
            return redirect()->route('admin.question-categories.index', ['branch_id' => $questionCategory->branch_id])
                ->with('error', 'This category cannot be deleted because it is used by one or more exams or questions.');
        }

        $branchId = $questionCategory->branch_id;
        $questionCategory->delete();

        return redirect()->route('admin.question-categories.index', ['branch_id' => $branchId])
            ->with('success', 'Question category deleted successfully.');
    }
}
