<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\ExamAttempt;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ResultController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        $branch = $this->selectedBranch();

        if (! $branch) {
            return redirect()->route('admin.branch-selection.index')
                ->with('success', 'Please select a branch first to manage branch-related data.');
        }

        $attempts = ExamAttempt::with(['student', 'exam', 'schoolClass'])
            ->where('branch_id', $branch->id)
            ->where('status', 'submitted')
            ->when($request->filled('search'), function ($query) use ($request): void {
                $search = $request->string('search')->toString();
                $query->where(function ($query) use ($search): void {
                    $query->whereHas('student', fn ($studentQuery) => $studentQuery
                        ->where('student_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%"))
                        ->orWhereHas('exam', fn ($examQuery) => $examQuery->where('title', 'like', "%{$search}%"));
                });
            })
            ->when($request->filled('result'), fn ($query) => $query->where('is_passed', $request->string('result')->toString() === 'passed'))
            ->latest('submitted_at')
            ->paginate(10)
            ->withQueryString();

        return view('admin.results.index', [
            'selectedBranch' => $branch,
            'attempts' => $attempts,
            'filters' => $request->only(['search', 'result']),
        ]);
    }

    public function show(ExamAttempt $attempt): View
    {
        $branch = $this->selectedBranch();
        abort_if(! $branch || $attempt->branch_id !== $branch->id, 403, 'This result is not in the selected branch.');

        return view('admin.results.show', [
            'selectedBranch' => $branch,
            'attempt' => $attempt->load(['student', 'exam', 'schoolClass', 'answers.question.options', 'answers.selectedOption']),
        ]);
    }

    private function selectedBranch(): ?Branch
    {
        $branchId = session('admin_selected_branch_id');

        return $branchId ? Branch::find($branchId) : null;
    }
}
