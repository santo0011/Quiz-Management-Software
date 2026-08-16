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
    public function index(Request $request): View|RedirectResponse
    {
        $branch = $this->selectedBranch();

        if (! $branch) {
            return redirect()->route('admin.branch-selection.index')
                ->with('success', 'Please select a branch first to manage branch-related data.');
        }

        $exams = Exam::with(['schoolClass', 'questions'])
            ->forBranch($branch->id)
            ->when($request->filled('search'), fn ($query) => $query->where('title', 'like', '%'.$request->string('search')->toString().'%'))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')->toString()))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.exams.index', [
            'selectedBranch' => $branch,
            'exam' => new Exam(['status' => Exam::STATUS_DRAFT, 'maximum_attempts' => 1, 'marks_per_question' => 1]),
            'exams' => $exams,
            'classes' => SchoolClass::where('branch_id', $branch->id)->orderBy('name')->get(),
            'filters' => $request->only(['search', 'status']),
        ]);
    }

    public function store(ExamRequest $request): RedirectResponse
    {
        $branch = $this->selectedBranch();
        abort_if(! $branch, 403);

        Exam::create($request->validated() + ['branch_id' => $branch->id]);

        return redirect()->route('admin.exams.index')->with('success', 'Exam created successfully.');
    }

    public function show(Exam $exam): View
    {
        $this->authorizeExam($exam);

        return view('admin.exams.show', [
            'selectedBranch' => $exam->branch,
            'exam' => $exam->load(['schoolClass', 'questions.options']),
        ]);
    }

    public function edit(Exam $exam): View
    {
        $this->authorizeExam($exam);
        abort_if($exam->isPublished(), 403, 'Published exams cannot be edited.');

        return view('admin.exams.edit', [
            'selectedBranch' => $exam->branch,
            'exam' => $exam,
            'classes' => SchoolClass::where('branch_id', $exam->branch_id)->orderBy('name')->get(),
        ]);
    }

    public function update(ExamRequest $request, Exam $exam): RedirectResponse
    {
        $this->authorizeExam($exam);
        abort_if($exam->isPublished(), 403, 'Published exams cannot be edited.');

        $exam->update($request->validated());

        return redirect()->route('admin.exams.index')->with('success', 'Exam updated successfully.');
    }

    public function destroy(Exam $exam): RedirectResponse
    {
        $this->authorizeExam($exam);
        abort_if($exam->isPublished(), 403, 'Published exams cannot be deleted.');
        abort_if($exam->attempts()->exists(), 422, 'Cannot delete an exam that already has attempts.');

        $exam->delete();

        return redirect()->route('admin.exams.index')->with('success', 'Exam deleted successfully.');
    }

    public function publish(Exam $exam): RedirectResponse|\Illuminate\Http\JsonResponse
    {
        $this->authorizeExam($exam);

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

    private function selectedBranch(): ?Branch
    {
        $branchId = session('admin_selected_branch_id');

        return $branchId ? Branch::find($branchId) : null;
    }

    private function authorizeExam(Exam $exam): void
    {
        $branch = $this->selectedBranch();

        abort_if(! $branch || $exam->branch_id !== $branch->id, 403, 'This exam is not in the selected branch.');
    }
}
