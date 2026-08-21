<?php

namespace App\Http\Controllers\Branch;

use App\Http\Controllers\Controller;
use App\Http\Requests\PassageGroupRequest;
use App\Models\Exam;
use App\Models\PassageGroup;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class PassageGroupController extends Controller
{
    public function create(Request $request, Exam $exam): View
    {
        $this->authorizeExam($request, $exam);
        abort_if($exam->hasBeenAttempted(), 403, Exam::LOCK_MESSAGE);

        return view('branch.passage-groups.create', [
            'branch' => $request->user()->branch,
            'exam' => $exam,
            'passageGroup' => new PassageGroup,
        ]);
    }

    public function store(PassageGroupRequest $request, Exam $exam): RedirectResponse
    {
        $this->authorizeExam($request, $exam);
        abort_if($exam->hasBeenAttempted(), 403, Exam::LOCK_MESSAGE);

        $passageGroup = DB::transaction(function () use ($request, $exam) {
            $validated = $request->safe()->only(['content']);
            $validated['title'] = 'Summary '.($exam->passageGroups()->count() + 1);
            $validated['position'] = $exam->nextTopLevelPosition();

            return $exam->passageGroups()->create($validated);
        });

        return redirect()->to(route('branch.questions.create', $exam).'#summary-'.$passageGroup->id)
            ->with('success', 'Summary added. Now add its questions below.')
            ->with('open_summary_id', $passageGroup->id);
    }

    public function edit(Request $request, PassageGroup $passageGroup): View
    {
        $this->authorizeExam($request, $passageGroup->exam);
        abort_if($passageGroup->exam->hasBeenAttempted(), 403, Exam::LOCK_MESSAGE);

        return view('branch.passage-groups.edit', [
            'branch' => $request->user()->branch,
            'exam' => $passageGroup->exam,
            'passageGroup' => $passageGroup,
        ]);
    }

    public function update(PassageGroupRequest $request, PassageGroup $passageGroup): RedirectResponse
    {
        $this->authorizeExam($request, $passageGroup->exam);
        abort_if($passageGroup->exam->hasBeenAttempted(), 403, Exam::LOCK_MESSAGE);

        DB::transaction(function () use ($request, $passageGroup): void {
            $passageGroup->update($request->safe()->only(['content']));
        });

        return redirect()->route('branch.questions.create', $passageGroup->exam)->with('success', 'Passage/Summary updated successfully.');
    }

    public function destroy(Request $request, PassageGroup $passageGroup): RedirectResponse
    {
        $this->authorizeExam($request, $passageGroup->exam);
        abort_if($passageGroup->exam->hasBeenAttempted(), 403, Exam::LOCK_MESSAGE);

        $exam = $passageGroup->exam;

        DB::transaction(function () use ($passageGroup): void {
            if ($passageGroup->image_path) {
                Storage::disk('public')->delete($passageGroup->image_path);
            }

            $passageGroup->delete();
        });

        $exam->recalculateTotalMarks();

        return redirect()->route('branch.questions.create', $exam)
            ->with('success', 'Passage/Summary and all its questions were deleted successfully.');
    }

    private function authorizeExam(Request $request, Exam $exam): void
    {
        abort_if(! $request->user()->branch_id || $exam->branch_id !== $request->user()->branch_id, 403, 'This exam does not belong to your branch.');
    }
}
