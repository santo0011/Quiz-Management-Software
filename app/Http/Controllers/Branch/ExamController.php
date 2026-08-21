<?php

namespace App\Http\Controllers\Branch;

use App\Http\Controllers\Controller;
use App\Http\Requests\ExamCategoryRequest;
use App\Http\Requests\ExamRequest;
use App\Http\Requests\ExamSettingsRequest;
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
            'classes' => SchoolClass::visibleToBranch($branch->id)->orderBy('name')->get(),
            'filters' => $request->only(['search', 'status']),
        ]);
    }

    public function store(ExamRequest $request): RedirectResponse
    {
        $branch = $request->user()->branch;
        abort_if(! $branch, 403, 'Your account is not linked to a branch.');

        Exam::create($request->validated() + [
            'branch_id' => $branch->id,
            'status' => Exam::STATUS_DRAFT,
            'total_marks' => 0,
            'marks_per_question' => 1,
            'duration_minutes' => 30,
        ]);

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
        abort_if($exam->hasBeenAttempted(), 403, Exam::LOCK_MESSAGE);

        return view('branch.exams.edit', [
            'branch' => $request->user()->branch,
            'exam' => $exam,
            'classes' => SchoolClass::visibleToBranch($request->user()->branch_id)->orderBy('name')->get(),
        ]);
    }

    public function update(ExamRequest $request, Exam $exam): RedirectResponse
    {
        $this->authorizeExam($request, $exam);
        abort_if($exam->hasBeenAttempted(), 403, Exam::LOCK_MESSAGE);

        $exam->update($request->validated());

        return redirect()->route('branch.exams.index')->with('success', 'Exam updated successfully.');
    }

    public function updateSettings(ExamSettingsRequest $request, Exam $exam): RedirectResponse
    {
        $exam->update($request->validated());
        $exam->recalculateTotalMarks();

        return redirect()->route('branch.exams.show', $exam)->with('success', 'Exam settings updated successfully.');
    }

    public function updateCategory(ExamCategoryRequest $request, Exam $exam): RedirectResponse
    {
        $exam->update($request->validated());

        return redirect()->route('branch.questions.create', $exam)->with('success', 'Question category saved successfully.');
    }

    public function reorderItems(Request $request, Exam $exam): RedirectResponse
    {
        $this->authorizeExam($request, $exam);
        abort_if($exam->hasBeenAttempted(), 403, Exam::LOCK_MESSAGE);

        $validated = $request->validate([
            'type' => ['required', 'in:question,passage_group'],
            'id' => ['required', 'integer'],
            'direction' => ['required', 'in:up,down'],
        ]);

        $this->swapItemPosition($exam, $validated['type'], (int) $validated['id'], $validated['direction']);

        return redirect()->route('branch.exams.show', $exam)->with('success', 'Order updated successfully.');
    }

    private function swapItemPosition(Exam $exam, string $type, int $id, string $direction): void
    {
        if ($type === 'question') {
            $question = $exam->questions()->find($id);

            if ($question && $question->passage_group_id) {
                $this->swapWithinSiblings($question->passageGroup->questions()->orderBy('position')->get(), $id, $direction);

                return;
            }
        }

        $items = $exam->orderedItems();

        $currentIndex = $items->search(fn (array $item) => $item['type'] === $type
            && ($item['type'] === 'question' ? $item['question']->id : $item['group']->id) === $id);

        if ($currentIndex === false) {
            return;
        }

        $targetIndex = $direction === 'up' ? $currentIndex - 1 : $currentIndex + 1;

        if (! $items->has($targetIndex)) {
            return;
        }

        $currentModel = $items[$currentIndex]['type'] === 'question' ? $items[$currentIndex]['question'] : $items[$currentIndex]['group'];
        $targetModel = $items[$targetIndex]['type'] === 'question' ? $items[$targetIndex]['question'] : $items[$targetIndex]['group'];

        [$currentModel->position, $targetModel->position] = [$targetModel->position, $currentModel->position];
        $currentModel->save();
        $targetModel->save();
    }

    private function swapWithinSiblings(\Illuminate\Support\Collection $siblings, int $id, string $direction): void
    {
        $siblings = $siblings->values();
        $currentIndex = $siblings->search(fn ($item) => $item->id === $id);

        if ($currentIndex === false) {
            return;
        }

        $targetIndex = $direction === 'up' ? $currentIndex - 1 : $currentIndex + 1;

        if (! $siblings->has($targetIndex)) {
            return;
        }

        $current = $siblings[$currentIndex];
        $target = $siblings[$targetIndex];

        [$current->position, $target->position] = [$target->position, $current->position];
        $current->save();
        $target->save();
    }

    public function destroy(Request $request, Exam $exam): RedirectResponse
    {
        $this->authorizeExam($request, $exam);

        if ($exam->hasBeenAttempted()) {
            return redirect()->route('branch.exams.index')
                ->with('error', Exam::LOCK_MESSAGE);
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
