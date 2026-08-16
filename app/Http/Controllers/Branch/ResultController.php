<?php

namespace App\Http\Controllers\Branch;

use App\Http\Controllers\Controller;
use App\Models\ExamAttempt;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ResultController extends Controller
{
    public function index(Request $request): View
    {
        $branch = $request->user()->branch;
        abort_if(! $branch, 403, 'Your account is not linked to a branch.');

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

        return view('branch.results.index', [
            'branch' => $branch,
            'attempts' => $attempts,
            'filters' => $request->only(['search', 'result']),
        ]);
    }

    public function show(Request $request, ExamAttempt $attempt): View
    {
        abort_if(! $request->user()->branch_id || $attempt->branch_id !== $request->user()->branch_id, 403, 'This result does not belong to your branch.');

        return view('branch.results.show', [
            'branch' => $request->user()->branch,
            'attempt' => $attempt->load(['student', 'exam', 'schoolClass', 'answers.question.options', 'answers.selectedOption']),
        ]);
    }
}
