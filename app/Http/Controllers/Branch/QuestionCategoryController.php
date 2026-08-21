<?php

namespace App\Http\Controllers\Branch;

use App\Http\Controllers\Controller;
use App\Http\Requests\QuestionCategoryRequest;
use App\Models\QuestionCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class QuestionCategoryController extends Controller
{
    public function index(Request $request): View
    {
        $branch = $request->user()->branch;
        abort_if(! $branch, 403, 'Your account is not linked to a branch.');

        $categories = QuestionCategory::withCount('questions')
            ->where('branch_id', $branch->id)
            ->when($request->filled('search'), fn ($query) => $query->where('name', 'like', '%'.$request->string('search')->toString().'%'))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('branch.question-categories.index', [
            'branch' => $branch,
            'category' => new QuestionCategory,
            'categories' => $categories,
            'filters' => $request->only(['search']),
        ]);
    }

    public function store(QuestionCategoryRequest $request): RedirectResponse
    {
        $branch = $request->user()->branch;
        abort_if(! $branch, 403, 'Your account is not linked to a branch.');

        QuestionCategory::create($request->validated() + [
            'branch_id' => $branch->id,
        ]);

        return redirect()->route('branch.question-categories.index')->with('success', 'Question category added successfully.');
    }

    public function update(QuestionCategoryRequest $request, QuestionCategory $questionCategory): RedirectResponse
    {
        $questionCategory->update($request->validated());

        return redirect()->route('branch.question-categories.index')->with('success', 'Question category updated successfully.');
    }

    public function destroy(Request $request, QuestionCategory $questionCategory): RedirectResponse
    {
        abort_if(! $request->user()->branch_id || $questionCategory->branch_id !== $request->user()->branch_id, 403, 'This category does not belong to your branch.');

        if ($questionCategory->questions()->exists() || $questionCategory->exams()->exists()) {
            return redirect()->route('branch.question-categories.index')
                ->with('error', 'This category cannot be deleted because it is used by one or more exams or questions.');
        }

        $questionCategory->delete();

        return redirect()->route('branch.question-categories.index')->with('success', 'Question category deleted successfully.');
    }
}
