<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\ExamAttempt;
use App\Services\AcademicSessionResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ResultController extends Controller
{
    public function index(Request $request): View
    {
        $branchId = $request->integer('branch_id') ?: null;
        $selectedSessionId = AcademicSessionResolver::selectedId($request);

        $attempts = $selectedSessionId
            ? ExamAttempt::with(['student', 'exam', 'schoolClass'])
                ->where('session_id', $selectedSessionId)
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
                ->withQueryString()
            : null;

        return view('admin.results.index', [
            'branches' => Branch::orderBy('name')->get(),
            'selectedBranchId' => $branchId,
            'selectedSessionId' => $selectedSessionId,
            'attempts' => $attempts,
            'filters' => $request->only(['search', 'result', 'branch_id']),
        ]);
    }

    public function show(Request $request, ExamAttempt $attempt): View
    {
        $this->authorizeSessionScope($request, $attempt);

        return view('admin.results.show', [
            'selectedBranch' => $attempt->branch,
            'attempt' => $attempt->load(['student', 'exam', 'schoolClass', 'answers.question.options', 'answers.selectedOption']),
        ]);
    }

    /**
     * Block reaching a Result that belongs to a different Academic Session
     * than the one currently selected. Legacy rows (either side null)
     * fall through as allowed.
     */
    private function authorizeSessionScope(Request $request, ExamAttempt $attempt): void
    {
        $selectedSessionId = AcademicSessionResolver::selectedId($request);

        abort_if(
            $attempt->session_id !== null && $selectedSessionId !== null && $attempt->session_id !== $selectedSessionId,
            403,
            'This result does not belong to the currently selected academic session.'
        );
    }
}
