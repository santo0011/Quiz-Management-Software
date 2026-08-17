<?php

namespace App\Http\Controllers\Branch;

use App\Http\Controllers\Controller;
use App\Http\Requests\ExamRequest;
use App\Models\Exam;
use App\Models\SchoolClass;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ExamController extends Controller
{
    public function index(Request $request): View
    {
        $branch = $request->user()->branch;
        abort_if(! $branch, 403, 'Your account is not linked to a branch.');

        $exams = Exam::with(['schoolClass', 'questions'])
            ->forBranch($branch->id)
            ->when($request->filled('search'), fn ($query) => $query->where('title', 'like', '%'.$request->string('search')->toString().'%'))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')->toString()))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('branch.exams.index', [
            'branch' => $branch,
            'exam' => new Exam(['status' => Exam::STATUS_DRAFT, 'maximum_attempts' => 1, 'marks_per_question' => 1]),
            'exams' => $exams,
            'classes' => SchoolClass::where('branch_id', $branch->id)->orderBy('name')->get(),
            'filters' => $request->only(['search', 'status']),
        ]);
    }

    public function store(ExamRequest $request): RedirectResponse
    {
        $branch = $request->user()->branch;
        abort_if(! $branch, 403, 'Your account is not linked to a branch.');

        Exam::create($request->validated() + ['branch_id' => $branch->id]);

        return redirect()->route('branch.exams.index')->with('success', 'Exam created successfully.');
    }

    public function show(Request $request, Exam $exam): View
    {
        $this->authorizeExam($request, $exam);

        return view('branch.exams.show', [
            'branch' => $request->user()->branch,
            'exam' => $exam->load(['schoolClass', 'questions.options']),
        ]);
    }

    public function edit(Request $request, Exam $exam): View
    {
        $this->authorizeExam($request, $exam);
        abort_if($exam->isPublished(), 403, 'Published exams cannot be edited.');

        return view('branch.exams.edit', [
            'branch' => $request->user()->branch,
            'exam' => $exam,
            'classes' => SchoolClass::where('branch_id', $request->user()->branch_id)->orderBy('name')->get(),
        ]);
    }

    public function update(ExamRequest $request, Exam $exam): RedirectResponse
    {
        $this->authorizeExam($request, $exam);
        abort_if($exam->isPublished(), 403, 'Published exams cannot be edited.');

        $exam->update($request->validated());

        return redirect()->route('branch.exams.index')->with('success', 'Exam updated successfully.');
    }

    public function destroy(Request $request, Exam $exam): RedirectResponse
    {
        $this->authorizeExam($request, $exam);

        if ($exam->isPublished()) {
            return redirect()->route('branch.exams.index')
                ->with('error', 'Published exams cannot be deleted. The exam is locked.');
        }

        if ($exam->attempts()->exists()) {
            return redirect()->route('branch.exams.index')
                ->with('error', 'This exam has student attempts and cannot be permanently deleted. Please close the exam instead.');
        }

        $exam->delete();

        return redirect()->route('branch.exams.index')->with('success', 'Exam deleted successfully.');
    }

    public function publish(Request $request, Exam $exam): RedirectResponse|\Illuminate\Http\JsonResponse
    {
        $this->authorizeExam($request, $exam);

        if ($exam->isPublished()) {
            if ($request->wantsJson()) {
                return response()->json(['message' => 'This exam is already published.'], 422);
            }

            abort(422, 'This exam is already published.');
        }

        $exam->update(['status' => Exam::STATUS_PUBLISHED]);

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Exam published successfully.']);
        }

        return redirect()->route('branch.exams.index')->with('success', 'Exam published successfully.');
    }

    private function authorizeExam(Request $request, Exam $exam): void
    {
        abort_if(! $request->user()->branch_id || $exam->branch_id !== $request->user()->branch_id, 403, 'This exam does not belong to your branch.');
    }
}
