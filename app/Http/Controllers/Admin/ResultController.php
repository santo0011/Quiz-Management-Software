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
    public function index(Request $request): View
    {
        $branchId = $request->integer('branch_id') ?: null;

        $attempts = ExamAttempt::with(['student', 'exam', 'schoolClass'])
            ->when($branchId, fn ($query) => $query->where('branch_id', $branchId))
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
            ->paginate(20)
            ->withQueryString();

        return view('admin.results.index', [
            'branches' => Branch::orderBy('name')->get(),
            'selectedBranchId' => $branchId,
            'attempts' => $attempts,
            'filters' => $request->only(['search', 'result', 'branch_id']),
        ]);
    }

    public function show(ExamAttempt $attempt): View
    {
        return view('admin.results.show', [
            'selectedBranch' => $attempt->branch,
            'attempt' => $attempt->load(['student', 'exam', 'schoolClass', 'answers.question.options', 'answers.selectedOption']),
        ]);
    }
}
