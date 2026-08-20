<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ExamRequest;
use App\Models\Branch;
use App\Models\Exam;
use App\Models\SchoolClass;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ExamController extends Controller
{
    public function index(Request $request): View
    {
        $branchId = $request->integer('branch_id') ?: null;

        $exams = Exam::with(['schoolClass', 'questions'])
            ->when($branchId, fn ($query) => $query->forBranch($branchId))
            ->when($request->filled('search'), fn ($query) => $query->where('title', 'like', '%'.$request->string('search')->toString().'%'))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')->toString()))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.exams.index', [
            'branches' => Branch::orderBy('name')->get(),
            'selectedBranchId' => $branchId,
            'exam' => new Exam(['status' => Exam::STATUS_DRAFT, 'maximum_attempts' => 1, 'marks_per_question' => 1]),
            'exams' => $exams,
            'classes' => $branchId
                ? SchoolClass::where('branch_id', $branchId)->orderBy('name')->get()
                : collect(),
            'filters' => $request->only(['search', 'status', 'branch_id']),
        ]);
    }

    public function store(ExamRequest $request): RedirectResponse
    {
        Exam::create($request->validated() + [
            'status' => Exam::STATUS_DRAFT,
        ]);

        return redirect()->route('admin.exams.index')->with('success', 'Exam created successfully.');
    }

    public function show(Exam $exam): View
    {
        return view('admin.exams.show', [
            'selectedBranch' => $exam->branch,
            'exam' => $exam->load(['schoolClass', 'questions.options']),
        ]);
    }

    public function edit(Exam $exam): View
    {
        abort_if($exam->isPublished(), 403, 'Published exams cannot be edited.');

        return view('admin.exams.edit', [
            'selectedBranch' => $exam->branch,
            'exam' => $exam,
            'classes' => SchoolClass::where('branch_id', $exam->branch_id)->orderBy('name')->get(),
        ]);
    }

    public function update(ExamRequest $request, Exam $exam): RedirectResponse
    {
        abort_if($exam->isPublished(), 403, 'Published exams cannot be edited.');

        $exam->update($request->validated());

        return redirect()->route('admin.exams.index')->with('success', 'Exam updated successfully.');
    }

    public function destroy(Exam $exam): RedirectResponse
    {
        if ($exam->isPublished()) {
            return redirect()->route('admin.exams.index')
                ->with('error', 'Published exams cannot be deleted. The exam is locked.');
        }

        if ($exam->attempts()->exists()) {
            return redirect()->route('admin.exams.index')
                ->with('error', 'This exam has student attempts and cannot be permanently deleted. Please close the exam instead.');
        }

        $exam->delete();

        return redirect()->route('admin.exams.index')->with('success', 'Exam deleted successfully.');
    }

    public function publish(Exam $exam): RedirectResponse|\Illuminate\Http\JsonResponse
    {
        if ($exam->isPublished()) {
            if ($this->wantsJson()) {
                return response()->json(['message' => 'This exam is already published.'], 422);
            }

            abort(422, 'This exam is already published.');
        }

        $exam->update(['status' => Exam::STATUS_PUBLISHED]);

        if ($this->wantsJson()) {
            return response()->json(['message' => 'Exam published successfully.']);
        }

        return redirect()->route('admin.exams.index')->with('success', 'Exam published successfully.');
    }
}
