<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\PassageGroupRequest;
use App\Models\Exam;
use App\Models\PassageGroup;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class PassageGroupController extends Controller
{
    public function create(Exam $exam): View
    {
        abort_if($exam->hasBeenAttempted(), 403, Exam::LOCK_MESSAGE);

        return view('admin.passage-groups.create', [
            'selectedBranch' => $exam->branch,
            'exam' => $exam,
            'passageGroup' => new PassageGroup,
        ]);
    }

    public function store(PassageGroupRequest $request, Exam $exam): RedirectResponse
    {
        abort_if($exam->hasBeenAttempted(), 403, Exam::LOCK_MESSAGE);

        $passageGroup = DB::transaction(function () use ($request, $exam) {
            $validated = $request->safe()->only(['content']);
            $validated['title'] = 'Summary '.($exam->passageGroups()->count() + 1);
            $validated['position'] = $exam->nextTopLevelPosition();

            return $exam->passageGroups()->create($validated);
        });

        return redirect()->to(route('admin.questions.create', $exam).'#summary-'.$passageGroup->id)
            ->with('success', 'Summary added. Now add its questions below.')
            ->with('open_summary_id', $passageGroup->id);
    }

    public function edit(PassageGroup $passageGroup): View
    {
        abort_if($passageGroup->exam->hasBeenAttempted(), 403, Exam::LOCK_MESSAGE);

        return view('admin.passage-groups.edit', [
            'selectedBranch' => $passageGroup->exam->branch,
            'exam' => $passageGroup->exam,
            'passageGroup' => $passageGroup,
        ]);
    }

    public function update(PassageGroupRequest $request, PassageGroup $passageGroup): RedirectResponse
    {
        abort_if($passageGroup->exam->hasBeenAttempted(), 403, Exam::LOCK_MESSAGE);

        DB::transaction(function () use ($request, $passageGroup): void {
            $passageGroup->update($request->safe()->only(['content']));
        });

        return redirect()->route('admin.questions.create', $passageGroup->exam)->with('success', 'Passage/Summary updated successfully.');
    }

    public function destroy(PassageGroup $passageGroup): RedirectResponse
    {
        abort_if($passageGroup->exam->hasBeenAttempted(), 403, Exam::LOCK_MESSAGE);

        $exam = $passageGroup->exam;

        DB::transaction(function () use ($passageGroup): void {
            if ($passageGroup->image_path) {
                Storage::disk('public')->delete($passageGroup->image_path);
            }

            $passageGroup->delete();
        });

        $exam->recalculateTotalMarks();

        return redirect()->route('admin.questions.create', $exam)
            ->with('success', 'Passage/Summary and all its questions were deleted successfully.');
    }
}
